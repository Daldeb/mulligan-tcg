<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250803213935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, address_id INT DEFAULT NULL, created_by_id INT NOT NULL, reviewed_by_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, event_type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, visibility VARCHAR(20) NOT NULL, start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', end_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', registration_deadline DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', max_participants INT DEFAULT NULL, current_participants INT NOT NULL, is_online TINYINT(1) NOT NULL, organizer_type VARCHAR(20) NOT NULL, organizer_id INT NOT NULL, tags JSON DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, entry_fee NUMERIC(10, 2) DEFAULT NULL, rules LONGTEXT DEFAULT NULL, prizes LONGTEXT DEFAULT NULL, stream_url VARCHAR(255) DEFAULT NULL, additional_data JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', reviewed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', review_comment LONGTEXT DEFAULT NULL, discriminator_type VARCHAR(255) NOT NULL, INDEX IDX_3BAE0AA7F5B7AF75 (address_id), INDEX IDX_3BAE0AA7B03A8386 (created_by_id), INDEX IDX_3BAE0AA7FC6B21F1 (reviewed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_games (event_id INT NOT NULL, game_id INT NOT NULL, INDEX IDX_BE389A1D71F7E88B (event_id), INDEX IDX_BE389A1DE48FD905 (game_id), PRIMARY KEY(event_id, game_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_registration (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, user_id INT NOT NULL, deck_id INT DEFAULT NULL, status VARCHAR(20) NOT NULL, registered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', confirmed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', cancelled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', notes LONGTEXT DEFAULT NULL, additional_data JSON DEFAULT NULL, deck_list LONGTEXT DEFAULT NULL, deck_list_submitted TINYINT(1) NOT NULL, deck_list_submitted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', checked_in TINYINT(1) NOT NULL, checked_in_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', seed_number INT DEFAULT NULL, final_ranking INT DEFAULT NULL, tournament_stats JSON DEFAULT NULL, INDEX IDX_8FBBAD5471F7E88B (event_id), INDEX IDX_8FBBAD54A76ED395 (user_id), INDEX IDX_8FBBAD54111948DC (deck_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament (id INT NOT NULL, game_format_id INT NOT NULL, tournament_format VARCHAR(20) NOT NULL, current_phase VARCHAR(20) NOT NULL, swiss_rounds INT DEFAULT NULL, current_round INT NOT NULL, top_cut_size INT DEFAULT NULL, match_timer INT NOT NULL, break_timer INT DEFAULT NULL, bracket_data JSON DEFAULT NULL, standings JSON DEFAULT NULL, pairings JSON DEFAULT NULL, prize_pool NUMERIC(10, 2) DEFAULT NULL, prize_distribution JSON DEFAULT NULL, allow_decklist TINYINT(1) NOT NULL, require_decklist TINYINT(1) NOT NULL, started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', finished_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', tournament_config JSON DEFAULT NULL, INDEX IDX_BD5FB8D948F3707 (game_format_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament_match (id INT AUTO_INCREMENT NOT NULL, tournament_id INT NOT NULL, round_id INT NOT NULL, player1_id INT NOT NULL, player2_id INT DEFAULT NULL, winner_id INT DEFAULT NULL, status VARCHAR(20) NOT NULL, table_number INT DEFAULT NULL, player1_score INT DEFAULT NULL, player2_score INT DEFAULT NULL, game_results JSON DEFAULT NULL, started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', finished_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', duration INT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, additional_data JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BB0D551C33D1A3E7 (tournament_id), INDEX IDX_BB0D551CA6005CA0 (round_id), INDEX IDX_BB0D551CC0990423 (player1_id), INDEX IDX_BB0D551CD22CABCD (player2_id), INDEX IDX_BB0D551C5DFCD4B8 (winner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament_round (id INT AUTO_INCREMENT NOT NULL, tournament_id INT NOT NULL, round_number INT NOT NULL, round_type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', finished_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', time_limit INT DEFAULT NULL, pairings_generated TINYINT(1) NOT NULL, pairings_generated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', all_matches_finished TINYINT(1) NOT NULL, pairings_data JSON DEFAULT NULL, standings JSON DEFAULT NULL, notes LONGTEXT DEFAULT NULL, additional_data JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4B87A2D33D1A3E7 (tournament_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7F5B7AF75 FOREIGN KEY (address_id) REFERENCES addresses (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7FC6B21F1 FOREIGN KEY (reviewed_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE event_games ADD CONSTRAINT FK_BE389A1D71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_games ADD CONSTRAINT FK_BE389A1DE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_registration ADD CONSTRAINT FK_8FBBAD5471F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_registration ADD CONSTRAINT FK_8FBBAD54A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_registration ADD CONSTRAINT FK_8FBBAD54111948DC FOREIGN KEY (deck_id) REFERENCES deck (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE tournament ADD CONSTRAINT FK_BD5FB8D948F3707 FOREIGN KEY (game_format_id) REFERENCES game_format (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE tournament ADD CONSTRAINT FK_BD5FB8D9BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551C33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551CA6005CA0 FOREIGN KEY (round_id) REFERENCES tournament_round (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551CC0990423 FOREIGN KEY (player1_id) REFERENCES event_registration (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551CD22CABCD FOREIGN KEY (player2_id) REFERENCES event_registration (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_match ADD CONSTRAINT FK_BB0D551C5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES event_registration (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE tournament_round ADD CONSTRAINT FK_4B87A2D33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7F5B7AF75');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7B03A8386');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7FC6B21F1');
        $this->addSql('ALTER TABLE event_games DROP FOREIGN KEY FK_BE389A1D71F7E88B');
        $this->addSql('ALTER TABLE event_games DROP FOREIGN KEY FK_BE389A1DE48FD905');
        $this->addSql('ALTER TABLE event_registration DROP FOREIGN KEY FK_8FBBAD5471F7E88B');
        $this->addSql('ALTER TABLE event_registration DROP FOREIGN KEY FK_8FBBAD54A76ED395');
        $this->addSql('ALTER TABLE event_registration DROP FOREIGN KEY FK_8FBBAD54111948DC');
        $this->addSql('ALTER TABLE tournament DROP FOREIGN KEY FK_BD5FB8D948F3707');
        $this->addSql('ALTER TABLE tournament DROP FOREIGN KEY FK_BD5FB8D9BF396750');
        $this->addSql('ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551C33D1A3E7');
        $this->addSql('ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551CA6005CA0');
        $this->addSql('ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551CC0990423');
        $this->addSql('ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551CD22CABCD');
        $this->addSql('ALTER TABLE tournament_match DROP FOREIGN KEY FK_BB0D551C5DFCD4B8');
        $this->addSql('ALTER TABLE tournament_round DROP FOREIGN KEY FK_4B87A2D33D1A3E7');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_games');
        $this->addSql('DROP TABLE event_registration');
        $this->addSql('DROP TABLE tournament');
        $this->addSql('DROP TABLE tournament_match');
        $this->addSql('DROP TABLE tournament_round');
    }
}
