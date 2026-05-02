<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('ai.gemini.api_key');
        $this->model  = config('ai.gemini.model');
        $this->apiUrl = config('ai.gemini.api_url');
    }

    /**
     * Send a message to Gemini and get a response.
     *
     * @param string $systemPrompt  Context/instructions for the AI
     * @param array  $history       Previous messages [['role' => 'user'|'model', 'text' => '...']]
     * @param string $userMessage   The latest user message
     * @return string               The AI's response text
     */
    public function chat(string $systemPrompt, array $history, string $userMessage): string
    {
        $contents = [];

        foreach ($history as $msg) {
            $contents[] = [
                'role'  => $msg['role'],
                'parts' => [['text' => $msg['text']]],
            ];
        }

        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        $response = Http::withQueryParameters(['key' => $this->apiKey])
            ->timeout(30)
            ->post("{$this->apiUrl}/{$this->model}:generateContent", [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents'         => $contents,
                'generationConfig' => [
                    'temperature'     => 0.7,
                    'maxOutputTokens' => 1024,
                ],
            ]);

        if ($response->failed()) {
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \Exception('AI service unavailable. Please try again.');
        }

        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text']
            ?? 'Sorry, I could not generate a response.';
    }

    /**
     * Single-turn generation (no history needed).
     */
    public function generate(string $prompt): string
    {
        return $this->chat('', [], $prompt);
    }

    public function generateJson(string $prompt): string
    {
        $response = Http::withQueryParameters(['key' => $this->apiKey])
            ->timeout(30)
            ->post("{$this->apiUrl}/{$this->model}:generateContent", [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature'     => 0.1,
                    'maxOutputTokens' => 1024,
                    'thinkingConfig'  => ['thinkingBudget' => 0],
                ],
            ]);

        if ($response->failed()) {
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \Exception('AI service unavailable. Please try again.');
        }

        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text']
            ?? 'Sorry, I could not generate a response.';
    }
}