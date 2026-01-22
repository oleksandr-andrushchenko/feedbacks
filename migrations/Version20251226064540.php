<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251226064540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messenger_users CHANGE show_extended_keyboard show_extended_keyboard TINYINT(1) DEFAULT 0');
        $this->addSql('ALTER TABLE telegram_bots CHANGE admin_only admin_only TINYINT(1) DEFAULT 1, CHANGE descriptions_synced descriptions_synced TINYINT(1) DEFAULT 0, CHANGE webhook_synced webhook_synced TINYINT(1) DEFAULT 0, CHANGE commands_synced commands_synced TINYINT(1) DEFAULT 0, CHANGE _primary _primary TINYINT(1) DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messenger_users CHANGE show_extended_keyboard show_extended_keyboard TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE telegram_bots CHANGE admin_only admin_only TINYINT(1) DEFAULT 1 NOT NULL, CHANGE descriptions_synced descriptions_synced TINYINT(1) DEFAULT 0 NOT NULL, CHANGE webhook_synced webhook_synced TINYINT(1) DEFAULT 0 NOT NULL, CHANGE commands_synced commands_synced TINYINT(1) DEFAULT 0 NOT NULL, CHANGE _primary _primary TINYINT(1) DEFAULT 1 NOT NULL');
    }
}
