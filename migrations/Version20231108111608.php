<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231108111608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback_telegram_notifications (id VARCHAR(32) NOT NULL, messenger_user_id VARCHAR(32) NOT NULL, feedback_search_term_id INT UNSIGNED NOT NULL, feedback_id VARCHAR(32) NOT NULL, target_feedback_id VARCHAR(32) NOT NULL, telegram_bot_id SMALLINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BA31FDF49E4CEF7D (messenger_user_id), INDEX IDX_BA31FDF4C2ED3DD8 (feedback_search_term_id), INDEX IDX_BA31FDF4D249A887 (feedback_id), INDEX IDX_BA31FDF45573E78D (target_feedback_id), INDEX IDX_BA31FDF4A0E2F38 (telegram_bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback_telegram_notifications ADD CONSTRAINT FK_BA31FDF49E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedback_telegram_notifications ADD CONSTRAINT FK_BA31FDF4C2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('ALTER TABLE feedback_telegram_notifications ADD CONSTRAINT FK_BA31FDF4D249A887 FOREIGN KEY (feedback_id) REFERENCES feedbacks (id)');
        $this->addSql('ALTER TABLE feedback_telegram_notifications ADD CONSTRAINT FK_BA31FDF45573E78D FOREIGN KEY (target_feedback_id) REFERENCES feedbacks (id)');
        $this->addSql('ALTER TABLE feedback_telegram_notifications ADD CONSTRAINT FK_BA31FDF4A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_telegram_notifications DROP FOREIGN KEY FK_BA31FDF49E4CEF7D');
        $this->addSql('ALTER TABLE feedback_telegram_notifications DROP FOREIGN KEY FK_BA31FDF4C2ED3DD8');
        $this->addSql('ALTER TABLE feedback_telegram_notifications DROP FOREIGN KEY FK_BA31FDF4D249A887');
        $this->addSql('ALTER TABLE feedback_telegram_notifications DROP FOREIGN KEY FK_BA31FDF45573E78D');
        $this->addSql('ALTER TABLE feedback_telegram_notifications DROP FOREIGN KEY FK_BA31FDF4A0E2F38');
        $this->addSql('DROP TABLE feedback_telegram_notifications');
    }
}
