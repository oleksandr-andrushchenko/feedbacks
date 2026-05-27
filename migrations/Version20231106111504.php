<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231106111504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications ADD feedback_id VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications ADD CONSTRAINT FK_BAA4CA76D249A887 FOREIGN KEY (feedback_id) REFERENCES feedbacks (id)');
        $this->addSql('CREATE INDEX IDX_BAA4CA76D249A887 ON feedback_search_user_telegram_notifications (feedback_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications DROP FOREIGN KEY FK_BAA4CA76D249A887');
        $this->addSql('DROP INDEX IDX_BAA4CA76D249A887 ON feedback_search_user_telegram_notifications');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications DROP feedback_id');
    }
}
