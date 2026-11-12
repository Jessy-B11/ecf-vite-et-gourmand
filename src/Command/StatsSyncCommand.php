<?php

namespace App\Command;

use App\Service\SynchronisationStatistiques;
use Doctrine\DBAL\Exception as DbalException;
use MongoDB\Driver\Exception\Exception as DriverException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:stats:sync',
    description: 'Synchronise les commandes PostgreSQL vers les statistiques MongoDB.'
)]
final class StatsSyncCommand extends Command
{
    public function __construct(private SynchronisationStatistiques $synchronisation)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!extension_loaded('mongodb')) {
            $io->error('L’extension PHP mongodb est absente. Activez-la ou utilisez l’image Docker du projet.');

            return Command::FAILURE;
        }

        try {
            $counts = $this->synchronisation->synchroniser();
        } catch (DriverException) {
            // Ne pas afficher l’exception brute : elle peut contenir une URI avec des identifiants.
            $io->error(
                'Synchronisation impossible : MongoDB est inaccessible ou a refusé une opération. '
                . 'Vérifiez MONGODB_URI, MONGODB_DATABASE et les droits de lecture/écriture. '
                . 'Vous pouvez relancer la commande après correction.'
            );

            return Command::FAILURE;
        } catch (DbalException) {
            $io->error('Lecture des commandes PostgreSQL impossible. Vérifiez DATABASE_URL et la connexion.');

            return Command::FAILURE;
        }

        $io->success(sprintf(
            '%d commande(s) lue(s) : %d document(s) créé(s), %d modifié(s), %d inchangé(s).',
            $counts['lues'], $counts['creees'], $counts['modifiees'], $counts['inchangees']
        ));

        return Command::SUCCESS;
    }
}
