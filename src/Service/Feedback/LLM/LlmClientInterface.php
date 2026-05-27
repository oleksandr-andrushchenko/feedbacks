<?php
declare(strict_types=1);

namespace App\Service\Feedback\LLM;

interface LlmClientInterface
{
    /**
     * @param string $name
     * @param array<array{role: string, content: string}> $messages
     * @param array $schema
     * @return array
     */
    public function requestJson(string $name, array $messages, array $schema): array;
}
