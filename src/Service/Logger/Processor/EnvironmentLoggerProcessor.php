<?php
declare(strict_types=1);

namespace App\Service\Logger\Processor;

use Monolog\LogRecord;

class EnvironmentLoggerProcessor
{
    public function __construct(
        private readonly string $stage,
        private readonly string $env,
    )
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['stage'] = $this->stage;
        $record->extra['env'] = $this->env;

        return $record;
    }
}