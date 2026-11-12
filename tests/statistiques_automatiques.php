<?php

// PHP avec extension mongodb ; PostgreSQL et MongoDB locaux.
// Schéma PostgreSQL et base MongoDB aléatoires, supprimés en fin de test.

use App\Command\StatsSyncCommand;
use App\Entity\Commande;
use App\Entity\Menu;
use App\Entity\Regime;
use App\Entity\Theme;
use App\Entity\Utilisateur;
use App\Kernel;
use App\Service\AcceptationCommande;
use App\Service\ActualisationStatistiquesCommande;
use App\Service\AnnulationCommande;
use App\Service\AnnulationCommandeEquipe;
use App\Service\CalculFraisLivraison;
use App\Service\CalculPrixCommande;
use App\Service\EnregistrementCommande;
use App\Service\ModificationCommande;
use App\Service\ModificationCommandeEquipe;
use App\Service\PretMaterielCommande;
use App\Service\RetourMaterielCommande;
use App\Service\SuiviCommande;
use App\Service\SynchronisationStatistiques;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Tools\SchemaTool;
use MongoDB\Client;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/migrations/Version20261112000000.php';

function verifier(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function brouillon(Menu $menu, Utilisateur $client, CalculPrixCommande $prix, ?string $jeton = null): Commande
{
    return (new Commande())->setMenu($menu)->setUtilisateur($client)
        ->setJetonValidation($jeton ?? bin2hex(random_bytes(16)))
        ->setNombrePersonnes(10)->setDatePrestation(new DateTimeImmutable('+30 days', new DateTimeZone('Europe/Paris')))
        ->setHeureLivraison(new DateTimeImmutable('12:00'))->setAdresseLivraison('Adresse fictive')
        ->setCodePostalLivraison('33000')->setVilleLivraison('Bordeaux')->setTelephoneContact('0000000000')
        ->setMontantMenuCentimes($prix->calculerMontantMenu($menu, 10))
        ->setMontantRemiseCentimes($prix->calculerRemise($menu, 10))->setFraisLivraisonCentimes(500)
        ->setMontantTotalCentimes($prix->calculerTotal($menu, 10, 500));
}

function confirmation(Commande $commande, Utilisateur $auteur, ModificationCommande $modification, CalculPrixCommande $prix, int $personnes): array
{
    return [
        'commande_id' => $commande->getId(), 'menu_id' => $commande->getMenu()->getId(),
        'utilisateur_id' => $commande->getUtilisateur()->getId(), 'auteur_id' => $auteur->getId(),
        'expire_a' => time() + 1800, 'empreinte_source' => $modification->calculerEmpreinte($commande),
        'mode_contact' => 'telephone', 'motif' => 'Modification de test', 'client_contacte' => true,
        'donnees' => [
            'nombre_personnes' => $personnes, 'date_prestation' => $commande->getDatePrestation()->format('Y-m-d'),
            'heure_livraison' => '12:00', 'adresse_livraison' => $commande->getAdresseLivraison(),
            'code_postal_livraison' => '33000', 'ville_livraison' => 'Bordeaux', 'telephone_contact' => '0000000000',
        ],
        'recapitulatif' => [
            'montant_menu' => $prix->calculerMontantMenu($commande->getMenu(), $personnes),
            'remise' => $prix->calculerRemise($commande->getMenu(), $personnes), 'frais_livraison' => 500,
            'total' => $prix->calculerTotal($commande->getMenu(), $personnes, 500),
        ],
    ];
}

// Ne jamais exécuter les tests sur Atlas, même si le projet y est configuré.
$uri = 'mongodb://127.0.0.1:27017';
$_SERVER['MONGODB_URI'] = $_ENV['MONGODB_URI'] = $uri;
putenv('MONGODB_URI=' . $uri);
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
verifier(extension_loaded('mongodb'), 'Extension mongodb requise.');
$suffix = bin2hex(random_bytes(8));
$schema = 'vg_stats_auto_test_' . $suffix;
$mongoDatabase = 'vg_stats_auto_test_' . $suffix;
$kernel = new Kernel('dev', true);
$connection = null;
$ancienneLecture = null;
$schemaCree = false;
$mongo = new Client($uri, ['serverSelectionTimeoutMS' => 3000], [
    'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
]);

try {
    $kernel->boot();
    $em = $kernel->getContainer()->get('doctrine')->getManager();
    $connection = $em->getConnection();
    verifier($connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'PostgreSQL requis.');
    $connection->executeStatement('CREATE SCHEMA ' . $schema);
    $schemaCree = true;
    $connection->executeStatement('SET search_path TO ' . $schema);
    (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());

    // Tester la vraie migration à partir du schéma précédent.
    $connection->executeStatement('ALTER TABLE commande DROP version_statistiques');
    $migration = new DoctrineMigrations\Version20261112000000($connection, new NullLogger());
    $migration->up(new Schema());
    foreach ($migration->getSql() as $query) {
        $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
    }

    $collection = $mongo->selectCollection($mongoDatabase, 'commandes');
    $sync = new SynchronisationStatistiques($connection, $uri, $mongoDatabase);
    $logger = new class extends AbstractLogger {
        public array $records = [];
        public function log($level, string|Stringable $message, array $context = []): void
        {
            $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
        }
    };
    $actualisation = new ActualisationStatistiquesCommande($sync, $logger);
    $prix = new CalculPrixCommande();
    $enregistrement = new EnregistrementCommande($em, $prix, $actualisation);
    $acceptation = new AcceptationCommande($em, $actualisation);
    $suivi = new SuiviCommande($em, $actualisation);
    $annulation = new AnnulationCommande($em, $actualisation);
    $annulationEquipe = new AnnulationCommandeEquipe($em, $actualisation);
    $pret = new PretMaterielCommande($em, $actualisation);
    $retour = new RetourMaterielCommande($em, $actualisation);
    $modification = new ModificationCommande($em, $prix, new CalculFraisLivraison(), $actualisation);
    $modificationEquipe = new ModificationCommandeEquipe($em, $modification, $prix, new CalculFraisLivraison(), $actualisation);
    $client = (new Utilisateur())->setEmail('client@example.test')->setPassword('hachage-test')->setRoles(['ROLE_CLIENT']);
    $employe = (new Utilisateur())->setEmail('employe@example.test')->setPassword('hachage-test')->setRoles(['ROLE_EMPLOYE']);
    $theme = (new Theme())->setLibelle('Test');
    $regime = (new Regime())->setLibelle('Test');
    $menu = (new Menu())->setNom('$Menu de test')->setDescription('Test')->setConditions('Test')
        ->setTheme($theme)->setRegime($regime)->setMinimumPersonnes(10)->setPrixMinimumCentimes(10000)
        ->setDelaiMinimumJours(0)->setStockCommandes(100);
    $autreMenu = (new Menu())->setNom('Autre menu')->setDescription('Test')->setConditions('Test')
        ->setTheme($theme)->setRegime($regime)->setMinimumPersonnes(10)->setPrixMinimumCentimes(20000)
        ->setDelaiMinimumJours(0)->setStockCommandes(100);
    foreach ([$client, $employe, $theme, $regime, $menu, $autreMenu] as $entity) {
        $em->persist($entity);
    }
    $em->flush();

    $commande = $enregistrement->enregistrer(brouillon($menu, $client, $prix));
    $id = $commande->getId();
    verifier(!$connection->isTransactionActive() && $collection->findOne(['_id' => $id])['statut'] === 'en_attente',
        'La création doit synchroniser après le commit.');
    verifier($collection->findOne(['_id' => $id])['menu']['nom'] === '$Menu de test', 'Le nom du menu doit être traité littéralement.');
    $renvoi = $enregistrement->enregistrer(brouillon($menu, $client, $prix, $commande->getJetonValidation()));
    verifier($renvoi->getId() === $id && $collection->countDocuments() === 1, 'Un renvoi doit conserver le même document.');
    echo "OK : création après commit et renvoi sans doublon.\n";

    // Un rattrapage a lu un ancien snapshot avant deux actions employé successives.
    $ancienneLecture = DriverManager::getConnection($connection->getParams());
    $ancienneLecture->executeStatement('SET search_path TO ' . $schema);
    $ancienneLecture->beginTransaction();
    $ancienneLecture->executeStatement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
    $ancienneLecture->fetchOne('SELECT version_statistiques FROM commande WHERE id = ?', [$id]);
    $acceptation->accepter($commande, $employe);
    $suivi->changerStatut($commande, $employe, 'en_preparation');
    $recent = $collection->findOne(['_id' => $id]);
    (new SynchronisationStatistiques($ancienneLecture, $uri, $mongoDatabase))->synchroniser();
    verifier(serialize($collection->findOne(['_id' => $id])) === serialize($recent) && $recent['version_source'] === 3,
        'Un ancien snapshot ne doit pas écraser un état récent.');
    $ancienneLecture->rollBack();
    $ancienneLecture->close();
    $actualisation->apresValidation($id);
    verifier($collection->countDocuments() === 1 && serialize($collection->findOne(['_id' => $id])) === serialize($recent),
        'Une réexécution doit rester idempotente.');
    foreach (['en_livraison', 'livree', 'terminee'] as $statut) {
        $suivi->changerStatut($commande, $employe, $statut);
    }
    verifier($collection->findOne(['_id' => $id])['statut'] === 'terminee', 'Le passage à terminée doit actualiser MongoDB.');
    echo "OK : changements successifs, terminée et rejet atomique d’un ancien état lu avant les changements.\n";

    $materiel = $enregistrement->enregistrer(brouillon($menu, $client, $prix));
    $acceptation->accepter($materiel, $employe);
    $pret->definir($materiel, $employe, true);
    foreach (['en_preparation', 'en_livraison', 'livree', 'attente_retour_materiel'] as $statut) {
        $suivi->changerStatut($materiel, $employe, $statut);
    }
    $retour->confirmer($materiel, $employe);
    verifier($collection->findOne(['_id' => $materiel->getId()])['statut'] === 'terminee', 'Le retour de matériel doit synchroniser la fin de commande.');

    $annuleeClient = $enregistrement->enregistrer(brouillon($menu, $client, $prix));
    $annulation->annuler($annuleeClient, $client);
    $avantRenvoi = $collection->findOne(['_id' => $annuleeClient->getId()]);
    verifier(!$annulation->annuler($annuleeClient, $client)
        && serialize($collection->findOne(['_id' => $annuleeClient->getId()])) === serialize($avantRenvoi)
        && $avantRenvoi['statut'] === 'annulee', 'L’annulation client et son renvoi doivent être idempotents.');
    $annuleeEquipe = $enregistrement->enregistrer(brouillon($menu, $client, $prix));
    $acceptation->accepter($annuleeEquipe, $employe);
    $annulationEquipe->annuler($annuleeEquipe, $employe, 'telephone', 'Annulation de test', true);
    verifier($collection->findOne(['_id' => $annuleeEquipe->getId()])['statut'] === 'annulee', 'L’annulation équipe doit synchroniser.');
    echo "OK : fin avec retour de matériel, annulations client et équipe, renvoi d’annulation.\n";

    $modifiee = $enregistrement->enregistrer(brouillon($menu, $client, $prix));
    $modification->modifier($modifiee, $client, confirmation($modifiee, $client, $modification, $prix, 16));
    verifier($collection->findOne(['_id' => $modifiee->getId()])['montant_remise_centimes'] === 1600, 'Les montants modifiés par le client doivent être synchronisés.');
    $acceptation->accepter($modifiee, $employe);
    $modificationEquipe->modifier($modifiee, $employe, confirmation($modifiee, $employe, $modification, $prix, 17));
    verifier($collection->findOne(['_id' => $modifiee->getId()])['montant_menu_centimes'] === 17000, 'Les montants modifiés par l’équipe doivent être synchronisés.');
    $connection->transactional(function () use ($connection, $modifiee, $autreMenu): void {
        $connection->executeStatement('UPDATE commande SET menu_id = ?, montant_menu_centimes = 34000, montant_remise_centimes = 3400, montant_total_centimes = 31100 WHERE id = ?', [$autreMenu->getId(), $modifiee->getId()]);
    });
    $actualisation->apresValidation($modifiee->getId());
    verifier($collection->findOne(['_id' => $modifiee->getId()])['menu']['id'] === $autreMenu->getId(),
        'Relire PostgreSQL doit aussi prendre le menu actualisé, sans utiliser l’ancienne entité ORM.');
    echo "OK : modifications client/équipe des montants et relecture du menu courant.\n";

    $connection->beginTransaction();
    $connection->executeStatement("UPDATE commande SET statut = 'annulee' WHERE id = ?", [$id]);
    $actualisation->apresValidation($id);
    verifier($collection->findOne(['_id' => $id])['statut'] === 'terminee', 'Aucune écriture avant le commit extérieur.');
    $connection->rollBack();

    $offline = new ActualisationStatistiquesCommande(new SynchronisationStatistiques(
        $connection, 'mongodb://identifiant:secret-test@127.0.0.1:1', $mongoDatabase
    ), $logger);
    $pendante = (new EnregistrementCommande($em, $prix, $offline))->enregistrer(brouillon($menu, $client, $prix));
    (new AcceptationCommande($em, $offline))->accepter($pendante, $employe);
    (new AnnulationCommandeEquipe($em, $offline))->annuler($pendante, $employe, 'telephone', 'Test panne', true);
    verifier($connection->fetchOne('SELECT statut FROM commande WHERE id = ?', [$pendante->getId()]) === 'annulee'
        && $collection->findOne(['_id' => $pendante->getId()]) === null, 'La panne MongoDB ne doit pas annuler les actions SQL.');
    $logs = json_encode($logger->records, JSON_THROW_ON_ERROR);
    verifier(count($logger->records) === 4 && !str_contains($logs, 'secret-test')
        && !str_contains($logs, 'mongodb://'), 'Les journaux ne doivent contenir aucun secret.');
    foreach ($logger->records as $record) {
        verifier(array_keys($record['context']) === ['commande_id', 'type_erreur'],
            'Le contexte des journaux doit être limité à l’identifiant et à la classe de l’erreur.');
    }
    $rattrapage = new CommandTester(new StatsSyncCommand($sync));
    verifier($rattrapage->execute([]) === 0 && $collection->findOne(['_id' => $pendante->getId()])['statut'] === 'annulee'
        && $collection->countDocuments() === (int) $connection->fetchOne('SELECT COUNT(*) FROM commande'),
        'app:stats:sync doit rattraper les écritures manquées.');
    echo "OK : panne lors de création/acceptation/annulation, SQL conservé, journaux sans secret et rattrapage CLI.\n";

    // Échec au COMMIT, après le flush : aucune projection ne doit avoir eu lieu.
    $connection->executeStatement(<<<'SQL'
        CREATE FUNCTION refuser_commit_test() RETURNS trigger AS $$
        BEGIN RAISE EXCEPTION 'Commit de test refusé'; END;
        $$ LANGUAGE plpgsql
        SQL);
    $connection->executeStatement("CREATE CONSTRAINT TRIGGER refuser_commit_test AFTER INSERT ON commande DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN (NEW.telephone_contact = 'refus_commit') EXECUTE FUNCTION refuser_commit_test()");
    $nombreAvant = $collection->countDocuments();
    $rejetee = brouillon($menu, $client, $prix)->setTelephoneContact('refus_commit');
    $rejet = false;
    try {
        $enregistrement->enregistrer($rejetee);
    } catch (Doctrine\DBAL\Exception) {
        $rejet = true;
    }
    verifier($rejet && $collection->countDocuments() === $nombreAvant
        && (int) $connection->fetchOne('SELECT COUNT(*) FROM commande') === $nombreAvant,
        'Un commit refusé ne doit laisser ni commande SQL ni projection MongoDB.');
    echo "OK : un commit PostgreSQL refusé après flush ne déclenche aucune écriture MongoDB.\n";
} finally {
    if ($ancienneLecture?->isTransactionActive()) {
        $ancienneLecture->rollBack();
    }
    $ancienneLecture?->close();
    if ($connection?->isTransactionActive()) {
        $connection->rollBack();
    }
    if ($schemaCree) {
        $connection->executeStatement('SET search_path TO public');
        $connection->executeStatement('DROP SCHEMA ' . $schema . ' CASCADE');
    }
    $mongo->dropDatabase($mongoDatabase);
    $connection?->close();
    $kernel->shutdown();
}
