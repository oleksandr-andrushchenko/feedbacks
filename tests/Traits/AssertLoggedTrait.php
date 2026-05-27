<?php
declare(strict_types=1);

namespace App\Tests\Traits;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use RuntimeException;

trait AssertLoggedTrait
{
    public function assertLogged(Level $level, string $text, string $message = ''): void
    {
        $logger = static::getContainer()->get('monolog.logger');

        $testHandler = null;

        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof TestHandler) {
                $testHandler = $handler;
                break;
            }
        }

        if ($testHandler === null) {
            throw new RuntimeException('Oops, not exist "test" handler in monolog.');
        }

        $this->assertTrue($testHandler->hasRecord($text, $level), $message);
    }
}