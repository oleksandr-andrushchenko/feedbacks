<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231105233816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_2F5DF3DDE22A4301772E836A ON messenger_users');
        $this->addSql('CREATE INDEX IDX_2F5DF3DD772E836A ON messenger_users (identifier)');
        $this->addSql('CREATE INDEX IDX_2F5DF3DDF85E0677 ON messenger_users (username)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_2F5DF3DD772E836A ON messenger_users');
        $this->addSql('DROP INDEX IDX_2F5DF3DDF85E0677 ON messenger_users');
        $this->addSql('CREATE INDEX IDX_2F5DF3DDE22A4301772E836A ON messenger_users (messenger, identifier)');
    }
}
