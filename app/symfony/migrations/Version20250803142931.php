<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250803142931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE shop (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, address_id INT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, phone VARCHAR(50) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, siret_number VARCHAR(100) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, primary_color VARCHAR(7) DEFAULT NULL, opening_hours JSON DEFAULT NULL, services JSON DEFAULT NULL, specialized_games JSON DEFAULT NULL, scraping_data JSON DEFAULT NULL, verification_data JSON DEFAULT NULL, confidence_score INT DEFAULT NULL, stats JSON DEFAULT NULL, config JSON DEFAULT NULL, is_active TINYINT(1) NOT NULL, is_featured TINYINT(1) NOT NULL, display_order INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', claimed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_AC6A4CA2989D9B62 (slug), UNIQUE INDEX UNIQ_AC6A4CA27E3C61F9 (owner_id), INDEX IDX_AC6A4CA2F5B7AF75 (address_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE shop ADD CONSTRAINT FK_AC6A4CA27E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE shop ADD CONSTRAINT FK_AC6A4CA2F5B7AF75 FOREIGN KEY (address_id) REFERENCES addresses (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop DROP FOREIGN KEY FK_AC6A4CA27E3C61F9');
        $this->addSql('ALTER TABLE shop DROP FOREIGN KEY FK_AC6A4CA2F5B7AF75');
        $this->addSql('DROP TABLE shop');
    }
}
