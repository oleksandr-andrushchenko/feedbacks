<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231023190618 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback_search_searches (id VARCHAR(32) NOT NULL, user_id VARCHAR(32) DEFAULT NULL, messenger_user_id VARCHAR(32) DEFAULT NULL, search_term_id INT UNSIGNED DEFAULT NULL, telegram_bot_id SMALLINT UNSIGNED DEFAULT NULL, has_active_subscription TINYINT(1) NOT NULL, country_code VARCHAR(2) DEFAULT NULL, locale_code VARCHAR(2) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5CD71DC0A76ED395 (user_id), INDEX IDX_5CD71DC09E4CEF7D (messenger_user_id), INDEX IDX_5CD71DC083FDDA66 (search_term_id), INDEX IDX_5CD71DC0A0E2F38 (telegram_bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feedback_search_terms (id INT UNSIGNED AUTO_INCREMENT NOT NULL, messenger_user_id VARCHAR(32) DEFAULT NULL, text VARCHAR(255) NOT NULL, normalized_text VARCHAR(255) NOT NULL, type SMALLINT UNSIGNED NOT NULL, messenger SMALLINT UNSIGNED DEFAULT NULL, messenger_username VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_201518849E4CEF7D (messenger_user_id), INDEX IDX_20151884B334D8E9 (normalized_text), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feedback_searches (id VARCHAR(32) NOT NULL, user_id VARCHAR(32) DEFAULT NULL, messenger_user_id VARCHAR(32) DEFAULT NULL, search_term_id INT UNSIGNED DEFAULT NULL, telegram_bot_id SMALLINT UNSIGNED DEFAULT NULL, has_active_subscription TINYINT(1) NOT NULL, country_code VARCHAR(2) DEFAULT NULL, locale_code VARCHAR(2) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2F56936BA76ED395 (user_id), INDEX IDX_2F56936B9E4CEF7D (messenger_user_id), INDEX IDX_2F56936B83FDDA66 (search_term_id), INDEX IDX_2F56936BA0E2F38 (telegram_bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feedback_user_subscriptions (id VARCHAR(32) NOT NULL, payment_id VARCHAR(32) DEFAULT NULL, messenger_user_id VARCHAR(32) DEFAULT NULL, subscription_plan SMALLINT UNSIGNED NOT NULL, expire_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_F0E0BB8A4C3A3BB (payment_id), INDEX IDX_F0E0BB8A9E4CEF7D (messenger_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feedbacks (id VARCHAR(32) NOT NULL, user_id VARCHAR(32) DEFAULT NULL, messenger_user_id VARCHAR(32) DEFAULT NULL, telegram_bot_id SMALLINT UNSIGNED DEFAULT NULL, rating SMALLINT NOT NULL, description LONGTEXT DEFAULT NULL, has_active_subscription TINYINT(1) NOT NULL, country_code VARCHAR(2) DEFAULT NULL, locale_code VARCHAR(2) DEFAULT NULL, channel_message_ids LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7E6C3F89A76ED395 (user_id), INDEX IDX_7E6C3F899E4CEF7D (messenger_user_id), INDEX IDX_7E6C3F89A0E2F38 (telegram_bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feedbacks_feedback_search_terms (feedback_id VARCHAR(32) NOT NULL, feedback_search_term_id INT UNSIGNED NOT NULL, INDEX IDX_1725DC6AD249A887 (feedback_id), INDEX IDX_1725DC6AC2ED3DD8 (feedback_search_term_id), PRIMARY KEY(feedback_id, feedback_search_term_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE level_1_regions (id VARCHAR(32) NOT NULL, country_code VARCHAR(2) NOT NULL, name VARCHAR(128) NOT NULL, timezone VARCHAR(32) DEFAULT NULL, INDEX IDX_EA10FCA9F026BB7C5E237E06 (country_code, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_users (id VARCHAR(32) NOT NULL, user_id VARCHAR(32) DEFAULT NULL, messenger SMALLINT UNSIGNED NOT NULL, identifier VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, show_extended_keyboard TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2F5DF3DDA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_bots (id SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL, username VARCHAR(38) NOT NULL, name VARCHAR(1024) NOT NULL, token VARCHAR(50) NOT NULL, country_code VARCHAR(2) NOT NULL, locale_code VARCHAR(2) NOT NULL, _group SMALLINT UNSIGNED NOT NULL, check_updates TINYINT(1) DEFAULT 0 NOT NULL, check_requests TINYINT(1) DEFAULT 0 NOT NULL, accept_payments TINYINT(1) DEFAULT 0 NOT NULL, admin_ids LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', admin_only TINYINT(1) DEFAULT 1 NOT NULL, descriptions_synced TINYINT(1) DEFAULT 0 NOT NULL, webhook_synced TINYINT(1) DEFAULT 0 NOT NULL, commands_synced TINYINT(1) DEFAULT 0 NOT NULL, _primary TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_DACD6EDF85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_channels (id SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL, username VARCHAR(38) NOT NULL, _group SMALLINT UNSIGNED NOT NULL, name VARCHAR(1024) NOT NULL, country_code VARCHAR(2) NOT NULL, locale_code VARCHAR(2) NOT NULL, level_1_region_id VARCHAR(32) DEFAULT NULL, chat_id BIGINT DEFAULT NULL, _primary TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_A791F3FEF85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_conversations (id INT UNSIGNED AUTO_INCREMENT NOT NULL, hash VARCHAR(255) NOT NULL, messenger_user_id VARCHAR(32) NOT NULL, chat_id BIGINT NOT NULL, bot_id SMALLINT NOT NULL, class VARCHAR(255) NOT NULL, state JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7B5D79B8D1B862B8 (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_payment_methods (id SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL, bot_id SMALLINT UNSIGNED DEFAULT NULL, name SMALLINT UNSIGNED NOT NULL, token VARCHAR(64) NOT NULL, currency_codes LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_CDACB4B492C1C487 (bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_payments (id VARCHAR(32) NOT NULL, messenger_user_id VARCHAR(32) DEFAULT NULL, method_id SMALLINT UNSIGNED DEFAULT NULL, chat_id BIGINT NOT NULL, purpose VARCHAR(255) NOT NULL, price_amount NUMERIC(7, 2) NOT NULL, price_currency VARCHAR(255) NOT NULL, payload JSON NOT NULL, pre_checkout_query JSON DEFAULT NULL, successful_payment JSON DEFAULT NULL, status SMALLINT UNSIGNED DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_31578A7A9E4CEF7D (messenger_user_id), INDEX IDX_31578A7A19883967 (method_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_requests (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, bot_id SMALLINT UNSIGNED DEFAULT NULL, method VARCHAR(32) NOT NULL, chat_id VARCHAR(32) DEFAULT NULL, inline_message_id BIGINT DEFAULT NULL, data JSON NOT NULL, response JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2F00C71992C1C487 (bot_id), INDEX IDX_2F00C7198B8E84281A9A7125F86D5A67 (created_at, chat_id, inline_message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_stopped_conversations (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, messenger_user_id VARCHAR(32) NOT NULL, chat_id BIGINT NOT NULL, bot_id SMALLINT NOT NULL, class VARCHAR(255) NOT NULL, state JSON NOT NULL, started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_updates (id BIGINT UNSIGNED NOT NULL, bot_id SMALLINT UNSIGNED DEFAULT NULL, data JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1E0E72C692C1C487 (bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_contact_messages (id VARCHAR(32) NOT NULL, messenger_user_id VARCHAR(32) DEFAULT NULL, user_id VARCHAR(32) DEFAULT NULL, telegram_bot_id SMALLINT UNSIGNED DEFAULT NULL, text VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3C32C0DC9E4CEF7D (messenger_user_id), INDEX IDX_3C32C0DCA76ED395 (user_id), INDEX IDX_3C32C0DCA0E2F38 (telegram_bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id VARCHAR(32) NOT NULL, name VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, locale_code VARCHAR(2) DEFAULT NULL, country_code VARCHAR(2) DEFAULT NULL, location_latitude VARCHAR(20) DEFAULT NULL, location_longitude VARCHAR(20) DEFAULT NULL, level_1_region_id VARCHAR(32) DEFAULT NULL, currency_code VARCHAR(3) DEFAULT NULL, timezone VARCHAR(32) DEFAULT NULL, phone_number BIGINT UNSIGNED DEFAULT NULL, email VARCHAR(128) DEFAULT NULL, subscription_expire_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', purged_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC0A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC09E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC083FDDA66 FOREIGN KEY (search_term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC0A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('ALTER TABLE feedback_search_terms ADD CONSTRAINT FK_201518849E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936B9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936B83FDDA66 FOREIGN KEY (search_term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936BA0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('ALTER TABLE feedback_user_subscriptions ADD CONSTRAINT FK_F0E0BB8A4C3A3BB FOREIGN KEY (payment_id) REFERENCES telegram_payments (id)');
        $this->addSql('ALTER TABLE feedback_user_subscriptions ADD CONSTRAINT FK_F0E0BB8A9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedbacks ADD CONSTRAINT FK_7E6C3F89A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE feedbacks ADD CONSTRAINT FK_7E6C3F899E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedbacks ADD CONSTRAINT FK_7E6C3F89A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('ALTER TABLE feedbacks_feedback_search_terms ADD CONSTRAINT FK_1725DC6AD249A887 FOREIGN KEY (feedback_id) REFERENCES feedbacks (id)');
        $this->addSql('ALTER TABLE feedbacks_feedback_search_terms ADD CONSTRAINT FK_1725DC6AC2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('ALTER TABLE messenger_users ADD CONSTRAINT FK_2F5DF3DDA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE telegram_payment_methods ADD CONSTRAINT FK_CDACB4B492C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('ALTER TABLE telegram_payments ADD CONSTRAINT FK_31578A7A9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE telegram_payments ADD CONSTRAINT FK_31578A7A19883967 FOREIGN KEY (method_id) REFERENCES telegram_payment_methods (id)');
        $this->addSql('ALTER TABLE telegram_requests ADD CONSTRAINT FK_2F00C71992C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('ALTER TABLE telegram_updates ADD CONSTRAINT FK_1E0E72C692C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('ALTER TABLE user_contact_messages ADD CONSTRAINT FK_3C32C0DC9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE user_contact_messages ADD CONSTRAINT FK_3C32C0DCA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_contact_messages ADD CONSTRAINT FK_3C32C0DCA0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC0A76ED395');
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC09E4CEF7D');
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC083FDDA66');
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC0A0E2F38');
        $this->addSql('ALTER TABLE feedback_search_terms DROP FOREIGN KEY FK_201518849E4CEF7D');
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936BA76ED395');
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936B9E4CEF7D');
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936B83FDDA66');
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936BA0E2F38');
        $this->addSql('ALTER TABLE feedback_user_subscriptions DROP FOREIGN KEY FK_F0E0BB8A4C3A3BB');
        $this->addSql('ALTER TABLE feedback_user_subscriptions DROP FOREIGN KEY FK_F0E0BB8A9E4CEF7D');
        $this->addSql('ALTER TABLE feedbacks DROP FOREIGN KEY FK_7E6C3F89A76ED395');
        $this->addSql('ALTER TABLE feedbacks DROP FOREIGN KEY FK_7E6C3F899E4CEF7D');
        $this->addSql('ALTER TABLE feedbacks DROP FOREIGN KEY FK_7E6C3F89A0E2F38');
        $this->addSql('ALTER TABLE feedbacks_feedback_search_terms DROP FOREIGN KEY FK_1725DC6AD249A887');
        $this->addSql('ALTER TABLE feedbacks_feedback_search_terms DROP FOREIGN KEY FK_1725DC6AC2ED3DD8');
        $this->addSql('ALTER TABLE messenger_users DROP FOREIGN KEY FK_2F5DF3DDA76ED395');
        $this->addSql('ALTER TABLE telegram_payment_methods DROP FOREIGN KEY FK_CDACB4B492C1C487');
        $this->addSql('ALTER TABLE telegram_payments DROP FOREIGN KEY FK_31578A7A9E4CEF7D');
        $this->addSql('ALTER TABLE telegram_payments DROP FOREIGN KEY FK_31578A7A19883967');
        $this->addSql('ALTER TABLE telegram_requests DROP FOREIGN KEY FK_2F00C71992C1C487');
        $this->addSql('ALTER TABLE telegram_updates DROP FOREIGN KEY FK_1E0E72C692C1C487');
        $this->addSql('ALTER TABLE user_contact_messages DROP FOREIGN KEY FK_3C32C0DC9E4CEF7D');
        $this->addSql('ALTER TABLE user_contact_messages DROP FOREIGN KEY FK_3C32C0DCA76ED395');
        $this->addSql('ALTER TABLE user_contact_messages DROP FOREIGN KEY FK_3C32C0DCA0E2F38');
        $this->addSql('DROP TABLE feedback_search_searches');
        $this->addSql('DROP TABLE feedback_search_terms');
        $this->addSql('DROP TABLE feedback_searches');
        $this->addSql('DROP TABLE feedback_user_subscriptions');
        $this->addSql('DROP TABLE feedbacks');
        $this->addSql('DROP TABLE feedbacks_feedback_search_terms');
        $this->addSql('DROP TABLE level_1_regions');
        $this->addSql('DROP TABLE messenger_users');
        $this->addSql('DROP TABLE telegram_bots');
        $this->addSql('DROP TABLE telegram_channels');
        $this->addSql('DROP TABLE telegram_conversations');
        $this->addSql('DROP TABLE telegram_payment_methods');
        $this->addSql('DROP TABLE telegram_payments');
        $this->addSql('DROP TABLE telegram_requests');
        $this->addSql('DROP TABLE telegram_stopped_conversations');
        $this->addSql('DROP TABLE telegram_updates');
        $this->addSql('DROP TABLE user_contact_messages');
        $this->addSql('DROP TABLE users');
    }
}
