<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add feedback media metadata to feedbacks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE feedbacks ADD media JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE feedbacks DROP media');
    }
}
