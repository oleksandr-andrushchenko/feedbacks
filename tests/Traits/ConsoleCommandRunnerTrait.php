<?php

declare(strict_types=1);

namespace App\Tests\Traits;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

trait ConsoleCommandRunnerTrait
{
    protected function runConsoleCommand(string $command, array $arguments = [], array $inputs = []): string
    {
        if (!self::$kernel) {
            self::bootKernel();
        }

        $application = new Application(self::$kernel);
        $application->setAutoExit(false);

        if (!$application->has($command)) {
            throw new RuntimeException(sprintf(
                'Console command "%s" not found.',
                $command
            ));
        }

        $tester = new ApplicationTester($application);

        if ($inputs !== []) {
            $tester->setInputs($inputs);
        }

        $tester->run(array_merge(
            ['--verbose' => true],
            ['command' => $command],
            $arguments
        ));

        return $tester->getDisplay();
    }
}