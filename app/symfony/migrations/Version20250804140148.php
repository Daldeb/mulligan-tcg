<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250804140148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deck_card ADD magic_card_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE deck_card ADD CONSTRAINT FK_2AF3DCED9F096991 FOREIGN KEY (magic_card_id) REFERENCES magic_card (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_2AF3DCED9F096991 ON deck_card (magic_card_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE deck_card DROP FOREIGN KEY FK_2AF3DCED9F096991');
        $this->addSql('DROP INDEX IDX_2AF3DCED9F096991 ON deck_card');
        $this->addSql('ALTER TABLE deck_card DROP magic_card_id');
    }
}
