<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Révision croissante des commandes pour empêcher une synchronisation MongoDB obsolète.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'PostgreSQL requis.');
        $this->addSql('ALTER TABLE commande ADD version_statistiques BIGINT DEFAULT 1 NOT NULL');
        $this->addSql(<<<'SQL'
            CREATE FUNCTION incrementer_version_statistiques_commande() RETURNS trigger AS $$
            BEGIN
                NEW.version_statistiques := OLD.version_statistiques + 1;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
            SQL);
        $this->addSql('CREATE TRIGGER commande_version_statistiques BEFORE UPDATE ON commande FOR EACH ROW EXECUTE FUNCTION incrementer_version_statistiques_commande()');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TRIGGER commande_version_statistiques ON commande');
        $this->addSql('DROP FUNCTION incrementer_version_statistiques_commande()');
        $this->addSql('ALTER TABLE commande DROP version_statistiques');
    }
}
