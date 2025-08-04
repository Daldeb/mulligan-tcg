<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250804090620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE magic_card (id INT AUTO_INCREMENT NOT NULL, magic_set_id INT NOT NULL, oracle_id VARCHAR(255) NOT NULL, scryfall_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, printed_name VARCHAR(255) DEFAULT NULL, lang VARCHAR(255) DEFAULT NULL, mana_cost VARCHAR(100) DEFAULT NULL, cmc DOUBLE PRECISION DEFAULT NULL, type_line VARCHAR(255) DEFAULT NULL, printed_type_line VARCHAR(255) DEFAULT NULL, oracle_text LONGTEXT DEFAULT NULL, printed_text LONGTEXT DEFAULT NULL, flavor_text LONGTEXT DEFAULT NULL, power VARCHAR(10) DEFAULT NULL, toughness VARCHAR(10) DEFAULT NULL, colors JSON DEFAULT NULL, color_identity JSON DEFAULT NULL, keywords JSON DEFAULT NULL, produced_mana JSON DEFAULT NULL, rarity VARCHAR(50) NOT NULL, is_standard_legal TINYINT(1) NOT NULL, is_commander_legal TINYINT(1) NOT NULL, image_url VARCHAR(500) DEFAULT NULL, image_url_large VARCHAR(500) DEFAULT NULL, artist VARCHAR(255) DEFAULT NULL, artist_ids JSON DEFAULT NULL, illustration_id VARCHAR(255) DEFAULT NULL, layout VARCHAR(50) DEFAULT NULL, frame VARCHAR(50) DEFAULT NULL, frame_effects JSON DEFAULT NULL, border_color VARCHAR(50) DEFAULT NULL, security_stamp VARCHAR(50) DEFAULT NULL, watermark VARCHAR(50) DEFAULT NULL, is_promo TINYINT(1) NOT NULL, is_reprint TINYINT(1) NOT NULL, is_reserved TINYINT(1) NOT NULL, is_full_art TINYINT(1) NOT NULL, is_textless TINYINT(1) NOT NULL, is_booster TINYINT(1) NOT NULL, is_digital TINYINT(1) NOT NULL, games JSON DEFAULT NULL, finishes JSON DEFAULT NULL, multiverse_ids JSON DEFAULT NULL, mtgo_id INT DEFAULT NULL, arena_id INT DEFAULT NULL, tcgplayer_id INT DEFAULT NULL, cardmarket_id INT DEFAULT NULL, edhrec_rank INT DEFAULT NULL, penny_rank INT DEFAULT NULL, released_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_synced_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_4BF8B0E4E13D77CC (oracle_id), UNIQUE INDEX UNIQ_4BF8B0E4C4213070 (scryfall_id), INDEX IDX_4BF8B0E4B549583A (magic_set_id), INDEX idx_magic_card_oracle_id (oracle_id), INDEX idx_magic_card_scryfall_id (scryfall_id), INDEX idx_magic_card_standard_legal (is_standard_legal), INDEX idx_magic_card_commander_legal (is_commander_legal), INDEX idx_magic_card_rarity (rarity), INDEX idx_magic_card_cmc (cmc), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE magic_set (id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, scryfall_id VARCHAR(255) NOT NULL, set_code VARCHAR(10) NOT NULL, name VARCHAR(255) NOT NULL, set_type VARCHAR(50) NOT NULL, set_uri VARCHAR(500) DEFAULT NULL, scryfall_uri VARCHAR(500) DEFAULT NULL, uri VARCHAR(500) DEFAULT NULL, search_uri VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_synced_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_62271026C4213070 (scryfall_id), UNIQUE INDEX UNIQ_62271026E2D2D211 (set_code), INDEX IDX_62271026E48FD905 (game_id), INDEX idx_magic_set_code (set_code), INDEX idx_magic_set_scryfall_id (scryfall_id), INDEX idx_magic_set_type (set_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE magic_card ADD CONSTRAINT FK_4BF8B0E4B549583A FOREIGN KEY (magic_set_id) REFERENCES magic_set (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE magic_set ADD CONSTRAINT FK_62271026E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE magic_card DROP FOREIGN KEY FK_4BF8B0E4B549583A');
        $this->addSql('ALTER TABLE magic_set DROP FOREIGN KEY FK_62271026E48FD905');
        $this->addSql('DROP TABLE magic_card');
        $this->addSql('DROP TABLE magic_set');
    }
}
