<?php

// Exécution : php tests/stats_sync.php (extension mongodb, PostgreSQL et MongoDB locaux requis).
// Les tables PostgreSQL sont temporaires, et la base MongoDB de test est supprimée en fin de test.

use App\Command\StatsSyncCommand;
use App\Kernel;
use App\Service\SynchronisationStatistiques;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

function verifier(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

verifier(extension_loaded('mongodb'), 'L’extension PHP mongodb doit être activée.');
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', false);
$connection = null;
$mongo = null;
$testDatabase = 'vg_stats_test_' . bin2hex(random_bytes(8));

try {
    $kernel->boot();
    $connection = $kernel->getContainer()->get('doctrine')->getConnection();
    verifier($connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
        'Ces tests doivent utiliser une connexion PostgreSQL.');
    $connection->beginTransaction();

    // pg_temp masque les tables réelles sur cette connexion uniquement.
    $connection->executeStatement(
        'CREATE TEMPORARY TABLE menu (id INTEGER PRIMARY KEY, nom TEXT, prix_minimum_centimes INTEGER) ON COMMIT DROP'
    );
    $connection->executeStatement(
        'CREATE TEMPORARY TABLE commande (
            id INTEGER PRIMARY KEY, menu_id INTEGER, nombre_personnes INTEGER,
            date_creation TIMESTAMPTZ, date_prestation DATE, statut TEXT,
            montant_menu_centimes INTEGER, montant_remise_centimes INTEGER,
            frais_livraison_centimes INTEGER, montant_total_centimes INTEGER,
            utilisateur_id INTEGER, adresse_livraison TEXT, telephone_contact TEXT, jeton_validation TEXT
        ) ON COMMIT DROP'
    );
    $connection->insert('menu', ['id' => 42, 'nom' => 'Menu de test', 'prix_minimum_centimes' => 999999]);
    $statuts = ['en_attente', 'acceptee', 'en_preparation', 'en_livraison', 'livree',
        'attente_retour_materiel', 'terminee', 'annulee'];
    foreach ($statuts as $index => $statut) {
        $connection->insert('commande', [
            'id' => $index + 1, 'menu_id' => 42, 'nombre_personnes' => 12,
            'date_creation' => '2026-11-01 01:30:00+01', 'date_prestation' => '2026-11-12',
            'statut' => $statut, 'montant_menu_centimes' => 30000, 'montant_remise_centimes' => 3000,
            'frais_livraison_centimes' => 1500, 'montant_total_centimes' => 28500,
            'utilisateur_id' => 900, 'adresse_livraison' => 'NE PAS EXPORTER',
            'telephone_contact' => 'NE PAS EXPORTER', 'jeton_validation' => 'NE PAS EXPORTER',
        ]);
    }
    $uri = $_SERVER['MONGODB_URI'] ?? $_ENV['MONGODB_URI'];
    $mongo = new Client($uri, ['serverSelectionTimeoutMS' => 3000], [
        'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
    ]);
    $collection = $mongo->selectCollection($testDatabase, 'commandes');
    $tester = new CommandTester(new StatsSyncCommand(new SynchronisationStatistiques($connection, $uri, $testDatabase)));

    verifier($tester->execute([]) === Command::SUCCESS && $collection->countDocuments() === 8,
        'La première synchronisation doit créer huit documents.');
    $before = $collection->find([], ['sort' => ['_id' => 1]])->toArray();
    $expectedKeys = ['_id', 'menu', 'nombre_personnes', 'date_creation', 'date_prestation', 'statut',
        'montant_menu_centimes', 'montant_remise_centimes', 'frais_livraison_centimes', 'montant_total_centimes'];
    foreach ($before as $index => $document) {
        verifier(array_keys($document) === $expectedKeys && $document['_id'] === $index + 1
            && $document['menu'] === ['id' => 42, 'nom' => 'Menu de test']
            && $document['statut'] === $statuts[$index], 'La projection doit être limitée aux champs statistiques.');
        verifier($document['date_creation'] instanceof UTCDateTime
            && $document['date_creation']->toDateTimeImmutable()->format('Y-m-d H:i:sP') === '2026-11-01 00:30:00+00:00'
            && $document['date_prestation'] === '2026-11-12', 'Les dates et leur fuseau doivent être préservés.');
        verifier($document['montant_menu_centimes'] === 30000 && $document['montant_remise_centimes'] === 3000
            && $document['frais_livraison_centimes'] === 1500 && $document['montant_total_centimes'] === 28500,
            'Les montants historiques doivent être des entiers en centimes, indépendants du prix actuel du menu.');
    }
    echo "OK : huit statuts synchronisés, dates correctes, montants historiques en centimes et aucune donnée client.\n";

    $connection->executeStatement('UPDATE menu SET prix_minimum_centimes = 1');
    verifier($tester->execute([]) === Command::SUCCESS
        && $collection->countDocuments() === 8
        && serialize($collection->find([], ['sort' => ['_id' => 1]])->toArray()) === serialize($before)
        && str_contains($tester->getDisplay(), '0 document(s) créé(s), 0 modifié(s), 8 inchangé(s)'),
        'Une seconde synchronisation doit laisser les documents strictement identiques.');
    echo "OK : seconde synchronisation sans doublon ni modification, même après changement du tarif du menu.\n";

    $connection->executeStatement("UPDATE commande SET statut = 'acceptee' WHERE id = 1");
    verifier($tester->execute([]) === Command::SUCCESS && $collection->countDocuments() === 8
        && $collection->findOne(['_id' => 1])['statut'] === 'acceptee'
        && str_contains($tester->getDisplay(), '0 document(s) créé(s), 1 modifié(s), 7 inchangé(s)'),
        'Le changement de statut doit modifier le document existant.');
    echo "OK : changement de statut répercuté sur le même identifiant, sans ajout de document.\n";

    $collection->updateOne(['_id' => 1], ['$set' => ['telephone_contact' => 'Ancienne donnée à supprimer']]);
    verifier($tester->execute([]) === Command::SUCCESS
        && !array_key_exists('telephone_contact', $collection->findOne(['_id' => 1])),
        'Le remplacement doit éliminer les champs hors projection, même sur un ancien document.');

    $offline = new CommandTester(new StatsSyncCommand(new SynchronisationStatistiques(
        $connection, 'mongodb://identifiant:secret-test@127.0.0.1:1', $testDatabase
    )));
    verifier($offline->execute([]) === Command::FAILURE
        && str_contains($offline->getDisplay(), 'MongoDB est inaccessible')
        && !str_contains($offline->getDisplay(), 'secret-test'),
        'Un serveur inaccessible doit produire un échec clair sans exposer l’URI.');
    echo "OK : MongoDB inaccessible signalé clairement, code d’échec 1 et aucun secret affiché.\n";
} finally {
    if ($connection?->isTransactionActive()) {
        $connection->rollBack();
    }
    // Nom aléatoire créé par ce script : ne jamais supprimer la base statistique configurée.
    if ($mongo !== null) {
        $mongo->dropDatabase($testDatabase);
    }
    $connection?->close();
    $kernel->shutdown();
}
