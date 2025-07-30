<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250730113653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE role_request ADD shop_address_id INT DEFAULT NULL, DROP shop_address');
        $this->addSql('ALTER TABLE role_request ADD CONSTRAINT FK_875A2A648FC44253 FOREIGN KEY (shop_address_id) REFERENCES addresses (id)');
        $this->addSql('CREATE INDEX IDX_875A2A648FC44253 ON role_request (shop_address_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE role_request DROP FOREIGN KEY FK_875A2A648FC44253');
        $this->addSql('DROP INDEX IDX_875A2A648FC44253 ON role_request');
        $this->addSql('ALTER TABLE role_request ADD shop_address VARCHAR(255) DEFAULT NULL, DROP shop_address_id');
    }
}
