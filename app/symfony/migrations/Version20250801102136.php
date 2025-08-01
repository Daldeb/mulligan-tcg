<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250801102136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE deck (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, game_id INT NOT NULL, game_format_id INT NOT NULL, title VARCHAR(200) NOT NULL, description LONGTEXT DEFAULT NULL, archetype VARCHAR(100) DEFAULT NULL, is_public TINYINT(1) NOT NULL, valid_deck TINYINT(1) NOT NULL, total_cards INT NOT NULL, average_cost DOUBLE PRECISION DEFAULT NULL, deckcode VARCHAR(1000) DEFAULT NULL, external_source VARCHAR(50) DEFAULT NULL, external_url VARCHAR(200) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4FAC3637A76ED395 (user_id), INDEX IDX_4FAC3637E48FD905 (game_id), INDEX IDX_4FAC363748F3707 (game_format_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE deck_card (id INT AUTO_INCREMENT NOT NULL, deck_id INT NOT NULL, hearthstone_card_id INT DEFAULT NULL, pokemon_card_id INT DEFAULT NULL, quantity INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2AF3DCED111948DC (deck_id), INDEX IDX_2AF3DCED45175FA5 (hearthstone_card_id), INDEX IDX_2AF3DCED26A6E6B1 (pokemon_card_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE deck ADD CONSTRAINT FK_4FAC3637A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE deck ADD CONSTRAINT FK_4FAC3637E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE deck ADD CONSTRAINT FK_4FAC363748F3707 FOREIGN KEY (game_format_id) REFERENCES game_format (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE deck_card ADD CONSTRAINT FK_2AF3DCED111948DC FOREIGN KEY (deck_id) REFERENCES deck (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE deck_card ADD CONSTRAINT FK_2AF3DCED45175FA5 FOREIGN KEY (hearthstone_card_id) REFERENCES hearthstone_card (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE deck_card ADD CONSTRAINT FK_2AF3DCED26A6E6B1 FOREIGN KEY (pokemon_card_id) REFERENCES pokemon_card (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deck DROP FOREIGN KEY FK_4FAC3637A76ED395');
        $this->addSql('ALTER TABLE deck DROP FOREIGN KEY FK_4FAC3637E48FD905');
        $this->addSql('ALTER TABLE deck DROP FOREIGN KEY FK_4FAC363748F3707');
        $this->addSql('ALTER TABLE deck_card DROP FOREIGN KEY FK_2AF3DCED111948DC');
        $this->addSql('ALTER TABLE deck_card DROP FOREIGN KEY FK_2AF3DCED45175FA5');
        $this->addSql('ALTER TABLE deck_card DROP FOREIGN KEY FK_2AF3DCED26A6E6B1');
        $this->addSql('DROP TABLE deck');
        $this->addSql('DROP TABLE deck_card');
    }
}
