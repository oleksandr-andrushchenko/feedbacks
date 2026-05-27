<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231118210714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_payments ADD bot_id SMALLINT UNSIGNED NOT NULL, CHANGE messenger_user_id messenger_user_id VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE telegram_payments ADD CONSTRAINT FK_31578A7A92C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('CREATE INDEX IDX_31578A7A92C1C487 ON telegram_payments (bot_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_payments DROP FOREIGN KEY FK_31578A7A92C1C487');
        $this->addSql('DROP INDEX IDX_31578A7A92C1C487 ON telegram_payments');
        $this->addSql('ALTER TABLE telegram_payments DROP bot_id, CHANGE messenger_user_id messenger_user_id VARCHAR(32) DEFAULT NULL');
    }
}
