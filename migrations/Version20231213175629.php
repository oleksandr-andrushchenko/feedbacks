<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231213175629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_notifications ADD feedback_user_subscription_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_notifications ADD CONSTRAINT FK_749D6E7813B132F1 FOREIGN KEY (feedback_user_subscription_id) REFERENCES feedback_user_subscriptions (id)');
        $this->addSql('CREATE INDEX IDX_749D6E7813B132F1 ON feedback_notifications (feedback_user_subscription_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_notifications DROP FOREIGN KEY FK_749D6E7813B132F1');
        $this->addSql('DROP INDEX IDX_749D6E7813B132F1 ON feedback_notifications');
        $this->addSql('ALTER TABLE feedback_notifications DROP feedback_user_subscription_id');
    }
}
