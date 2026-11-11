<?php

// Exécution : php tests/demo_clients.php
// Vérifications sur SQLite temporaire, sans écrire dans la base de travail.

use App\Command\DemoClientsCommand;
use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

function verifier(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function identifiants(string $display): array
{
    preg_match_all('/^\h*(client\d{2}\.demo@example\.com)\h+(\S+)\h*$/m', str_replace("\r\n", "\n", $display), $matches);

    return array_combine($matches[1], $matches[2]);
}

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
$database = tempnam(sys_get_temp_dir(), 'vg-demo-clients-');
verifier($database !== false, 'Impossible de créer la base temporaire.');
$url = 'sqlite:///' . str_replace('\\', '/', $database);
putenv('DATABASE_URL=' . $url);
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $url;
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
    $tester = new CommandTester(new DemoClientsCommand($entityManager, $hasher));

    verifier($tester->execute([], ['interactive' => false]) === Command::FAILURE
        && (int) $connection->fetchOne('SELECT COUNT(*) FROM utilisateur') === 0,
        'Sans interaction, la commande doit refuser toute écriture.');
    foreach (['no', ''] as $answer) {
        $tester->setInputs([$answer]);
        verifier($tester->execute([], ['interactive' => true]) === Command::SUCCESS
            && (int) $connection->fetchOne('SELECT COUNT(*) FROM utilisateur') === 0,
            'Le refus ou la réponse par défaut doit laisser la base vide.');
    }
    echo "OK : confirmation obligatoire et refus par défaut, aucune écriture.\n";

    $tester->setInputs(['yes']);
    verifier($tester->execute([], ['interactive' => true]) === Command::SUCCESS, 'La création doit réussir.');
    $display = $tester->getDisplay();
    verifier(str_contains($display, 'Base ciblée : main'), 'La base doit être affichée avant confirmation.');
    $passwords = identifiants($display);
    verifier(count($passwords) === 8 && count(array_unique($passwords)) === 8,
        'Chaque compte créé doit avoir un mot de passe distinct affiché dans le terminal.');

    $expectedAddresses = [
        ['85 cours du Maréchal Juin', '33000', 'Bordeaux'],
        ['5 place Bardineau', '33000', 'Bordeaux'],
        ['4 rue Raymond Poincaré', '33100', 'Bordeaux'],
        ['2 rue Fieffé', '33800', 'Bordeaux'],
        ['19 place Charles de Gaulle', '33700', 'Mérignac'],
        ['21 rue de Camponac', '33600', 'Pessac'],
        ['1 allée Peixotto', '33400', 'Talence'],
        ['58 avenue du Maréchal de Lattre de Tassigny', '33130', 'Bègles'],
    ];
    $factory = $container->get(FormFactoryInterface::class);
    foreach ($expectedAddresses as $index => $address) {
        $email = sprintf('client%02d.demo@example.com', $index + 1);
        $client = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
        verifier($client instanceof Utilisateur && $client->getRoles() === ['ROLE_CLIENT', 'ROLE_USER']
            && $client->isActif() && $client->getDateCreation() <= new DateTimeImmutable()
            && [$client->getAdresse(), $client->getCodePostal(), $client->getVille()] === $address,
            'Le compte doit avoir le rôle, les valeurs par défaut et l’adresse demandés : ' . $email);
        verifier($client->getPassword() !== $passwords[$email]
            && $hasher->isPasswordValid($client, $passwords[$email]), 'Le mot de passe doit être haché.');
        $form = $factory->create(RegistrationFormType::class, $client, ['csrf_protection' => false]);
        $form->submit([
            'prenom' => $client->getPrenom(), 'nom' => $client->getNom(), 'email' => $client->getEmail(),
            'telephone' => $client->getTelephone(), 'adresse' => $client->getAdresse(),
            'codePostal' => $client->getCodePostal(), 'ville' => $client->getVille(),
            'plainPassword' => $passwords[$email], 'agreeTerms' => true,
        ]);
        verifier($form->isValid(), 'Les données doivent satisfaire les règles de l’inscription.');
    }
    $entityManager->clear();
    $cities = $connection->fetchAllKeyValue('SELECT ville, COUNT(*) FROM utilisateur GROUP BY ville');
    verifier(count($cities) === 5 && (int) $cities['Bordeaux'] === 4
        && (int) $cities['Mérignac'] === 1 && (int) $cities['Pessac'] === 1
        && (int) $cities['Talence'] === 1 && (int) $cities['Bègles'] === 1,
        'La répartition doit être de quatre Bordeaux et une par autre commune.');
    echo "OK : huit profils valides, adresses exactes, répartition des communes, mots de passe distincts et hachés.\n";

    $before = $connection->fetchAllAssociative('SELECT * FROM utilisateur ORDER BY email');
    $tester->setInputs(['yes']);
    verifier($tester->execute([], ['interactive' => true]) === Command::SUCCESS
        && $connection->fetchAllAssociative('SELECT * FROM utilisateur ORDER BY email') === $before,
        'La seconde exécution doit laisser toutes les données strictement identiques.');
    verifier(substr_count($tester->getDisplay(), 'Ignoré :') === 8
        && identifiants($tester->getDisplay()) === []
        && !str_contains($tester->getDisplay(), 'Mot de passe initial'),
        'Les huit adresses doivent être signalées comme ignorées, sans afficher leurs identifiants.');
    echo "OK : seconde exécution sans doublon, sans modification et sans réaffichage des identifiants.\n";

    // Un compte préexistant peut avoir un autre rôle, être désactivé ou avoir une autre adresse.
    $connection->executeStatement(
        'UPDATE utilisateur SET roles = ?, actif = ?, adresse = ?, nom = ? WHERE email = ?',
        ['["ROLE_ADMIN"]', 0, 'Adresse préexistante', 'Nom conservé', 'client01.demo@example.com']
    );
    $connection->executeStatement('DELETE FROM utilisateur WHERE email = ?', ['client08.demo@example.com']);
    $preserved = $connection->fetchAssociative('SELECT * FROM utilisateur WHERE email = ?', ['client01.demo@example.com']);
    $entityManager->clear();
    $tester->setInputs(['yes']);
    verifier($tester->execute([], ['interactive' => true]) === Command::SUCCESS
        && $connection->fetchAssociative('SELECT * FROM utilisateur WHERE email = ?', ['client01.demo@example.com']) === $preserved
        && (int) $connection->fetchOne('SELECT COUNT(*) FROM utilisateur') === 8,
        'Les comptes préexistants doivent rester intacts, quel que soit leur rôle ou leur profil.');
    verifier(array_keys(identifiants($tester->getDisplay())) === ['client08.demo@example.com']
        && substr_count($tester->getDisplay(), 'Ignoré :') === 7,
        'Seul le compte manquant doit être créé et recevoir de nouveaux identifiants.');
    echo "OK : compte préexistant conservé intégralement, création des seuls comptes manquants.\n";
} finally {
    $entityManager?->getConnection()->close();
    $kernel->shutdown();
    gc_collect_cycles();
    unlink($database);
}
