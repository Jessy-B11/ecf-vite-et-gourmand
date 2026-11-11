<?php

namespace App\Command;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:demo:clients',
    description: 'Crée huit clients fictifs sans modifier les comptes existants.'
)]
final class DemoClientsCommand extends Command
{
    // Identités fictives et adresses de lieux publics fournies pour la démonstration.
    private const CLIENTS = [
        ['Camille', 'Martin', '85 cours du Maréchal Juin', '33000', 'Bordeaux'],
        ['Alexis', 'Leroux', '5 place Bardineau', '33000', 'Bordeaux'],
        ['Noémie', 'Rivière', '4 rue Raymond Poincaré', '33100', 'Bordeaux'],
        ['Hugo', 'Morel', '2 rue Fieffé', '33800', 'Bordeaux'],
        ['Léa', 'Dubois', '19 place Charles de Gaulle', '33700', 'Mérignac'],
        ['Émile', 'Bernard', '21 rue de Camponac', '33600', 'Pessac'],
        ['Inès', 'Petit', '1 allée Peixotto', '33400', 'Talence'],
        ['Sacha', 'Laurent', '58 avenue du Maréchal de Lattre de Tassigny', '33130', 'Bègles'],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $userPasswordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Une confirmation interactive est obligatoire avant toute écriture. '
            . 'Les adresses existantes sont ignorées, sans modification de leur profil ou mot de passe. '
            . 'Les identifiants des nouveaux comptes sont affichés uniquement dans le terminal, après création.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $database = $this->entityManager->getConnection()->getDatabase();
        $io->text(sprintf('Base ciblée : %s', $database ?? '(indéterminée)'));

        if ($database === null || !$input->isInteractive()) {
            $io->error('Une base identifiée et une confirmation interactive sont requises. Aucun compte créé.');

            return Command::FAILURE;
        }

        if (!$io->confirm(sprintf('Créer les clients de démonstration dans la base « %s » ?', $database), false)) {
            $io->note('Opération annulée. Aucun compte modifié.');

            return Command::SUCCESS;
        }

        // Ne divulguer aucun mot de passe tant que la transaction n’est pas validée.
        [$credentials, $ignored] = $this->entityManager->wrapInTransaction(function (): array {
            $credentials = [];
            $ignored = [];
            $passwords = [];
            $repository = $this->entityManager->getRepository(Utilisateur::class);

            foreach (self::CLIENTS as $index => [$prenom, $nom, $adresse, $codePostal, $ville]) {
                $number = $index + 1;
                $email = sprintf('client%02d.demo@example.com', $number);

                if ($repository->findOneBy(['email' => $email]) !== null) {
                    $ignored[] = $email;

                    continue;
                }

                do {
                    // 128 bits aléatoires, avec toutes les catégories exigées à l’inscription.
                    $plainPassword = bin2hex(random_bytes(16)) . 'aA1!';
                } while (in_array($plainPassword, $passwords, true));

                $passwords[] = $plainPassword;
                $client = (new Utilisateur())
                    ->setPrenom($prenom)
                    ->setNom($nom)
                    ->setEmail($email)
                    ->setTelephone(sprintf('00000000%02d', $number))
                    ->setAdresse($adresse)
                    ->setCodePostal($codePostal)
                    ->setVille($ville)
                    ->setRoles(['ROLE_CLIENT'])
                    ->setActif(true);
                $client->setPassword(
                    $this->userPasswordHasher->hashPassword($client, $plainPassword)
                );

                $this->entityManager->persist($client);
                $credentials[] = [$email, $plainPassword];
            }

            return [$credentials, $ignored];
        });

        foreach ($ignored as $email) {
            $io->text(sprintf('Ignoré : %s (compte existant conservé).', $email));
        }

        $io->success(sprintf('%d compte(s) créé(s), %d compte(s) ignoré(s).', count($credentials), count($ignored)));

        if ($credentials !== []) {
            $io->section('Identifiants des comptes créés pendant cette exécution');
            $io->table(['Adresse email', 'Mot de passe initial'], $credentials);
        }

        return Command::SUCCESS;
    }
}
