<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250603050945 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE patients CHANGE gender gender VARCHAR(10) DEFAULT NULL, CHANGE first_name first_name VARCHAR(32) DEFAULT NULL, CHANGE last_name last_name VARCHAR(64) DEFAULT NULL, CHANGE birthday birthday DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)', CHANGE phone_number phone_number VARCHAR(18) DEFAULT NULL, CHANGE passport_series passport_series VARCHAR(45) DEFAULT NULL, CHANGE passport_number passport_number VARCHAR(45) DEFAULT NULL, CHANGE passport_issue_date passport_issue_date DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)', CHANGE passport_issued_by passport_issued_by VARCHAR(150) DEFAULT NULL, CHANGE address_country address_country VARCHAR(45) DEFAULT NULL, CHANGE address_region address_region VARCHAR(100) DEFAULT NULL, CHANGE address_locality address_locality VARCHAR(100) DEFAULT NULL, CHANGE address_street address_street VARCHAR(150) DEFAULT NULL, CHANGE address_house address_house VARCHAR(20) DEFAULT NULL, CHANGE address_body address_body VARCHAR(20) DEFAULT NULL, CHANGE address_apartment address_apartment VARCHAR(20) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE patients CHANGE first_name first_name VARCHAR(32) NOT NULL, CHANGE last_name last_name VARCHAR(64) NOT NULL, CHANGE gender gender VARCHAR(10) NOT NULL, CHANGE birthday birthday DATE NOT NULL COMMENT '(DC2Type:date_immutable)', CHANGE phone_number phone_number VARCHAR(18) NOT NULL, CHANGE passport_series passport_series VARCHAR(45) NOT NULL, CHANGE passport_number passport_number VARCHAR(45) NOT NULL, CHANGE passport_issue_date passport_issue_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', CHANGE passport_issued_by passport_issued_by VARCHAR(45) NOT NULL, CHANGE address_country address_country VARCHAR(45) NOT NULL, CHANGE address_region address_region VARCHAR(45) NOT NULL, CHANGE address_locality address_locality VARCHAR(45) NOT NULL, CHANGE address_street address_street VARCHAR(45) NOT NULL, CHANGE address_house address_house INT NOT NULL, CHANGE address_body address_body VARCHAR(10) DEFAULT NULL, CHANGE address_apartment address_apartment INT DEFAULT NULL
        SQL);
    }
}
