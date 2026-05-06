<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaService
{
    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    public function generate(string $prompt, string $model = 'llama3'): ?array
    {
        $response = $this->httpClient->request('POST', 'http://localhost:11434/api/generate', [
            'json' => [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
            ],
        ]);

        $data = $response->toArray(false);

        if (!isset($data['response'])) {
            return null;
        }

        return [
            'raw' => $data['response'],
        ];
    }
}