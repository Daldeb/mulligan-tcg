<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250801110052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, parent_id INT DEFAULT NULL, author_id INT NOT NULL, content LONGTEXT NOT NULL, score INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, INDEX IDX_9474526C4B89032C (post_id), INDEX IDX_9474526C727ACA70 (parent_id), INDEX IDX_9474526CF675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comment_vote (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, comment_id INT NOT NULL, type VARCHAR(4) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_7C262788A76ED395 (user_id), INDEX IDX_7C262788F8697D13 (comment_id), UNIQUE INDEX UNIQ_USER_COMMENT (user_id, comment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forum (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, is_official TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_852BBECD989D9B62 (slug), UNIQUE INDEX UNIQ_FORUM_NAME (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, forum_id INT DEFAULT NULL, title VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, content LONGTEXT NOT NULL, score INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, is_pinned TINYINT(1) NOT NULL, is_locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_5A8A6C8D989D9B62 (slug), INDEX IDX_5A8A6C8DF675F31B (author_id), INDEX IDX_5A8A6C8D29CCBAD0 (forum_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post_vote (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, post_id INT NOT NULL, type VARCHAR(4) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_9345E26FA76ED395 (user_id), INDEX IDX_9345E26F4B89032C (post_id), UNIQUE INDEX UNIQ_USER_POST (user_id, post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C727ACA70 FOREIGN KEY (parent_id) REFERENCES comment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment_vote ADD CONSTRAINT FK_7C262788A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment_vote ADD CONSTRAINT FK_7C262788F8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DF675F31B FOREIGN KEY (author_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D29CCBAD0 FOREIGN KEY (forum_id) REFERENCES forum (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE post_vote ADD CONSTRAINT FK_9345E26FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_vote ADD CONSTRAINT FK_9345E26F4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C4B89032C');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C727ACA70');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CF675F31B');
        $this->addSql('ALTER TABLE comment_vote DROP FOREIGN KEY FK_7C262788A76ED395');
        $this->addSql('ALTER TABLE comment_vote DROP FOREIGN KEY FK_7C262788F8697D13');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DF675F31B');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D29CCBAD0');
        $this->addSql('ALTER TABLE post_vote DROP FOREIGN KEY FK_9345E26FA76ED395');
        $this->addSql('ALTER TABLE post_vote DROP FOREIGN KEY FK_9345E26F4B89032C');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE comment_vote');
        $this->addSql('DROP TABLE forum');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE post_vote');
    }
}
