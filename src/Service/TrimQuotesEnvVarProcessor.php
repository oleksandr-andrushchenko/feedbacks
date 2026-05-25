<?php

declare(strict_types=1);

namespace App\Service;

use Closure;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class TrimQuotesEnvVarProcessor implements EnvVarProcessorInterface
{
    public static function getProvidedTypes(): array
    {
        return [
            'trimQuotes' => 'string',
        ];
    }

    public function getEnv(string $prefix, string $name, Closure $getEnv): mixed
    {
        $env = $getEnv($name);

        return trim($env, '\'"');
    }
}