<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231110071116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback_notifications (id VARCHAR(32) NOT NULL, messenger_user_id VARCHAR(32) NOT NULL, feedback_search_term_id INT UNSIGNED NOT NULL, feedback_id VARCHAR(32) DEFAULT NULL, target_feedback_id VARCHAR(32) DEFAULT NULL, feedback_search_id VARCHAR(32) DEFAULT NULL, target_feedback_search_id VARCHAR(32) DEFAULT NULL, feedback_lookup_id VARCHAR(32) DEFAULT NULL, target_feedback_lookup_id VARCHAR(32) DEFAULT NULL, telegram_bot_id SMALLINT UNSIGNED DEFAULT NULL, type SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_749D6E789E4CEF7D (messenger_user_id), INDEX IDX_749D6E78C2ED3DD8 (feedback_search_term_id), INDEX IDX_749D6E78D249A887 (feedback_id), INDEX IDX_749D6E785573E78D (target_feedback_id), INDEX IDX_749D6E78DB433E12 (feedback_search_id), INDEX IDX_749D6E781A22F740 (target_feedback_search_id), INDEX IDX_749D6E7837119A26 (feedback_lookup_id), INDEX IDX_749D6E78F6705374 (target_feedback_lookup_id), INDEX IDX_749D6E78A0E2F38 (telegram_bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback_notifications ADD CONSTRAINT FK_749D6E789E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedback_notifications ADD CONSTRAINT FK_749D6E78C2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('ALTER TABLE feedback_notifications ADD CONSTRAINT FK_749D6E78D249A887 FOREIGN KEY (feedback_id) REFERENCES feedbacks (id)');
        $this->addSql('ALTER TABLE feedback_notifications ADD CONSTRAINT FK_749D6E785573E78D FOREIGN KEY (target_feedback_id) REFERENCES feedbacks (id)');
        $this->addSql('ALTER TABLE feedback_notifications ADD CONSTRAINT FK_749D6E78DB433E12 FOREIGN KEY (feedback_search_id) REFERENCES feedback_searches (id)');
        $this->addSql('ALTER TABLE feedback_notifications ADD CONSTRAINT FK_749D6E781A22F740 FOREIGN KEY (target_feedback_search_id) REFERENCES feedback_searches (id)');
        $this->addSql('ALTER TABLE feedback_notifications ADD CONSTRAINT FK_749D6E7837119A26 FOREIGN KEY (feedback_lookup_id) REFERENCES feedback_lookups (id)');
        $this->addSql('ALTER TABLE feedback_notifications ADD CONSTRAINT FK_749D6E78F6705374 FOREIGN KEY (target_feedback_lookup_id) REFERENCES feedback_lookups (id)');
        $this->addSql('ALTER TABLE feedback_notifications ADD CONSTRAINT FK_749D6E78A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('ALTER TABLE feedback_lookup_1_telegram_notifications DROP FOREIGN KEY FK_9F28825237119A26');
        $this->addSql('ALTER TABLE feedback_lookup_1_telegram_notifications DROP FOREIGN KEY FK_9F2882529E4CEF7D');
        $this->addSql('ALTER TABLE feedback_lookup_1_telegram_notifications DROP FOREIGN KEY FK_9F288252A0E2F38');
        $this->addSql('ALTER TABLE feedback_lookup_1_telegram_notifications DROP FOREIGN KEY FK_9F288252C2ED3DD8');
        $this->addSql('ALTER TABLE feedback_lookup_2_telegram_notifications DROP FOREIGN KEY FK_C7362B7A37119A26');
        $this->addSql('ALTER TABLE feedback_lookup_2_telegram_notifications DROP FOREIGN KEY FK_C7362B7A9E4CEF7D');
        $this->addSql('ALTER TABLE feedback_lookup_2_telegram_notifications DROP FOREIGN KEY FK_C7362B7AA0E2F38');
        $this->addSql('ALTER TABLE feedback_lookup_2_telegram_notifications DROP FOREIGN KEY FK_C7362B7AC2ED3DD8');
        $this->addSql('ALTER TABLE feedback_lookup_2_telegram_notifications DROP FOREIGN KEY FK_C7362B7AF6705374');
        $this->addSql('ALTER TABLE feedback_lookup_user_telegram_notifications DROP FOREIGN KEY FK_72B9752E37119A26');
        $this->addSql('ALTER TABLE feedback_lookup_user_telegram_notifications DROP FOREIGN KEY FK_72B9752E9E4CEF7D');
        $this->addSql('ALTER TABLE feedback_lookup_user_telegram_notifications DROP FOREIGN KEY FK_72B9752EA0E2F38');
        $this->addSql('ALTER TABLE feedback_lookup_user_telegram_notifications DROP FOREIGN KEY FK_72B9752EC2ED3DD8');
        $this->addSql('ALTER TABLE feedback_lookup_user_telegram_notifications DROP FOREIGN KEY FK_72B9752EDB433E12');
        $this->addSql('ALTER TABLE feedback_search_1_telegram_notifications DROP FOREIGN KEY FK_5BC75AA49E4CEF7D');
        $this->addSql('ALTER TABLE feedback_search_1_telegram_notifications DROP FOREIGN KEY FK_5BC75AA41A22F740');
        $this->addSql('ALTER TABLE feedback_search_1_telegram_notifications DROP FOREIGN KEY FK_5BC75AA4A0E2F38');
        $this->addSql('ALTER TABLE feedback_search_1_telegram_notifications DROP FOREIGN KEY FK_5BC75AA4C2ED3DD8');
        $this->addSql('ALTER TABLE feedback_search_1_telegram_notifications DROP FOREIGN KEY FK_5BC75AA4DB433E12');
        $this->addSql('ALTER TABLE feedback_search_search_term_user_telegram_notifications DROP FOREIGN KEY FK_9A7647FBDB433E12');
        $this->addSql('ALTER TABLE feedback_search_search_term_user_telegram_notifications DROP FOREIGN KEY FK_9A7647FB9E4CEF7D');
        $this->addSql('ALTER TABLE feedback_search_search_term_user_telegram_notifications DROP FOREIGN KEY FK_9A7647FBA0E2F38');
        $this->addSql('ALTER TABLE feedback_search_search_term_user_telegram_notifications DROP FOREIGN KEY FK_9A7647FBC2ED3DD8');
        $this->addSql('ALTER TABLE feedback_search_term_user_telegram_notifications DROP FOREIGN KEY FK_AB4E7BDB9E4CEF7D');
        $this->addSql('ALTER TABLE feedback_search_term_user_telegram_notifications DROP FOREIGN KEY FK_AB4E7BDBA0E2F38');
        $this->addSql('ALTER TABLE feedback_search_term_user_telegram_notifications DROP FOREIGN KEY FK_AB4E7BDBC2ED3DD8');
        $this->addSql('ALTER TABLE feedback_search_term_user_telegram_notifications DROP FOREIGN KEY FK_AB4E7BDBD249A887');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications DROP FOREIGN KEY FK_BAA4CA76C2ED3DD8');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications DROP FOREIGN KEY FK_BAA4CA769E4CEF7D');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications DROP FOREIGN KEY FK_BAA4CA76A0E2F38');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications DROP FOREIGN KEY FK_BAA4CA76D249A887');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications DROP FOREIGN KEY FK_BAA4CA76DB433E12');
        $this->addSql('ALTER TABLE feedback_telegram_notifications DROP FOREIGN KEY FK_BA31FDF45573E78D');
        $this->addSql('ALTER TABLE feedback_telegram_notifications DROP FOREIGN KEY FK_BA31FDF49E4CEF7D');
        $this->addSql('ALTER TABLE feedback_telegram_notifications DROP FOREIGN KEY FK_BA31FDF4A0E2F38');
        $this->addSql('ALTER TABLE feedback_telegram_notifications DROP FOREIGN KEY FK_BA31FDF4C2ED3DD8');
        $this->addSql('ALTER TABLE feedback_telegram_notifications DROP FOREIGN KEY FK_BA31FDF4D249A887');
        $this->addSql('DROP TABLE feedback_lookup_1_telegram_notifications');
        $this->addSql('DROP TABLE feedback_lookup_2_telegram_notifications');
        $this->addSql('DROP TABLE feedback_lookup_user_telegram_notifications');
        $this->addSql('DROP TABLE feedback_search_1_telegram_notifications');
        $this->addSql('DROP TABLE feedback_search_search_term_user_telegram_notifications');
        $this->addSql('DROP TABLE feedback_search_term_user_telegram_notifications');
        $this->addSql('DROP TABLE feedback_search_user_telegram_notifications');
        $this->addSql('DROP TABLE feedback_telegram_notifications');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback_lookup_1_telegram_notifications (id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, messenger_user_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, feedback_search_term_id INT UNSIGNED NOT NULL, feedback_lookup_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, telegram_bot_id SMALLINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_9F2882529E4CEF7D (messenger_user_id), INDEX IDX_9F288252C2ED3DD8 (feedback_search_term_id), INDEX IDX_9F28825237119A26 (feedback_lookup_id), INDEX IDX_9F288252A0E2F38 (telegram_bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE feedback_lookup_2_telegram_notifications (id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, messenger_user_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, feedback_search_term_id INT UNSIGNED NOT NULL, feedback_lookup_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, target_feedback_lookup_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, telegram_bot_id SMALLINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C7362B7A9E4CEF7D (messenger_user_id), INDEX IDX_C7362B7AC2ED3DD8 (feedback_search_term_id), INDEX IDX_C7362B7A37119A26 (feedback_lookup_id), INDEX IDX_C7362B7AF6705374 (target_feedback_lookup_id), INDEX IDX_C7362B7AA0E2F38 (telegram_bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE feedback_lookup_user_telegram_notifications (id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, messenger_user_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, feedback_search_term_id INT UNSIGNED NOT NULL, feedback_search_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, feedback_lookup_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, telegram_bot_id SMALLINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_72B9752E37119A26 (feedback_lookup_id), INDEX IDX_72B9752E9E4CEF7D (messenger_user_id), INDEX IDX_72B9752EA0E2F38 (telegram_bot_id), INDEX IDX_72B9752EC2ED3DD8 (feedback_search_term_id), INDEX IDX_72B9752EDB433E12 (feedback_search_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE feedback_search_1_telegram_notifications (id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, messenger_user_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, feedback_search_term_id INT UNSIGNED NOT NULL, feedback_search_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, target_feedback_search_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, telegram_bot_id SMALLINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5BC75AA41A22F740 (target_feedback_search_id), INDEX IDX_5BC75AA49E4CEF7D (messenger_user_id), INDEX IDX_5BC75AA4A0E2F38 (telegram_bot_id), INDEX IDX_5BC75AA4C2ED3DD8 (feedback_search_term_id), INDEX IDX_5BC75AA4DB433E12 (feedback_search_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE feedback_search_search_term_user_telegram_notifications (id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, messenger_user_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, feedback_search_term_id INT UNSIGNED NOT NULL, feedback_search_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, telegram_bot_id SMALLINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_9A7647FB9E4CEF7D (messenger_user_id), INDEX IDX_9A7647FBA0E2F38 (telegram_bot_id), INDEX IDX_9A7647FBC2ED3DD8 (feedback_search_term_id), INDEX IDX_9A7647FBDB433E12 (feedback_search_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE feedback_search_term_user_telegram_notifications (id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, messenger_user_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, feedback_search_term_id INT UNSIGNED NOT NULL, feedback_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, telegram_bot_id SMALLINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_AB4E7BDB9E4CEF7D (messenger_user_id), INDEX IDX_AB4E7BDBA0E2F38 (telegram_bot_id), INDEX IDX_AB4E7BDBC2ED3DD8 (feedback_search_term_id), INDEX IDX_AB4E7BDBD249A887 (feedback_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE feedback_search_user_telegram_notifications (id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, messenger_user_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, feedback_search_term_id INT UNSIGNED NOT NULL, feedback_search_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, telegram_bot_id SMALLINT UNSIGNED NOT NULL, feedback_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BAA4CA769E4CEF7D (messenger_user_id), INDEX IDX_BAA4CA76A0E2F38 (telegram_bot_id), INDEX IDX_BAA4CA76C2ED3DD8 (feedback_search_term_id), INDEX IDX_BAA4CA76D249A887 (feedback_id), INDEX IDX_BAA4CA76DB433E12 (feedback_search_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE feedback_telegram_notifications (id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, messenger_user_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, feedback_search_term_id INT UNSIGNED NOT NULL, feedback_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, target_feedback_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, telegram_bot_id SMALLINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BA31FDF45573E78D (target_feedback_id), INDEX IDX_BA31FDF49E4CEF7D (messenger_user_id), INDEX IDX_BA31FDF4A0E2F38 (telegram_bot_id), INDEX IDX_BA31FDF4C2ED3DD8 (feedback_search_term_id), INDEX IDX_BA31FDF4D249A887 (feedback_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE feedback_lookup_1_telegram_notifications ADD CONSTRAINT FK_9F28825237119A26 FOREIGN KEY (feedback_lookup_id) REFERENCES feedback_lookups (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookup_1_telegram_notifications ADD CONSTRAINT FK_9F2882529E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookup_1_telegram_notifications ADD CONSTRAINT FK_9F288252A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookup_1_telegram_notifications ADD CONSTRAINT FK_9F288252C2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookup_2_telegram_notifications ADD CONSTRAINT FK_C7362B7A37119A26 FOREIGN KEY (feedback_lookup_id) REFERENCES feedback_lookups (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookup_2_telegram_notifications ADD CONSTRAINT FK_C7362B7A9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookup_2_telegram_notifications ADD CONSTRAINT FK_C7362B7AA0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookup_2_telegram_notifications ADD CONSTRAINT FK_C7362B7AC2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookup_2_telegram_notifications ADD CONSTRAINT FK_C7362B7AF6705374 FOREIGN KEY (target_feedback_lookup_id) REFERENCES feedback_lookups (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookup_user_telegram_notifications ADD CONSTRAINT FK_72B9752E37119A26 FOREIGN KEY (feedback_lookup_id) REFERENCES feedback_lookups (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookup_user_telegram_notifications ADD CONSTRAINT FK_72B9752E9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookup_user_telegram_notifications ADD CONSTRAINT FK_72B9752EA0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookup_user_telegram_notifications ADD CONSTRAINT FK_72B9752EC2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookup_user_telegram_notifications ADD CONSTRAINT FK_72B9752EDB433E12 FOREIGN KEY (feedback_search_id) REFERENCES feedback_searches (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_1_telegram_notifications ADD CONSTRAINT FK_5BC75AA49E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_1_telegram_notifications ADD CONSTRAINT FK_5BC75AA41A22F740 FOREIGN KEY (target_feedback_search_id) REFERENCES feedback_searches (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_1_telegram_notifications ADD CONSTRAINT FK_5BC75AA4A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_1_telegram_notifications ADD CONSTRAINT FK_5BC75AA4C2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_1_telegram_notifications ADD CONSTRAINT FK_5BC75AA4DB433E12 FOREIGN KEY (feedback_search_id) REFERENCES feedback_searches (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_search_term_user_telegram_notifications ADD CONSTRAINT FK_9A7647FBDB433E12 FOREIGN KEY (feedback_search_id) REFERENCES feedback_searches (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_search_term_user_telegram_notifications ADD CONSTRAINT FK_9A7647FB9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_search_term_user_telegram_notifications ADD CONSTRAINT FK_9A7647FBA0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_search_term_user_telegram_notifications ADD CONSTRAINT FK_9A7647FBC2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_term_user_telegram_notifications ADD CONSTRAINT FK_AB4E7BDB9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_term_user_telegram_notifications ADD CONSTRAINT FK_AB4E7BDBA0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_term_user_telegram_notifications ADD CONSTRAINT FK_AB4E7BDBC2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_term_user_telegram_notifications ADD CONSTRAINT FK_AB4E7BDBD249A887 FOREIGN KEY (feedback_id) REFERENCES feedbacks (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications ADD CONSTRAINT FK_BAA4CA76C2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications ADD CONSTRAINT FK_BAA4CA769E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications ADD CONSTRAINT FK_BAA4CA76A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications ADD CONSTRAINT FK_BAA4CA76D249A887 FOREIGN KEY (feedback_id) REFERENCES feedbacks (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_user_telegram_notifications ADD CONSTRAINT FK_BAA4CA76DB433E12 FOREIGN KEY (feedback_search_id) REFERENCES feedback_searches (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_telegram_notifications ADD CONSTRAINT FK_BA31FDF45573E78D FOREIGN KEY (target_feedback_id) REFERENCES feedbacks (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_telegram_notifications ADD CONSTRAINT FK_BA31FDF49E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_telegram_notifications ADD CONSTRAINT FK_BA31FDF4A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_telegram_notifications ADD CONSTRAINT FK_BA31FDF4C2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_telegram_notifications ADD CONSTRAINT FK_BA31FDF4D249A887 FOREIGN KEY (feedback_id) REFERENCES feedbacks (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_notifications DROP FOREIGN KEY FK_749D6E789E4CEF7D');
        $this->addSql('ALTER TABLE feedback_notifications DROP FOREIGN KEY FK_749D6E78C2ED3DD8');
        $this->addSql('ALTER TABLE feedback_notifications DROP FOREIGN KEY FK_749D6E78D249A887');
        $this->addSql('ALTER TABLE feedback_notifications DROP FOREIGN KEY FK_749D6E785573E78D');
        $this->addSql('ALTER TABLE feedback_notifications DROP FOREIGN KEY FK_749D6E78DB433E12');
        $this->addSql('ALTER TABLE feedback_notifications DROP FOREIGN KEY FK_749D6E781A22F740');
        $this->addSql('ALTER TABLE feedback_notifications DROP FOREIGN KEY FK_749D6E7837119A26');
        $this->addSql('ALTER TABLE feedback_notifications DROP FOREIGN KEY FK_749D6E78F6705374');
        $this->addSql('ALTER TABLE feedback_notifications DROP FOREIGN KEY FK_749D6E78A0E2F38');
        $this->addSql('DROP TABLE feedback_notifications');
    }
}
