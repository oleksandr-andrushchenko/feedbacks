<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251201091945 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_2F00C7198B8E84281A9A7125F86D5A67 ON telegram_requests');
        $this->addSql('ALTER TABLE telegram_requests DROP inline_message_id');
        $this->addSql('CREATE INDEX IDX_2F00C7198B8E84281A9A7125 ON telegram_requests (created_at, chat_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_2F00C7198B8E84281A9A7125 ON telegram_requests');
        $this->addSql('ALTER TABLE telegram_requests ADD inline_message_id BIGINT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_2F00C7198B8E84281A9A7125F86D5A67 ON telegram_requests (created_at, chat_id, inline_message_id)');
    }
}
