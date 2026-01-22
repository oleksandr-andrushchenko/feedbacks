<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251223165537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_lookups CHANGE has_active_subscription has_active_subscription TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_notifications DROP FOREIGN KEY FK_749D6E78C2ED3DD8');
        $this->addSql('DROP INDEX IDX_749D6E78C2ED3DD8 ON feedback_notifications');
        $this->addSql('ALTER TABLE feedback_notifications CHANGE feedback_search_term_id search_term_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_notifications ADD CONSTRAINT FK_749D6E7883FDDA66 FOREIGN KEY (search_term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('CREATE INDEX IDX_749D6E7883FDDA66 ON feedback_notifications (search_term_id)');
        $this->addSql('ALTER TABLE feedback_searches CHANGE has_active_subscription has_active_subscription TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE feedbacks CHANGE has_active_subscription has_active_subscription TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE telegram_payment_methods DROP FOREIGN KEY FK_CDACB4B492C1C487');
        $this->addSql('DROP INDEX IDX_CDACB4B492C1C487 ON telegram_payment_methods');
        $this->addSql('ALTER TABLE telegram_payment_methods CHANGE bot_id telegram_bot_id SMALLINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE telegram_payment_methods ADD CONSTRAINT FK_CDACB4B4A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('CREATE INDEX IDX_CDACB4B4A0E2F38 ON telegram_payment_methods (telegram_bot_id)');
        $this->addSql('ALTER TABLE telegram_payments DROP FOREIGN KEY FK_31578A7A19883967');
        $this->addSql('ALTER TABLE telegram_payments DROP FOREIGN KEY FK_31578A7A92C1C487');
        $this->addSql('DROP INDEX IDX_31578A7A19883967 ON telegram_payments');
        $this->addSql('DROP INDEX IDX_31578A7A92C1C487 ON telegram_payments');
        $this->addSql('ALTER TABLE telegram_payments CHANGE method_id telegram_bot_payment_method_id SMALLINT UNSIGNED DEFAULT NULL, CHANGE bot_id telegram_bot_id SMALLINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE telegram_payments ADD CONSTRAINT FK_31578A7A33B1B36F FOREIGN KEY (telegram_bot_payment_method_id) REFERENCES telegram_payment_methods (id)');
        $this->addSql('ALTER TABLE telegram_payments ADD CONSTRAINT FK_31578A7AA0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('CREATE INDEX IDX_31578A7A33B1B36F ON telegram_payments (telegram_bot_payment_method_id)');
        $this->addSql('CREATE INDEX IDX_31578A7AA0E2F38 ON telegram_payments (telegram_bot_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_lookups CHANGE has_active_subscription has_active_subscription TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE feedback_notifications DROP FOREIGN KEY FK_749D6E7883FDDA66');
        $this->addSql('DROP INDEX IDX_749D6E7883FDDA66 ON feedback_notifications');
        $this->addSql('ALTER TABLE feedback_notifications CHANGE search_term_id feedback_search_term_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_notifications ADD CONSTRAINT FK_749D6E78C2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_749D6E78C2ED3DD8 ON feedback_notifications (feedback_search_term_id)');
        $this->addSql('ALTER TABLE feedback_searches CHANGE has_active_subscription has_active_subscription TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE feedbacks CHANGE has_active_subscription has_active_subscription TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE telegram_payment_methods DROP FOREIGN KEY FK_CDACB4B4A0E2F38');
        $this->addSql('DROP INDEX IDX_CDACB4B4A0E2F38 ON telegram_payment_methods');
        $this->addSql('ALTER TABLE telegram_payment_methods CHANGE telegram_bot_id bot_id SMALLINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE telegram_payment_methods ADD CONSTRAINT FK_CDACB4B492C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_CDACB4B492C1C487 ON telegram_payment_methods (bot_id)');
        $this->addSql('ALTER TABLE telegram_payments DROP FOREIGN KEY FK_31578A7A33B1B36F');
        $this->addSql('ALTER TABLE telegram_payments DROP FOREIGN KEY FK_31578A7AA0E2F38');
        $this->addSql('DROP INDEX IDX_31578A7A33B1B36F ON telegram_payments');
        $this->addSql('DROP INDEX IDX_31578A7AA0E2F38 ON telegram_payments');
        $this->addSql('ALTER TABLE telegram_payments CHANGE telegram_bot_payment_method_id method_id SMALLINT UNSIGNED DEFAULT NULL, CHANGE telegram_bot_id bot_id SMALLINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE telegram_payments ADD CONSTRAINT FK_31578A7A19883967 FOREIGN KEY (method_id) REFERENCES telegram_payment_methods (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE telegram_payments ADD CONSTRAINT FK_31578A7A92C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_31578A7A19883967 ON telegram_payments (method_id)');
        $this->addSql('CREATE INDEX IDX_31578A7A92C1C487 ON telegram_payments (bot_id)');
    }
}
