<?php
declare(strict_types=1);

namespace App\Service\LLM;

interface LlmClientInterface
{
    /**
     * @param array<array{role: string, content: string}> $messages
     */
    public function requestJson(string $name, array $messages, array $schema): array;
}
