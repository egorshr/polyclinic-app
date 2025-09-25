<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250604133811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE visit_services (visit_id INT NOT NULL, service_id INT NOT NULL, INDEX IDX_76643F7E75FA0FF2 (visit_id), INDEX IDX_76643F7EED5CA9E6 (service_id), PRIMARY KEY(visit_id, service_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE visit_services ADD CONSTRAINT FK_76643F7E75FA0FF2 FOREIGN KEY (visit_id) REFERENCES visits (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE visit_services ADD CONSTRAINT FK_76643F7EED5CA9E6 FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE visit_services DROP FOREIGN KEY FK_76643F7E75FA0FF2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE visit_services DROP FOREIGN KEY FK_76643F7EED5CA9E6
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE visit_services
        SQL);
    }
}
