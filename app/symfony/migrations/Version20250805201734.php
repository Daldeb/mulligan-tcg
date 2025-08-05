<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250805201734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE deck_like (id INT AUTO_INCREMENT NOT NULL, deck_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_9084048D111948DC (deck_id), INDEX IDX_9084048DA76ED395 (user_id), UNIQUE INDEX deck_user_unique (deck_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE deck_like ADD CONSTRAINT FK_9084048D111948DC FOREIGN KEY (deck_id) REFERENCES deck (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE deck_like ADD CONSTRAINT FK_9084048DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE deck ADD likes_count INT NOT NULL, ADD last_liked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deck_like DROP FOREIGN KEY FK_9084048D111948DC');
        $this->addSql('ALTER TABLE deck_like DROP FOREIGN KEY FK_9084048DA76ED395');
        $this->addSql('DROP TABLE deck_like');
        $this->addSql('ALTER TABLE deck DROP likes_count, DROP last_liked_at');
    }
}
