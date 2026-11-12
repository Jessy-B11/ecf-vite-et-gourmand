<?php

// Exécution : php tests/statistiques_dashboard.php (extension mongodb et MongoDB local requis).
// Authentification dans SQLite temporaire et statistiques dans une base MongoDB de test aléatoire.

use App\Entity\Utilisateur;
use App\Kernel;
use App\Service\StatistiquesCommandes;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
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

function requete(array &$cookies, string $path, string $method = 'GET', array $data = []): Response
{
    $kernel = new Kernel('test', true);
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

function xpath(Response $response): DOMXPath
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8">' . $response->getContent());
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return new DOMXPath($document);
}

function connexion(string $role): array
{
    $cookies = [];
    $login = requete($cookies, '/login');
    $token = xpath($login)->evaluate('string(//input[@name="_csrf_token"]/@value)');
    $response = requete($cookies, '/login', 'POST', [
        '_username' => $role . '@example.test', '_password' => 'ConnexionValide42!', '_csrf_token' => $token,
    ]);
    verifier($response->isRedirect() && !str_ends_with($response->headers->get('Location'), '/login'),
        'La connexion doit réussir pour ' . $role . '.');

    return $cookies;
}

function verifierCa(array &$cookies, array $filters, string $expected, int $finished): Response
{
    $query = $filters === [] ? '' : '?' . http_build_query($filters);
    $response = requete($cookies, '/admin/statistiques' . $query);
    verifier($response->getStatusCode() === 200, 'La page filtrée doit répondre 200 : ' . $query);
    $xpath = xpath($response);
    verifier(trim($xpath->evaluate('string(//table[@id="stats-ca-tableau"]/tfoot/tr/td[last()])')) === $expected,
        'CA attendu : ' . $expected . ' pour ' . $query);
    verifier((int) $xpath->evaluate('string(//table[@id="stats-ca-tableau"]/tfoot/tr/td[1])') === $finished,
        'Nombre de commandes terminées incorrect pour ' . $query);

    return $response;
}

function document(int $id, int $menu, string $date, string $statut, int $montant, int $remise): array
{
    return [
        '_id' => $id,
        'menu' => ['id' => $menu, 'nom' => [1 => 'Menu Bordeaux', 2 => 'Menu Jardin', 3 => 'Menu Sans vente'][$menu]],
        'date_creation' => new UTCDateTime(new DateTimeImmutable($date)),
        // Délibérément différente de la période filtrée : elle ne doit pas servir au calcul.
        'date_prestation' => '2027-12-31', 'statut' => $statut, 'nombre_personnes' => 10,
        'montant_menu_centimes' => $montant, 'montant_remise_centimes' => $remise,
        'frais_livraison_centimes' => 12345, 'montant_total_centimes' => $montant - $remise + 12345,
    ];
}

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
verifier(extension_loaded('mongodb'), 'L’extension PHP mongodb doit être activée.');
$uri = $_SERVER['MONGODB_URI'] ?? $_ENV['MONGODB_URI'];
$mongoDatabase = 'vg_dashboard_test_' . bin2hex(random_bytes(8));
$_SERVER['MONGODB_DATABASE'] = $_ENV['MONGODB_DATABASE'] = $mongoDatabase;
putenv('MONGODB_DATABASE=' . $mongoDatabase);
$sqlite = tempnam(sys_get_temp_dir(), 'vg-dashboard-');
verifier($sqlite !== false, 'Impossible de créer la base temporaire.');
$databaseUrl = 'sqlite:///' . str_replace('\\', '/', $sqlite);
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $databaseUrl;
putenv('DATABASE_URL=' . $databaseUrl);
$kernel = new Kernel('test', true);
$entityManager = null;
$mongo = new Client($uri, ['serverSelectionTimeoutMS' => 3000]);

