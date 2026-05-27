<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231023210719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback_lookups (id VARCHAR(32) NOT NULL, user_id VARCHAR(32) DEFAULT NULL, messenger_user_id VARCHAR(32) DEFAULT NULL, search_term_id INT UNSIGNED DEFAULT NULL, telegram_bot_id SMALLINT UNSIGNED DEFAULT NULL, has_active_subscription TINYINT(1) NOT NULL, country_code VARCHAR(2) DEFAULT NULL, locale_code VARCHAR(2) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_FACD0796A76ED395 (user_id), INDEX IDX_FACD07969E4CEF7D (messenger_user_id), INDEX IDX_FACD079683FDDA66 (search_term_id), INDEX IDX_FACD0796A0E2F38 (telegram_bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback_lookups ADD CONSTRAINT FK_FACD0796A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE feedback_lookups ADD CONSTRAINT FK_FACD07969E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedback_lookups ADD CONSTRAINT FK_FACD079683FDDA66 FOREIGN KEY (search_term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('ALTER TABLE feedback_lookups ADD CONSTRAINT FK_FACD0796A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC0A76ED395');
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC0A0E2F38');
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC09E4CEF7D');
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC083FDDA66');
        $this->addSql('DROP TABLE feedback_search_searches');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback_search_searches (id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, user_id VARCHAR(32) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, messenger_user_id VARCHAR(32) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, search_term_id INT UNSIGNED DEFAULT NULL, telegram_bot_id SMALLINT UNSIGNED DEFAULT NULL, has_active_subscription TINYINT(1) NOT NULL, country_code VARCHAR(2) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, locale_code VARCHAR(2) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5CD71DC083FDDA66 (search_term_id), INDEX IDX_5CD71DC09E4CEF7D (messenger_user_id), INDEX IDX_5CD71DC0A0E2F38 (telegram_bot_id), INDEX IDX_5CD71DC0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC0A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC0A0E2F38 FOREIGN KEY (telegram_bot_id) REFERENCES telegram_bots (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC09E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC083FDDA66 FOREIGN KEY (search_term_id) REFERENCES feedback_search_terms (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedback_lookups DROP FOREIGN KEY FK_FACD0796A76ED395');
        $this->addSql('ALTER TABLE feedback_lookups DROP FOREIGN KEY FK_FACD07969E4CEF7D');
        $this->addSql('ALTER TABLE feedback_lookups DROP FOREIGN KEY FK_FACD079683FDDA66');
        $this->addSql('ALTER TABLE feedback_lookups DROP FOREIGN KEY FK_FACD0796A0E2F38');
        $this->addSql('DROP TABLE feedback_lookups');
    }
}
