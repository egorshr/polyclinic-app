<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250602150530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE discounts (discount_id INT AUTO_INCREMENT NOT NULL, discount_percent SMALLINT NOT NULL, PRIMARY KEY(discount_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE employees (id INT AUTO_INCREMENT NOT NULL, speciality_id INT NOT NULL, user_id INT NOT NULL, first_name VARCHAR(32) NOT NULL, middle_name VARCHAR(36) DEFAULT NULL, last_name VARCHAR(64) NOT NULL, birthday DATE NOT NULL COMMENT '(DC2Type:date_immutable)', gender VARCHAR(10) NOT NULL, phone_number VARCHAR(18) NOT NULL, duration_of_visit TIME DEFAULT NULL COMMENT '(DC2Type:time_immutable)', INDEX IDX_BA82C3003B5A08D7 (speciality_id), UNIQUE INDEX UNIQ_BA82C300A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE patients (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, gender VARCHAR(10) NOT NULL, first_name VARCHAR(32) NOT NULL, middle_name VARCHAR(36) DEFAULT NULL, last_name VARCHAR(64) NOT NULL, birthday DATE NOT NULL COMMENT '(DC2Type:date_immutable)', phone_number VARCHAR(18) NOT NULL, passport_series VARCHAR(45) NOT NULL, passport_number VARCHAR(45) NOT NULL, passport_issue_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', passport_issued_by VARCHAR(45) NOT NULL, address_country VARCHAR(45) NOT NULL, address_region VARCHAR(45) NOT NULL, address_locality VARCHAR(45) NOT NULL, address_street VARCHAR(45) NOT NULL, address_house INT NOT NULL, address_body VARCHAR(10) DEFAULT NULL, address_apartment INT DEFAULT NULL, UNIQUE INDEX UNIQ_2CCC2E2CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE schedules (id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', time_from TIME NOT NULL COMMENT '(DC2Type:time_immutable)', time_to TIME NOT NULL COMMENT '(DC2Type:time_immutable)', INDEX IDX_313BDC8E8C03F15C (employee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE services (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, price NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE social_statuses (id INT AUTO_INCREMENT NOT NULL, discount_id INT NOT NULL, description VARCHAR(100) NOT NULL, INDEX IDX_C5C18A3D4C7C611F (discount_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE specialties (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(36) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE users (user_id INT AUTO_INCREMENT NOT NULL, username VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, password_hash VARCHAR(255) NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) NOT NULL, role VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE visits (id INT AUTO_INCREMENT NOT NULL, discount_id INT DEFAULT NULL, patient_id INT NOT NULL, employee_id INT NOT NULL, visit_date_and_time DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', status VARCHAR(10) NOT NULL, INDEX IDX_444839EA4C7C611F (discount_id), INDEX IDX_444839EA6B899279 (patient_id), INDEX IDX_444839EA8C03F15C (employee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employees ADD CONSTRAINT FK_BA82C3003B5A08D7 FOREIGN KEY (speciality_id) REFERENCES specialties (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employees ADD CONSTRAINT FK_BA82C300A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE patients ADD CONSTRAINT FK_2CCC2E2CA76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE schedules ADD CONSTRAINT FK_313BDC8E8C03F15C FOREIGN KEY (employee_id) REFERENCES employees (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE social_statuses ADD CONSTRAINT FK_C5C18A3D4C7C611F FOREIGN KEY (discount_id) REFERENCES discounts (discount_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE visits ADD CONSTRAINT FK_444839EA4C7C611F FOREIGN KEY (discount_id) REFERENCES discounts (discount_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE visits ADD CONSTRAINT FK_444839EA6B899279 FOREIGN KEY (patient_id) REFERENCES patients (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE visits ADD CONSTRAINT FK_444839EA8C03F15C FOREIGN KEY (employee_id) REFERENCES employees (id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE photographer
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE service
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE booking
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE photographer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_16337A7F5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_E19D9AD25E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, service VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, photographer VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, user_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, password_hash VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, role VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at VARCHAR(19) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employees DROP FOREIGN KEY FK_BA82C3003B5A08D7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employees DROP FOREIGN KEY FK_BA82C300A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE patients DROP FOREIGN KEY FK_2CCC2E2CA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE schedules DROP FOREIGN KEY FK_313BDC8E8C03F15C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE social_statuses DROP FOREIGN KEY FK_C5C18A3D4C7C611F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE visits DROP FOREIGN KEY FK_444839EA4C7C611F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE visits DROP FOREIGN KEY FK_444839EA6B899279
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE visits DROP FOREIGN KEY FK_444839EA8C03F15C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE discounts
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE employees
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE patients
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE schedules
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE services
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE social_statuses
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE specialties
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE users
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE visits
        SQL);
    }
}
