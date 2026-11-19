<?php

declare(strict_types=1);

namespace Yu\SimilarWords\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Yu\WebToolsCore\Exceptions\ToolException;

/**
 * The two OpenAI calls of the tool: a chat answer in a strict JSON Schema and embeddings of many texts at once.
 */
class OpenAiClient
{
    /**
     * @param  array{api_key: ?string, base_url: string, chat_model: string, embedding_model: string, timeout: int}  $config
     */
    public function __construct(private array $config) {}

    public function isConfigured(): bool
    {
        return filled($this->config['api_key']);
    }

    public function embeddingModel(): string
    {
        return $this->config['embedding_model'];
    }

    /**
     * @param  array<string, mixed>  $schema  JSON Schema of the answer
     * @return array<string, mixed>
     *
     * @throws ToolException "ai_unavailable" or "ai_bad_response"
     */
    public function chat(string $system, string $user, array $schema): array
    {
        $response = $this->post('/chat/completions', [
            'model' => $this->config['chat_model'],
            'temperature' => 0.3,
            'seed' => 42,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => ['name' => 'answer', 'strict' => true, 'schema' => $schema],
            ],
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ]);

        $answer = json_decode((string) $response->json('choices.0.message.content'), true);

        if (!is_array($answer)) {
            throw new ToolException('ai_bad_response');
        }

        return $answer;
    }

    /**
     * Embeddings in the order of $texts; text-embedding-3 vectors come normalized to length 1.
     *
     * @param  list<string>  $texts
     * @return list<list<float>>
     *
     * @throws ToolException "ai_unavailable" or "ai_bad_response"
     */
    public function embed(array $texts): array
    {
        $data = $this->post('/embeddings', [
            'model' => $this->config['embedding_model'],
            'input' => $texts,
        ])->json('data');

        if (!is_array($data) || count($data) !== count($texts)) {
            throw new ToolException('ai_bad_response');
        }

        usort($data, fn (array $a, array $b): int => $a['index'] <=> $b['index']);

        return array_map(fn (array $item): array => array_map(floatval(...), $item['embedding']), $data);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(string $path, array $payload): Response
    {
        try {
            return Http::withToken((string) $this->config['api_key'])
                ->timeout($this->config['timeout'])
                ->post(rtrim($this->config['base_url'], '/').$path, $payload)
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            report($e);

            throw new ToolException('ai_unavailable', previous: $e);
        }
    }
}
