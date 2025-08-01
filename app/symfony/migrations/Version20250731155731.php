<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250731155731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pokemon_card (id INT AUTO_INCREMENT NOT NULL, pokemon_set_id INT NOT NULL, external_id VARCHAR(100) NOT NULL, local_id VARCHAR(10) NOT NULL, name VARCHAR(200) NOT NULL, image_url VARCHAR(500) NOT NULL, illustrator VARCHAR(150) DEFAULT NULL, rarity VARCHAR(100) DEFAULT NULL, types JSON DEFAULT NULL, hp INT DEFAULT NULL, is_standard_legal TINYINT(1) NOT NULL, is_expanded_legal TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_synced_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_2ABDE6909F75D7B0 (external_id), INDEX IDX_2ABDE6907C12714C (pokemon_set_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pokemon_set (id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, external_id VARCHAR(50) NOT NULL, name VARCHAR(200) NOT NULL, logo_url VARCHAR(500) DEFAULT NULL, symbol_url VARCHAR(500) DEFAULT NULL, total_cards INT NOT NULL, official_cards INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_4C8EB7F89F75D7B0 (external_id), INDEX IDX_4C8EB7F8E48FD905 (game_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pokemon_card ADD CONSTRAINT FK_2ABDE6907C12714C FOREIGN KEY (pokemon_set_id) REFERENCES pokemon_set (id)');
        $this->addSql('ALTER TABLE pokemon_set ADD CONSTRAINT FK_4C8EB7F8E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pokemon_card DROP FOREIGN KEY FK_2ABDE6907C12714C');
        $this->addSql('ALTER TABLE pokemon_set DROP FOREIGN KEY FK_4C8EB7F8E48FD905');
        $this->addSql('DROP TABLE pokemon_card');
        $this->addSql('DROP TABLE pokemon_set');
    }
}
