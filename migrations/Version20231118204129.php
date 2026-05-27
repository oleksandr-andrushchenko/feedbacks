<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231118204129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_user_subscriptions DROP FOREIGN KEY FK_F0E0BB8A4C3A3BB');
        $this->addSql('DROP INDEX UNIQ_F0E0BB8A4C3A3BB ON feedback_user_subscriptions');
        $this->addSql('ALTER TABLE feedback_user_subscriptions ADD user_id VARCHAR(32) NOT NULL, CHANGE payment_id telegram_payment_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_user_subscriptions ADD CONSTRAINT FK_F0E0BB8A56D312C1 FOREIGN KEY (telegram_payment_id) REFERENCES telegram_payments (id)');
        $this->addSql('ALTER TABLE feedback_user_subscriptions ADD CONSTRAINT FK_F0E0BB8AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F0E0BB8A56D312C1 ON feedback_user_subscriptions (telegram_payment_id)');
        $this->addSql('CREATE INDEX IDX_F0E0BB8AA76ED395 ON feedback_user_subscriptions (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_user_subscriptions DROP FOREIGN KEY FK_F0E0BB8A56D312C1');
        $this->addSql('ALTER TABLE feedback_user_subscriptions DROP FOREIGN KEY FK_F0E0BB8AA76ED395');
        $this->addSql('DROP INDEX UNIQ_F0E0BB8A56D312C1 ON feedback_user_subscriptions');
        $this->addSql('DROP INDEX IDX_F0E0BB8AA76ED395 ON feedback_user_subscriptions');
        $this->addSql('ALTER TABLE feedback_user_subscriptions DROP user_id, CHANGE telegram_payment_id payment_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_user_subscriptions ADD CONSTRAINT FK_F0E0BB8A4C3A3BB FOREIGN KEY (payment_id) REFERENCES telegram_payments (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F0E0BB8A4C3A3BB ON feedback_user_subscriptions (payment_id)');
    }
}
