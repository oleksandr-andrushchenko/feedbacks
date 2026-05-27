<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231106080806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback_search_user_telegram_notifications (id VARCHAR(32) NOT NULL, messenger_user_id VARCHAR(32) NOT NULL, feedback_search_term_id INT UNSIGNED NOT NULL, feedback_search_id VARCHAR(32) NOT NULL, telegram_bot_id SMALLINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BAA4CA769E4CEF7D (messenger_user_id), INDEX IDX_BAA4CA76C2ED3DD8 (feedback_search_term_id), INDEX IDX_BAA4CA76DB433E12 (feedback_search_id), INDEX IDX_BAA4CA76A0E2F38 (telegram_bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications ADD CONSTRAINT FK_BAA4CA769E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications ADD CONSTRAINT FK_BAA4CA76C2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications ADD CONSTRAINT FK_BAA4CA76DB433E12 FOREIGN KEY (feedback_search_id) REFERENCES feedback_searches (id)');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications ADD CONSTRAINT FK_BAA4CA76A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications DROP FOREIGN KEY FK_BAA4CA769E4CEF7D');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications DROP FOREIGN KEY FK_BAA4CA76C2ED3DD8');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications DROP FOREIGN KEY FK_BAA4CA76DB433E12');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications DROP FOREIGN KEY FK_BAA4CA76A0E2F38');
        $this->addSql('DROP TABLE feedback_search_user_telegram_notifications');
    }
}
