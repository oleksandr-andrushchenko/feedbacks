<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260114040207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_bots CHANGE check_updates check_updates TINYINT(1) DEFAULT 0, CHANGE check_requests check_requests TINYINT(1) DEFAULT 0, CHANGE accept_payments accept_payments TINYINT(1) DEFAULT 0');
        $this->addSql('ALTER TABLE telegram_requests CHANGE bot_id bot_id SMALLINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE telegram_updates CHANGE bot_id bot_id SMALLINT UNSIGNED NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_bots CHANGE check_updates check_updates TINYINT(1) DEFAULT 0 NOT NULL, CHANGE check_requests check_requests TINYINT(1) DEFAULT 0 NOT NULL, CHANGE accept_payments accept_payments TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE telegram_requests CHANGE bot_id bot_id SMALLINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE telegram_updates CHANGE bot_id bot_id SMALLINT UNSIGNED DEFAULT NULL');
    }
}