try {
    $kernel->boot();
    $container = $kernel->getContainer()->get('test.service_container');
    $entityManager = $container->get(EntityManagerInterface::class);
    verifier($entityManager->getConnection()->getParams()['driver'] === 'pdo_sqlite'
        && str_replace('\\', '/', $entityManager->getConnection()->getParams()['path']) === str_replace('\\', '/', $sqlite),
        'Les tests ne doivent pas utiliser la base PostgreSQL de travail.');
    (new SchemaTool($entityManager))->createSchema([$entityManager->getClassMetadata(Utilisateur::class)]);
    $hasher = $container->get(UserPasswordHasherInterface::class);
    foreach (['admin' => 'ROLE_ADMIN', 'employe' => 'ROLE_EMPLOYE', 'client' => 'ROLE_CLIENT'] as $name => $role) {
        $user = (new Utilisateur())->setEmail($name . '@example.test')->setRoles([$role]);
        $user->setPassword($hasher->hashPassword($user, 'ConnexionValide42!'));
        $entityManager->persist($user);
    }
    $entityManager->flush();
    $collection = $mongo->selectCollection($mongoDatabase, 'commandes');
    $collection->insertMany([
        document(1, 1, '2026-01-09T22:59:59.999Z', 'terminee', 10000, 1000),
        document(2, 1, '2026-01-09T23:00:00Z', 'terminee', 10000, 1500),
        document(3, 1, '2026-01-10T22:59:59.999Z', 'terminee', 20000, 2500),
        document(4, 1, '2026-01-10T23:00:00Z', 'terminee', 5000, 500),
        document(5, 1, '2026-01-10T12:00:00Z', 'annulee', 999999, 0),
        document(6, 1, '2026-01-10T12:00:00Z', 'en_attente', 999999, 0),
        document(7, 2, '2026-01-10T12:00:00Z', 'terminee', 12000, 2000),
        document(8, 2, '2026-01-10T12:00:00Z', 'livree', 999999, 0),
        document(9, 2, '2026-01-10T12:00:00Z', 'en_preparation', 999999, 0),
        document(10, 2, '2026-01-10T12:00:00Z', 'en_livraison', 999999, 0),
        document(11, 2, '2026-01-10T12:00:00Z', 'acceptee', 999999, 0),
        document(12, 2, '2026-01-10T12:00:00Z', 'attente_retour_materiel', 999999, 0),
        document(13, 3, '2026-01-10T12:00:00Z', 'annulee', 999999, 0),
    ]);

    $anonymous = [];
    $response = requete($anonymous, '/admin/statistiques');
    verifier($response->isRedirect() && str_ends_with($response->headers->get('Location'), '/login'),
        'Un visiteur doit être redirigé vers la connexion.');
    foreach (['client', 'employe'] as $role) {
        $cookies = connexion($role);
        verifier(requete($cookies, '/admin/statistiques')->getStatusCode() === 403,
            'Le rôle ' . $role . ' ne doit pas accéder aux statistiques.');
        verifier(!str_contains(requete($cookies, '/login')->getContent(), '/admin/statistiques'),
            'Le lien Statistiques doit être masqué pour ' . $role . '.');
    }
    echo "OK : visiteur redirigé, client et employé refusés, navigation réservée au gérant.\n";

    $admin = connexion('admin');
    $response = verifierCa($admin, [], '495,00 €', 5);
    $dom = xpath($response);
    verifier($dom->query('//nav/a[@href="/admin/statistiques"]')->length === 1,
        'Le gérant doit avoir un lien Statistiques dans la navigation.');
    verifier((int) $dom->evaluate('string(//table[@id="stats-commandes-tableau"]/tfoot/tr/td)') === 13,
        'Tous les statuts doivent compter dans les commandes enregistrées.');
    $counts = [];
    foreach ($dom->query('//table[@id="stats-commandes-tableau"]/tbody/tr/td') as $cell) {
        $counts[] = (int) $cell->textContent;
    }
    verifier($counts === [6, 6, 1] && $dom->query('//figure[contains(@class,"stats-chart")]//li')->length === 3,
        'Le graphique et le tableau doivent comparer les trois menus.');
    $chartCounts = [];
    foreach ($dom->query('//figure[contains(@class,"stats-chart")]//li/strong') as $count) {
        $chartCounts[] = (int) $count->textContent;
    }
    verifier($chartCounts === $counts, 'Le graphique doit afficher les mêmes valeurs MongoDB que le tableau.');
    $service = new StatistiquesCommandes($uri, $mongoDatabase);
    verifier($service->chiffreAffaires()['total_centimes'] === 49500,
        'Le calcul doit rester entier en centimes jusqu’à l’affichage.');
    echo "OK : données MongoDB uniquement, treize commandes tous statuts et CA de 49 500 centimes (495,00 €).\n";

    $response = verifierCa($admin, ['debut' => '2026-01-10', 'fin' => '2026-01-10'], '360,00 €', 3);
    verifier((int) xpath($response)->evaluate('string(//table[@id="stats-commandes-tableau"]/tfoot/tr/td)') === 13,
        'Les filtres de CA ne doivent pas modifier le graphique global.');
    verifierCa($admin, ['menu' => '1', 'debut' => '2026-01-10', 'fin' => '2026-01-10'], '260,00 €', 2);
    verifierCa($admin, ['menu' => '2'], '100,00 €', 1);
    verifierCa($admin, ['debut' => '2026-01-09', 'fin' => '2026-01-09'], '90,00 €', 1);
    verifierCa($admin, ['debut' => '2026-01-11', 'fin' => '2026-01-11'], '45,00 €', 1);
    verifierCa($admin, ['debut' => '2026-01-10'], '405,00 €', 4);
    verifierCa($admin, ['fin' => '2026-01-10'], '450,00 €', 4);
    $empty = verifierCa($admin, ['menu' => '3'], '0,00 €', 0);
    verifier(str_contains($empty->getContent(), 'Aucune commande terminée ne correspond'),
        'Un menu sans vente terminée doit afficher un résultat vide explicite.');
    verifierCa($admin, ['debut' => '2040-01-01'], '0,00 €', 0);
    echo "OK : filtres par menu, dates inclusives, bornes ouvertes, remises et exclusion de la livraison/non-terminées.\n";

    foreach ([
        ['debut' => '2026-01-11', 'fin' => '2026-01-10'], ['debut' => '2026-02-30'],
        ['fin' => 'invalide'], ['debut' => "2026-01-10\0"], ['fin' => ['2026-01-10']],
        ['menu' => '0'], ['menu' => '999'], ['menu' => ['$ne' => '1']],
    ] as $filters) {
        $invalid = requete($admin, '/admin/statistiques?' . http_build_query($filters));
        verifier($invalid->getStatusCode() === 400
            && str_contains($invalid->getContent(), 'Corrigez les filtres')
            && !str_contains($invalid->getContent(), 'id="stats-ca-tableau"'),
            'Une période ou un menu invalide ne doit pas afficher un CA trompeur.');
    }
    echo "OK : périodes impossibles/inversées et paramètres invalides refusés avec une erreur lisible.\n";

    $collection->insertMany([
        document(14, 2, '2026-03-28T22:59:59.999Z', 'terminee', 700, 0),
        document(15, 2, '2026-03-28T23:00:00Z', 'terminee', 800, 0),
        document(16, 2, '2026-03-29T21:59:59.999Z', 'terminee', 900, 0),
        document(17, 2, '2026-03-29T22:00:00Z', 'terminee', 1000, 0),
        document(18, 2, '2026-10-24T22:00:00Z', 'terminee', 1100, 0),
        document(19, 2, '2026-10-25T22:59:59.999Z', 'terminee', 1200, 0),
        document(20, 2, '2026-10-25T23:00:00Z', 'terminee', 1300, 0),
        document(21, 2, '2026-10-24T21:59:59.999Z', 'terminee', 1400, 0),
    ]);
    verifierCa($admin, ['debut' => '2026-03-29', 'fin' => '2026-03-29'], '17,00 €', 2);
    verifierCa($admin, ['debut' => '2026-10-25', 'fin' => '2026-10-25'], '23,00 €', 2);
    echo "OK : journées Europe/Paris de 23 et 25 heures, jusqu’à la dernière milliseconde incluse.\n";

    $collection->deleteMany([]);
    $empty = verifierCa($admin, [], '0,00 €', 0);
    verifier(str_contains($empty->getContent(), 'Aucune commande enregistrée dans les statistiques'),
        'Une base vide doit avoir un message spécifique.');
    $_SERVER['MONGODB_URI'] = $_ENV['MONGODB_URI'] = 'mongodb://identifiant:secret-test@127.0.0.1:1';
    putenv('MONGODB_URI=' . $_SERVER['MONGODB_URI']);
    $offline = requete($admin, '/admin/statistiques');
    verifier($offline->getStatusCode() === 503
        && str_contains($offline->getContent(), 'Les statistiques sont temporairement indisponibles')
        && !str_contains($offline->getContent(), 'Aucune commande enregistrée')
        && !str_contains($offline->getContent(), 'secret-test')
        && !str_contains($offline->getContent(), 'id="stats-ca-tableau"'),
        'Une panne MongoDB doit être distincte d’un résultat vide, sans révéler les identifiants.');
    echo "OK : base vide distincte d’une indisponibilité MongoDB (503), aucun secret affiché.\n";
} finally {
    $mongo->dropDatabase($mongoDatabase);
    $entityManager?->getConnection()->close();
    $kernel->shutdown();
    gc_collect_cycles();
    unlink($sqlite);
}
