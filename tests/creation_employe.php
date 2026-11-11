<?php

// Exécution : php tests/creation_employe.php
// Tests HTTP avec le vrai kernel, sans dépendance de test supplémentaire.
// La base SQLite temporaire est créée puis supprimée, sans toucher à la base locale.

use App\Entity\Utilisateur;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

function verifier(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requete(array &$cookies, string $method, string $path, array $data = []): Response
{
    $kernel = new Kernel('test', false);
    $request = Request::create('http://localhost' . $path, $method, $data, $cookies, [], [
        'HTTP_ORIGIN' => 'http://localhost',
    ]);

    try {
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->isCleared()) {
                unset($cookies[$cookie->getName()]);
            } else {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }
        }

        return $response;
    } finally {
        $kernel->shutdown();
    }
}

function champs(Response $response): array
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8">' . $response->getContent());
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $fields = [];

    foreach ((new DOMXPath($document))->query('//form//input[@name]') as $input) {
        $fields[$input->getAttribute('name')] = $input->getAttribute('value');
    }

    return $fields;
}

function connexion(string $email, string $password): array
{
    $cookies = [];
    $login = champs(requete($cookies, 'GET', '/login'));
    $response = requete($cookies, 'POST', '/login', [
        '_username' => $email,
        '_password' => $password,
        '_csrf_token' => $login['_csrf_token'],
    ]);
    verifier($response->isRedirect() && !str_ends_with($response->headers->get('Location'), '/login'),
        'La connexion doit réussir.');

    return $cookies;
}

function soumettre(array &$cookies, array $overrides = []): Response
{
    $page = requete($cookies, 'GET', '/admin/employes/creer');
    verifier($page->getStatusCode() === 200, 'Le gérant doit accéder au formulaire.');
    $fields = champs($page);

    return requete($cookies, 'POST', '/admin/employes/creer', [
        'employe' => array_replace_recursive([
            'prenom' => 'Camille',
            'nom' => 'Martin',
            'email' => 'nouvel-employe@example.test',
            'plainPassword' => ['first' => 'InitialValide42!', 'second' => 'InitialValide42!'],
            '_token' => $fields['employe[_token]'],
        ], $overrides),
    ]);
}

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
$database = tempnam(sys_get_temp_dir(), 'vg-employe-');
verifier($database !== false, 'Impossible de créer la base temporaire.');
$databaseUrl = 'sqlite:///' . str_replace('\\', '/', $database);
putenv('DATABASE_URL=' . $databaseUrl);
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $databaseUrl;

$kernel = new Kernel('test', false);
$entityManager = null;

