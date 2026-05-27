<?php
declare(strict_types=1);

namespace App\Service\Feedback\LLM;

use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class OpenAiLlmClient implements LlmClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
        private string $model,
    )
    {
    }

    public function requestJson(string $name, array $messages, array $schema): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->model,
                'temperature' => 0,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => $name,
                        'strict' => true,
                        'schema' => $schema,
                    ],
                ],
                'messages' => $messages,
            ],
            'timeout' => 30,
        ]);

        $data = $response->toArray();
        $content = $data['choices'][0]['message']['content'] ?? null;

        if (!is_string($content)) {
            throw new RuntimeException('LLM response does not contain JSON content.');
        }

        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($payload)) {
            throw new RuntimeException('LLM response has invalid JSON payload.');
        }

        return $payload;
    }
}
