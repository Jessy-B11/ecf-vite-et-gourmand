<?php

// php tests/horaires_postgresql.php : PostgreSQL local configuré, tables TEMP uniquement.
use App\Kernel;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
$kernel = new Kernel('dev', true);
$connection = null;
$status = 0;

function verifier(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

function lignes($connection): array
{
    return [
        $connection->fetchAllAssociative('SELECT * FROM horaire_jour ORDER BY jour_semaine'),
        $connection->fetchAllAssociative('SELECT * FROM creneau_horaire ORDER BY id'),
    ];
}

try {
    $kernel->boot();
    $connection = $kernel->getContainer()->get('doctrine')->getConnection();
    verifier($connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'PostgreSQL requis.');
    $avant = [
        $connection->fetchAllAssociative('SELECT * FROM public.horaire_jour ORDER BY id'),
        $connection->fetchAllAssociative('SELECT * FROM public.creneau_horaire ORDER BY id'),
    ];
    // PostgreSQL crée aussi des identités et index propres aux copies temporaires.
    $connection->executeStatement('CREATE TEMP TABLE horaire_jour (LIKE public.horaire_jour INCLUDING ALL)');
    $connection->executeStatement('CREATE TEMP TABLE creneau_horaire (LIKE public.creneau_horaire INCLUDING ALL)');
    foreach (['horaire_jour', 'creneau_horaire'] as $table) {
        verifier($connection->fetchOne("SELECT relpersistence FROM pg_class WHERE oid = to_regclass(?)", [$table]) === 't',
            'Le test doit écrire uniquement dans des tables temporaires.');
    }
    $donnees = file_get_contents(dirname(__DIR__) . '/sql/02_donnees.sql');
    verifier((bool) preg_match('/-- DEBUT HORAIRES PROPOSES POUR LE PROJET(.*?)-- FIN HORAIRES PROPOSES POUR LE PROJET/s', $donnees, $bloc),
        'Le bloc horaires doit être présent dans les données initiales.');
    // Le reste des données initiales n'est pas rejouable sur une base déjà installée.
    $sql = "BEGIN;\n" . $bloc[1] . "\nCOMMIT;";
    $connection->executeStatement($sql);
    $premier = lignes($connection);
    verifier(count($premier[0]) === 7 && count($premier[1]) === 11, 'Démonstration : sept jours, onze créneaux.');
    $connection->executeStatement($sql);
    verifier(lignes($connection) === $premier, 'Rejouer le script laisse toutes les lignes identiques.');

    // Vérifier le véritable mapping ORM et le footer sur PostgreSQL, sans écrire dans public.
    $request = Request::create('http://localhost/login');
    $response = $kernel->handle($request);
    $html = $response->getContent();
    verifier($response->getStatusCode() === 200 && str_contains($html, '09h00–12h00')
        && str_contains($html, '14h00–18h00') && str_contains($html, '10h00–14h00')
        && str_contains($html, 'Fermé'), 'Lecture PostgreSQL et format du footer.');
    $kernel->terminate($request, $response);
    if ($dir = getenv('HORAIRES_REVIEW_DIR')) {
        file_put_contents($dir . '/postgresql.html', $html);
    }

    $connection->executeStatement('DELETE FROM pg_temp.creneau_horaire');
    $connection->executeStatement('DELETE FROM pg_temp.horaire_jour');
    $connection->executeStatement('INSERT INTO horaire_jour (jour_semaine, ferme) VALUES (1, TRUE), (2, FALSE), (3, FALSE)');
    $connection->executeStatement("INSERT INTO creneau_horaire (horaire_jour_id, heure_ouverture, heure_fermeture)
        SELECT id, TIME '08:30', TIME '11:45' FROM horaire_jour WHERE jour_semaine = 2");
    $existants = lignes($connection);
    $connection->executeStatement($sql);
    $apres = lignes($connection);
    verifier(array_slice($apres[0], 0, 3) === $existants[0], 'Les jours présents, même sans créneaux, sont préservés.');
    verifier(array_slice($apres[1], 0, 1) === $existants[1] && count($apres[1]) === 6,
        'Le créneau existant est préservé, seuls les quatre jours absents sont complétés.');
    $connection->executeStatement($sql);
    verifier(lignes($connection) === $apres, 'Le rejeu sur une semaine partielle reste identique.');
    verifier([
        $connection->fetchAllAssociative('SELECT * FROM public.horaire_jour ORDER BY id'),
        $connection->fetchAllAssociative('SELECT * FROM public.creneau_horaire ORDER BY id'),
    ] === $avant, 'Les horaires réels doivent rester strictement identiques.');
    echo "OK PostgreSQL : script sur tables TEMP, sept jours / onze créneaux, rejeu identique, données existantes et jours sans créneaux préservés.\n";
    echo "OK : lecture ORM et rendu du footer sur PostgreSQL ; tables public inchangées.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e::class . ($e instanceof RuntimeException ? ': ' . $e->getMessage() : '') . PHP_EOL);
    $status = 1;
} finally {
    // La fermeture supprime les copies et leurs séquences temporaires.
    $connection?->close();
    $kernel->shutdown();
}
exit($status);
