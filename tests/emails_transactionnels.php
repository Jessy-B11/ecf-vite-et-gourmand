<?php

// Exécution : php tests/emails_transactionnels.php
// Aucun envoi réel : boîte en mémoire et API HTTPS simulée, avec SQLite temporaire pour le reset.

use App\Entity\Commande;
use App\Entity\ResetPasswordRequest;
use App\Entity\Utilisateur;
use App\Kernel;
use App\Service\EmailBienvenue;
use App\Service\EmailConfirmationCommande;
use App\Service\EmailInvitationAvis;
use App\Service\EmailRetourMateriel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\HttpClient\HttpClientInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

function verifier(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

final class BoiteEmailsTest implements MailerInterface
{
    /** @var list<Email> */
    public array $emails = [];

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        verifier($message instanceof Email, 'Un e-mail structuré est attendu.');
        $this->emails[] = clone $message;
    }
}

function requeteReset(array &$cookies, BoiteEmailsTest $boite, string $method, array $data = []): Response
{
    $kernel = new Kernel('test', true);
    $kernel->boot();
    $kernel->getContainer()->get('test.service_container')->set('mailer.mailer', $boite);
    $request = Request::create('http://localhost/reset-password', $method, $data, $cookies, [], [
        'HTTP_ORIGIN' => 'http://localhost',
    ]);
    try {
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        return $response;
    } finally {
        $kernel->shutdown();
    }
}

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['MAILER_FROM_EMAIL'] = $_ENV['MAILER_FROM_EMAIL'] = 'expediteur-test@example.test';
$_SERVER['MAILER_DSN'] = $_ENV['MAILER_DSN'] = 'null://null';
putenv('MAILER_FROM_EMAIL=expediteur-test@example.test');
putenv('MAILER_DSN=null://null');
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
$database = tempnam(sys_get_temp_dir(), 'vg-mails-');
verifier($database !== false, 'Impossible de créer la base temporaire.');
$databaseUrl = 'sqlite:///' . str_replace('\\', '/', $database);
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $databaseUrl;
putenv('DATABASE_URL=' . $databaseUrl);
$kernel = new Kernel('test', true);
$entityManager = null;

try {
    $kernel->boot();
    $container = $kernel->getContainer()->get('test.service_container');
    $boite = new BoiteEmailsTest();
    $container->set('mailer.mailer', $boite);
    $entityManager = $container->get(EntityManagerInterface::class);
    $connection = $entityManager->getConnection();
    verifier($connection->getParams()['driver'] === 'pdo_sqlite'
        && str_replace('\\', '/', $connection->getParams()['path']) === str_replace('\\', '/', $database),
        'La base de travail ne doit pas être utilisée par ce test.');
    (new SchemaTool($entityManager))->createSchema([
        $entityManager->getClassMetadata(Utilisateur::class),
        $entityManager->getClassMetadata(ResetPasswordRequest::class),
    ]);
    $client = (new Utilisateur())->setEmail('destinataire@example.test')->setPrenom('Camille')
        ->setNom('Test')->setPassword('hachage-fictif')->setRoles(['ROLE_CLIENT']);
    $entityManager->persist($client);
    $entityManager->flush();
    $commande = (new Commande())->setUtilisateur($client);
    (new ReflectionProperty(Commande::class, 'id'))->setValue($commande, 42);

    $container->get(EmailBienvenue::class)->envoyer($client);
    $container->get(EmailConfirmationCommande::class)->envoyer($commande);
    $container->get(EmailInvitationAvis::class)->envoyer($commande);
    $commande->setMaterielPrete(true)->setStatut('attente_retour_materiel');
    $container->get(EmailRetourMateriel::class)->envoyer($commande);
    verifier(count($boite->emails) === 4, 'Les quatre services doivent transmettre leur e-mail.');

    $cookies = [];
    $form = requeteReset($cookies, $boite, 'GET');
    verifier($form->getStatusCode() === 200, 'Le formulaire de réinitialisation doit être accessible.');
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML($form->getContent());
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $token = (new DOMXPath($document))->evaluate('string(//input[@name="reset_password_request_form[_token]"]/@value)');
    $response = requeteReset($cookies, $boite, 'POST', [
        'reset_password_request_form' => ['email' => $client->getEmail(), '_token' => $token],
    ]);
    verifier($response->isRedirect('/reset-password/check-email') && count($boite->emails) === 5,
        'Le parcours de réinitialisation doit transmettre son e-mail et rediriger.');
    foreach ($boite->emails as $email) {
        $from = $email->getFrom();
        verifier(count($from) === 1 && $from[0]->getAddress() === 'expediteur-test@example.test'
            && $from[0]->getName() === 'Vite & Gourmand', 'Les cinq expéditeurs doivent utiliser MAILER_FROM_EMAIL et le nom de marque.');
    }
    verifier(str_contains($boite->emails[4]->getSubject(), 'Réinitialisation'), 'Le cinquième message doit être celui du reset.');
    echo "OK : bienvenue, confirmation, avis, retour matériel et reset utilisent l’expéditeur configuré.\n";

    verifier(HttpClient::create() instanceof HttpClientInterface, 'Le client HTTP Symfony doit être disponible.');
    $requests = 0;
    $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
        ++$requests;
        verifier($method === 'POST' && $url === 'https://api.brevo.com/v3/smtp/email', 'Le transport doit utiliser l’API HTTPS de Brevo.');
        verifier(($options['normalized_headers']['api-key'][0] ?? '') === 'api-key: cle-api-fictive', 'La clé du DSN doit être transmise à l’API.');
        $payload = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);
        verifier($payload['sender'] === ['email' => 'expediteur-test@example.test', 'name' => 'Vite & Gourmand']
            && $payload['to'][0]['email'] === 'destinataire@example.test', 'La requête API doit conserver l’expéditeur et le destinataire.');

        return new MockResponse('{"messageId":"<test@example.test>"}', ['http_code' => 201]);
    });
    $transport = (new BrevoTransportFactory(client: $http))->create(Dsn::fromString('brevo+api://cle-api-fictive@default'));
    verifier($transport instanceof BrevoApiTransport, 'Le DSN doit sélectionner le transport Brevo API.');
    $transport->send($boite->emails[0]);
    verifier($requests === 1, 'L’envoi doit appeler le client HTTP immédiatement.');
    verifier(Transport::fromDsn('smtp://127.0.0.1:1025') instanceof EsmtpTransport, 'Le DSN local doit conserver le transport SMTP Mailpit.');
    echo "OK : transport Brevo HTTPS, requête API simulée, client HTTP disponible et transport Mailpit conservé.\n";
} finally {
    $entityManager?->getConnection()->close();
    $kernel->shutdown();
    gc_collect_cycles();
    unlink($database);
}