try {
    $kernel->boot();
    $container = $kernel->getContainer()->get('test.service_container');
    $entityManager = $container->get(EntityManagerInterface::class);
    $connection = $entityManager->getConnection();
    verifier($connection->getParams()['driver'] === 'pdo_sqlite'
        && str_replace('\\', '/', $connection->getParams()['path']) === str_replace('\\', '/', $database),
        'Les tests doivent utiliser exclusivement la base temporaire.');
    (new SchemaTool($entityManager))->createSchema([$entityManager->getClassMetadata(Utilisateur::class)]);
    $hasher = $container->get(UserPasswordHasherInterface::class);

    foreach (['admin' => 'ROLE_ADMIN', 'employe' => 'ROLE_EMPLOYE', 'client' => 'ROLE_CLIENT'] as $name => $role) {
        $user = (new Utilisateur())
            ->setEmail($name . '@example.test')
            ->setPrenom(ucfirst($name))
            ->setNom('Test')
            ->setRoles([$role]);
        $user->setPassword($hasher->hashPassword($user, 'ConnexionValide42!'));
        $entityManager->persist($user);
    }
    $entityManager->flush();

    $anonymous = [];
    foreach (['GET', 'POST'] as $method) {
        $response = requete($anonymous, $method, '/admin/employes/creer');
        verifier($response->isRedirect() && str_ends_with($response->headers->get('Location'), '/login'),
            'Un visiteur doit être redirigé vers la connexion.');
    }
    echo "OK : visiteur redirigé vers la connexion (GET et POST).\n";

    foreach (['employe', 'client'] as $role) {
        $cookies = connexion($role . '@example.test', 'ConnexionValide42!');
        verifier(!str_contains(requete($cookies, 'GET', '/login')->getContent(), '/admin/employes/creer'),
            'Le lien de création doit être masqué pour ' . $role . '.');
        foreach (['GET', 'POST'] as $method) {
            verifier(requete($cookies, $method, '/admin/employes/creer')->getStatusCode() === 403,
                'Accès à refuser pour ' . $role . ' en ' . $method . '.');
        }
    }
    echo "OK : employé et client refusés (GET et POST), lien masqué.\n";

    $admin = connexion('admin@example.test', 'ConnexionValide42!');
    $page = requete($admin, 'GET', '/admin/employes/creer');
    verifier($page->getStatusCode() === 200 && str_contains($page->getContent(), '/admin/employes/creer'),
        'Le gérant doit accéder à la page et à son lien de navigation.');
    $names = array_keys(champs($page));
    sort($names);
    $expected = ['employe[prenom]', 'employe[nom]', 'employe[email]',
        'employe[plainPassword][first]', 'employe[plainPassword][second]', 'employe[_token]'];
    sort($expected);
    verifier($names === $expected, 'Le formulaire doit exposer uniquement les champs prévus, sans rôle.');

    $invalid = [
        'CSRF absent' => ['_token' => ''],
        'CSRF invalide' => ['_token' => 'jeton-invalide'],
        'nom vide' => ['nom' => ''],
        'prénom vide' => ['prenom' => ''],
        'nom trop long' => ['nom' => str_repeat('a', 101)],
        'prénom trop long' => ['prenom' => str_repeat('a', 101)],
        'email invalide' => ['email' => 'invalide'],
        'mot de passe vide' => ['plainPassword' => ['first' => '', 'second' => '']],
        'mot de passe trop court' => ['plainPassword' => ['first' => 'Aa1!', 'second' => 'Aa1!']],
        'majuscule absente' => ['plainPassword' => ['first' => 'initialvalide42!', 'second' => 'initialvalide42!']],
        'minuscule absente' => ['plainPassword' => ['first' => 'INITIALVALIDE42!', 'second' => 'INITIALVALIDE42!']],
        'chiffre absent' => ['plainPassword' => ['first' => 'InitialValide!!', 'second' => 'InitialValide!!']],
        'caractère spécial absent' => ['plainPassword' => ['first' => 'InitialValide42', 'second' => 'InitialValide42']],
        'confirmation différente' => ['plainPassword' => ['second' => 'AutreMotDePasse42!']],
        'injection de rôle' => ['roles' => ['ROLE_ADMIN']],
    ];
    foreach ($invalid as $name => $data) {
        $response = soumettre($admin, $data);
        verifier(!$response->isRedirect() && str_contains($response->getContent(), 'Le compte employé n’a pas été créé.'),
            'Le formulaire doit refuser : ' . $name . '.');
        verifier((int) $connection->fetchOne('SELECT COUNT(*) FROM utilisateur') === 3,
            'Aucun compte ne doit être créé pour : ' . $name . '.');
    }
    echo "OK : CSRF, identité, email, règles du mot de passe, confirmation et injection de rôle refusés.\n";

    $duplicate = soumettre($admin, ['email' => 'client@example.test']);
    verifier(str_contains($duplicate->getContent(), 'Un compte existe déjà avec cette adresse email.')
        && (int) $connection->fetchOne('SELECT COUNT(*) FROM utilisateur') === 3,
        'Un email déjà utilisé par un client doit afficher une erreur sans créer de compte.');

    $created = soumettre($admin);
    verifier($created->getStatusCode() === 303
        && $created->headers->get('Location') === '/admin/employes/creer',
        'La création doit rediriger vers le formulaire.');
    $entityManager->clear();
    $employee = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => 'nouvel-employe@example.test']);
    verifier($employee instanceof Utilisateur && $employee->getRoles() === ['ROLE_EMPLOYE', 'ROLE_USER']
        && $employee->isActif() && $employee->getPrenom() === 'Camille' && $employee->getNom() === 'Martin'
        && $employee->getPassword() !== 'InitialValide42!' && $hasher->isPasswordValid($employee, 'InitialValide42!'),
        'Le nouveau compte actif doit avoir le rôle employé et un mot de passe correctement haché.');
    $confirmation = requete($admin, 'GET', '/admin/employes/creer');
    verifier($confirmation->getStatusCode() === 200
        && str_contains($confirmation->getContent(), 'Le compte employé a bien été créé.'),
        'Le gérant doit rester connecté et voir la confirmation après redirection.');
    echo "OK : création par le gérant, rôle employé imposé, hachage, redirection et confirmation.\n";

    $duplicate = soumettre($admin);
    verifier(str_contains($duplicate->getContent(), 'Un compte existe déjà avec cette adresse email.')
        && (int) $connection->fetchOne('SELECT COUNT(*) FROM utilisateur') === 4,
        'Une seconde création avec le même email employé doit être refusée.');
    echo "OK : email en double refusé pour un client existant et un employé existant.\n";

    $employeeCookies = connexion('nouvel-employe@example.test', 'InitialValide42!');
    verifier(requete($employeeCookies, 'GET', '/admin/employes/creer')->getStatusCode() === 403,
        'Le nouvel employé peut se connecter mais ne peut pas créer un autre employé.');
    echo "OK : connexion du nouvel employé et refus de la création de comptes.\n";
} finally {
    $entityManager?->getConnection()->close();
    $kernel->shutdown();
    gc_collect_cycles();
    unlink($database);
}
