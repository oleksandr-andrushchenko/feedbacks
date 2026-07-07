<?php
declare(strict_types=1);

namespace App\Tests;

use App\Tests\Traits\ConsoleCommandRunnerTrait;
use App\Tests\Traits\EntityManagerProviderTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

abstract class DatabaseTestCase extends KernelTestCase
{
    use EntityManagerProviderTrait;
    use ConsoleCommandRunnerTrait;

    protected static bool $databaseBooted = false;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseUp();
    }

    public function tearDown(): void
    {
        $this->databaseDown();

        parent::tearDown();
    }

    protected function databaseUp(): void
    {
        $this->bootDatabase()
            ->rollBackIfNeed()
            ->beginTransaction()
        ;
    }

    protected function databaseDown(): void
    {
        $this->rollBackIfNeed();
    }

    protected function beginTransaction(): void
    {
    }

    protected function rollBackIfNeed(): static
    {
        return $this;
    }

    protected function bootDatabase(): static
    {
        if (static::$databaseBooted) {
            return $this;
        }

        $this->resetDynamodbTables();

        static::$databaseBooted = true;

        return $this;
    }

    protected function bootFixtures(array $refs): self
    {
        $this->resetDynamodbTables();

        $fixtures = new Fixtures($this->getEntityManager());
        $fixtures->bootFixtures($refs);

        return $this;
    }

    private function resetDynamodbTables(): void
    {
        $em = $this->getEntityManager()->getDynamodb();
        $schemaTool = $em->getSchemaTool();
        try {
            $schemaTool->dropTables();
        } catch (Throwable) {
        }
        $schema = json_decode($this->runConsoleCommand('dynamodb:schema:extract'), true);
        $schemaTool->createTables($schema);
    }
}
