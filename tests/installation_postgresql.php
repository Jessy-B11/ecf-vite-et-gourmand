<?php

// php tests/installation_postgresql.php : crée puis supprime une base PostgreSQL vide.
// Le rôle PostgreSQL local configuré doit disposer du droit CREATEDB.
use App\Kernel;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
$kernel = new Kernel('dev', true);
$source = null;
$test = null;
$cree = false;
$status = 0;
$nom = 'vg_install_test_' . bin2hex(random_bytes(8));

function verifier(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

try {
    $kernel->boot();
    $source = $kernel->getContainer()->get('doctrine')->getConnection();
    verifier($source->getDatabasePlatform() instanceof PostgreSQLPlatform, 'PostgreSQL requis.');
    verifier(!$source->isTransactionActive(), 'La création de base exige une connexion hors transaction.');
    verifier((bool) $source->fetchOne('SELECT rolcreatedb OR rolsuper FROM pg_roles WHERE rolname = current_user'),
        'Le rôle local doit disposer du droit CREATEDB pour ce test.');
    verifier((bool) preg_match('/^vg_install_test_[a-f0-9]{16}$/D', $nom)
        && $nom !== $source->getDatabase(), 'Seule une nouvelle base de test peut être ciblée.');
    $identifiant = $source->quoteIdentifier($nom);
    $source->executeStatement('CREATE DATABASE ' . $identifiant . ' TEMPLATE template0');
    $cree = true;

    $params = $source->getParams();
    unset($params['url']);
    $params['dbname'] = $nom;
    $test = DriverManager::getConnection($params);
    verifier($test->fetchOne('SELECT current_database()') === $nom, 'Connexion exclusivement à la nouvelle base.');
    verifier((int) $test->fetchOne("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public'") === 0,
        'La base de test doit être vide avant installation.');

    $creation = file_get_contents(dirname(__DIR__) . '/sql/01_creation.sql');
    $donnees = file_get_contents(dirname(__DIR__) . '/sql/02_donnees.sql');
    $test->executeStatement($creation);
    verifier((int) $test->fetchOne('SELECT count(*) FROM horaire_jour') === 0, 'Le schéma initial est sans données horaires.');
    $test->executeStatement($donnees);
    foreach (['theme' => 4, 'regime' => 3, 'menu' => 2, 'plat' => 3, 'menu_plat' => 3,
        'image_menu' => 1, 'horaire_jour' => 7, 'creneau_horaire' => 11] as $table => $attendu) {
        verifier((int) $test->fetchOne('SELECT count(*) FROM ' . $table) === $attendu, 'Données initiales : ' . $table);
    }
    verifier(array_map('intval', $test->fetchFirstColumn('SELECT jour_semaine FROM horaire_jour ORDER BY jour_semaine'))
        === range(1, 7), 'Les sept jours sont créés.');
    verifier((int) $test->fetchOne('SELECT count(*) FROM horaire_jour WHERE jour_semaine = 7 AND ferme IS TRUE') === 1,
        'Le dimanche proposé est fermé.');
    verifier((int) $test->fetchOne("SELECT count(*) FROM creneau_horaire c JOIN horaire_jour h ON h.id = c.horaire_jour_id
        WHERE h.jour_semaine BETWEEN 1 AND 5 AND ((c.heure_ouverture = TIME '09:00' AND c.heure_fermeture = TIME '12:00')
        OR (c.heure_ouverture = TIME '14:00' AND c.heure_fermeture = TIME '18:00'))") === 10, 'Deux créneaux du lundi au vendredi.');
    verifier((int) $test->fetchOne("SELECT count(*) FROM creneau_horaire c JOIN horaire_jour h ON h.id = c.horaire_jour_id
        WHERE h.jour_semaine = 6 AND c.heure_ouverture = TIME '10:00' AND c.heure_fermeture = TIME '14:00'") === 1,
        'Créneau proposé du samedi.');

    verifier((bool) preg_match('/-- DEBUT HORAIRES PROPOSES POUR LE PROJET(.*?)-- FIN HORAIRES PROPOSES POUR LE PROJET\s*COMMIT;\s*$/s', $donnees, $bloc),
        'Le bloc horaires doit précéder le COMMIT final.');
    $avant = [
        $test->fetchAllAssociative('SELECT * FROM horaire_jour ORDER BY id'),
        $test->fetchAllAssociative('SELECT * FROM creneau_horaire ORDER BY id'),
    ];
    // Seul ce bloc est rejoué : les autres données initiales ne sont pas idempotentes.
    $test->executeStatement("BEGIN;\n" . $bloc[1] . "\nCOMMIT;");
    verifier([
        $test->fetchAllAssociative('SELECT * FROM horaire_jour ORDER BY id'),
        $test->fetchAllAssociative('SELECT * FROM creneau_horaire ORDER BY id'),
    ] === $avant, 'Rejouer le bloc horaires ne modifie aucune ligne installée.');
    echo "OK : base PostgreSQL réellement vide, installation complète 01_creation.sql puis 02_donnees.sql.\n";
    echo "OK : données catalogue initiales, sept jours / onze créneaux, dimanche fermé ; rejeu du bloc horaires identique.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e::class . ($e instanceof RuntimeException ? ': ' . $e->getMessage() : '') . PHP_EOL);
    $status = 1;
} finally {
    if ($test?->isTransactionActive()) {
        $test->rollBack();
    }
    $test?->close();
    if ($cree) {
        try {
            $source->executeStatement('DROP DATABASE ' . $source->quoteIdentifier($nom));
            echo "OK : base de test supprimée ; base de travail non modifiée.\n";
        } catch (Throwable $e) {
            fwrite(STDERR, 'Nettoyage de la base temporaire impossible (' . $e::class . ').' . PHP_EOL);
            $status = 1;
        }
    }
    $source?->close();
    $kernel->shutdown();
}
exit($status);
