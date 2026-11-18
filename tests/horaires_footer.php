<?php

// php tests/horaires_footer.php : SQLite temporaire, aucune donnée locale modifiée.
use App\Entity\{CreneauHoraire, HoraireJour, Menu, Regime, Theme};
use App\Kernel;
use App\Twig\HorairesExtension;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['MAILER_DSN'] = $_ENV['MAILER_DSN'] = 'null://null';
putenv('MAILER_DSN=null://null');
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
$file = tempnam(sys_get_temp_dir(), 'vg-horaires-');
$url = 'sqlite:///' . str_replace('\\', '/', $file);
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $url;
putenv('DATABASE_URL=' . $url);

function verifier(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

function lireFooter(string $html): array
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    $jours = [];
    foreach ($xpath->query('//section[@aria-labelledby="footer-horaires"]/dl/div') as $ligne) {
        $jours[trim($xpath->evaluate('string(dt)', $ligne))] = preg_replace(
            '/\s+/u', ' ', trim($xpath->evaluate('string(dd)', $ligne))
        );
    }

    return $jours;
}

function compterRequetes(object $container): int
{
    $nombre = 0;
    foreach ($container->get('doctrine.debug_data_holder')->getData() as $requetes) {
        foreach ($requetes as $requete) {
            if (preg_match('/\bFROM (horaire_jour|creneau_horaire)\b/', $requete['sql'])) {
                ++$nombre;
            }
        }
    }

    return $nombre;
}

function page(string $path, string $nom, bool $ajax = false): string
{
    $kernel = new Kernel('test', true);
    $request = Request::create('http://localhost' . $path, server: $ajax ? ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'] : []);
    try {
        $response = $kernel->handle($request);
        verifier($response->getStatusCode() === 200, 'Page accessible : ' . $path);
        $container = $kernel->getContainer()->get('test.service_container');
        // Le contrôleur actuel renvoie aussi le gabarit complet aux requêtes AJAX.
        verifier(compterRequetes($container) === 1, 'Une seule lecture horaires : ' . $nom);
        if (!$ajax) {
            $stack = $container->get('request_stack');
            $stack->push($request);
            try {
                $extension = $container->get(HorairesExtension::class);
                $extension->getHoraires();
                $extension->getHoraires();
                verifier(compterRequetes($container) === 1, 'La semaine est réutilisée pendant la requête.');
            } finally {
                $stack->pop();
            }
        }
        $html = $response->getContent();
        if ($dir = getenv('HORAIRES_REVIEW_DIR')) {
            file_put_contents($dir . '/' . $nom . '.html', $html);
        }
        $kernel->terminate($request, $response);

        return $html;
    } finally {
        $kernel->shutdown();
    }
}

$kernel = new Kernel('test', true);
$em = null;
$status = 0;
try {
    $kernel->boot();
    $em = $kernel->getContainer()->get('doctrine')->getManager();
    (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
    $theme = (new Theme())->setLibelle('Test');
    $regime = (new Regime())->setLibelle('Test');
    $em->persist($theme);
    $em->persist($regime);
    foreach (range(1, 6) as $i) {
        $em->persist((new Menu())->setNom('Menu test ' . $i)->setDescription('Test')->setConditions('Test')
            ->setMinimumPersonnes(4)->setPrixMinimumCentimes(12000)->setDelaiMinimumJours(3)
            ->setStockCommandes(8)->setTheme($theme)->setRegime($regime));
    }
    $em->flush();
    $attendus = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    $vide = lireFooter(page('/login', 'vide'));
    verifier(array_keys($vide) === $attendus && count(array_unique($vide)) === 1
        && $vide['Lundi'] === 'Horaires non renseignés', 'Sept jours absents, sans horaires inventés.');

    $jours = [];
    foreach (range(7, 1) as $numero) {
        $jour = (new HoraireJour())->setJourSemaine($numero);
        $em->persist($jour);
        $jours[$numero] = $jour;
        $em->persist((new CreneauHoraire())->setHoraireJour($jour)
            ->setHeureOuverture(new DateTimeImmutable('09:00'))->setHeureFermeture(new DateTimeImmutable('12:00')));
    }
    $em->flush();
    $semaine = lireFooter(page('/', 'semaine'));
    verifier(array_keys($semaine) === $attendus && array_unique(array_values($semaine)) === ['09h00–12h00'],
        'Sept jours ouverts, ordre français et format des heures.');

    $jours[7]->setFerme(true);
    $em->persist((new CreneauHoraire())->setHoraireJour($jours[2])
        ->setHeureOuverture(new DateTimeImmutable('14:00'))->setHeureFermeture(new DateTimeImmutable('18:00')));
    $em->flush();
    $catalogue = page('/menus', 'multiple');
    verifier(substr_count($catalogue, 'menu-card--catalogue') === 6, 'Six menus sont réellement rendus.');
    $multiple = lireFooter($catalogue);
    verifier($multiple['Dimanche'] === 'Fermé', 'Fermeture prioritaire même si un ancien créneau existe.');
    verifier($multiple['Mardi'] === '09h00–12h00 / 14h00–18h00', 'Deux créneaux triés et séparés par une barre oblique.');
    page('/login', 'login');
    page('/menus', 'fragment', true);

    $c = $em->getConnection();
    $c->executeStatement('DELETE FROM creneau_horaire WHERE horaire_jour_id IN (?, ?)', [$jours[1]->getId(), $jours[3]->getId()]);
    $c->executeStatement('DELETE FROM horaire_jour WHERE id = ?', [$jours[3]->getId()]);
    $partiel = lireFooter(page('/menus', 'partiel'));
    verifier($partiel['Lundi'] === 'Horaires non renseignés' && $partiel['Mercredi'] === 'Horaires non renseignés',
        'Jour ouvert sans créneau et jour absent ne sont pas présentés comme fermés.');
    echo "OK : sept jours, fermeture, créneaux multiples triés, données vides et partielles.\n";
    echo "OK : footer sur accueil, connexion et catalogue de six menus ; une requête jointe par page, y compris la réponse AJAX complète, et mémoïsation par requête.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e::class . ($e instanceof RuntimeException ? ': ' . $e->getMessage() : '') . PHP_EOL);
    $status = 1;
} finally {
    $em?->getConnection()->close();
    $kernel->shutdown();
    unlink($file);
}
exit($status);
