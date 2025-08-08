<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250808073728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE notification ADD related_event_id INT DEFAULT NULL, ADD related_user_id INT DEFAULT NULL, ADD priority VARCHAR(10) NOT NULL, ADD category VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAD774A626 FOREIGN KEY (related_event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA98771930 FOREIGN KEY (related_user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_BF5476CAD774A626 ON notification (related_event_id)');
        $this->addSql('CREATE INDEX IDX_BF5476CA98771930 ON notification (related_user_id)');
        $this->addSql('CREATE INDEX idx_type_created ON notification (type, created_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAD774A626');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA98771930');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('DROP INDEX IDX_BF5476CAD774A626 ON notification');
        $this->addSql('DROP INDEX IDX_BF5476CA98771930 ON notification');
        $this->addSql('DROP INDEX idx_type_created ON notification');
        $this->addSql('ALTER TABLE notification DROP related_event_id, DROP related_user_id, DROP priority, DROP category');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
