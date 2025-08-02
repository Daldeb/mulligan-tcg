<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250802192611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forum ADD game_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE forum ADD CONSTRAINT FK_852BBECDE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_852BBECDE48FD905 ON forum (game_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forum DROP FOREIGN KEY FK_852BBECDE48FD905');
        $this->addSql('DROP INDEX IDX_852BBECDE48FD905 ON forum');
        $this->addSql('ALTER TABLE forum DROP game_id');
    }
}
