<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250801082338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE hearthstone_card (id INT AUTO_INCREMENT NOT NULL, hearthstone_set_id INT NOT NULL, external_id VARCHAR(100) NOT NULL, dbf_id INT NOT NULL, name VARCHAR(200) NOT NULL, image_url VARCHAR(500) NOT NULL, artist VARCHAR(150) DEFAULT NULL, cost INT DEFAULT NULL, attack INT DEFAULT NULL, health INT DEFAULT NULL, card_class VARCHAR(50) NOT NULL, card_type VARCHAR(50) NOT NULL, rarity VARCHAR(50) DEFAULT NULL, text LONGTEXT DEFAULT NULL, flavor LONGTEXT DEFAULT NULL, mechanics JSON DEFAULT NULL, faction VARCHAR(50) DEFAULT NULL, is_standard_legal TINYINT(1) NOT NULL, is_wild_legal TINYINT(1) NOT NULL, is_collectible TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_synced_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_662860429F75D7B0 (external_id), INDEX IDX_66286042CBC7BC51 (hearthstone_set_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE hearthstone_set (id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, external_id VARCHAR(50) NOT NULL, name VARCHAR(200) NOT NULL, logo_url VARCHAR(500) DEFAULT NULL, symbol_url VARCHAR(500) DEFAULT NULL, total_cards INT NOT NULL, is_standard_legal TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_B0183BD99F75D7B0 (external_id), INDEX IDX_B0183BD9E48FD905 (game_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE hearthstone_card ADD CONSTRAINT FK_66286042CBC7BC51 FOREIGN KEY (hearthstone_set_id) REFERENCES hearthstone_set (id)');
        $this->addSql('ALTER TABLE hearthstone_set ADD CONSTRAINT FK_B0183BD9E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE hearthstone_card DROP FOREIGN KEY FK_66286042CBC7BC51');
        $this->addSql('ALTER TABLE hearthstone_set DROP FOREIGN KEY FK_B0183BD9E48FD905');
        $this->addSql('DROP TABLE hearthstone_card');
        $this->addSql('DROP TABLE hearthstone_set');
    }
}
