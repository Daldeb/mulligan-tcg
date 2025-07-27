<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250727133155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD pseudo VARCHAR(50) NOT NULL, ADD is_verified TINYINT(1) NOT NULL, ADD verification_token VARCHAR(255) DEFAULT NULL, ADD verification_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD reset_password_token VARCHAR(255) DEFAULT NULL, ADD reset_password_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD first_name VARCHAR(100) DEFAULT NULL, ADD last_name VARCHAR(100) DEFAULT NULL, ADD bio LONGTEXT DEFAULT NULL, ADD avatar VARCHAR(255) DEFAULT NULL, ADD favorite_class VARCHAR(100) DEFAULT NULL, ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD last_login_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64986CC499D ON user (pseudo)');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_identifier_email TO UNIQ_8D93D649E7927C74');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D64986CC499D ON `user`');
        $this->addSql('ALTER TABLE `user` DROP pseudo, DROP is_verified, DROP verification_token, DROP verification_token_expires_at, DROP reset_password_token, DROP reset_password_token_expires_at, DROP first_name, DROP last_name, DROP bio, DROP avatar, DROP favorite_class, DROP created_at, DROP last_login_at');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_8d93d649e7927c74 TO UNIQ_IDENTIFIER_EMAIL');
    }
}
