<?php

namespace App\Service;

use App\Entity\Offers;

class OfferAiInsightService
{
    public function __construct(private OllamaService $ollamaService)
    {
    }

    public function analyzeOffer(Offers $offer): array
    {
        $prompt = $this->buildPrompt($offer);
        $result = $this->ollamaService->generate($prompt, 'llama3');

        if (!$result || empty($result['raw'])) {
            return $this->fallbackInsight($offer);
        }

        $decoded = json_decode($result['raw'], true);

        if (!is_array($decoded)) {
            return [
                ...$this->fallbackInsight($offer),
                'raw_text' => $result['raw'],
            ];
        }

        return [
            'category' => $decoded['category'] ?? 'General Freelance',
            'experience_level' => $decoded['experience_level'] ?? 'Mid',
            'urgency' => $decoded['urgency'] ?? 'Medium',
            'risk' => $decoded['risk'] ?? 'Moderate',
            'score' => $decoded['score'] ?? 70,
            'summary' => $decoded['summary'] ?? 'Analyse IA indisponible.',
            'strengths' => $decoded['strengths'] ?? [],
            'warnings' => $decoded['warnings'] ?? [],
        ];
    }

    private function buildPrompt(Offers $offer): string
    {
        return <<<PROMPT
Analyze this freelance/job offer and return ONLY valid JSON.

Offer title: {$offer->getTitle()}
Offer description: {$offer->getDescription()}
Offer type: {$offer->getType()}
Offer budget: {$offer->getAmount()}

Expected JSON format:
{
  "category": "Web Development | UI/UX Design | Marketing | Data | General Freelance",
  "experience_level": "Junior | Mid | Senior",
  "urgency": "Low | Medium | High",
  "risk": "Low | Moderate | High",
  "score": 0,
  "summary": "short professional summary",
  "strengths": ["...", "..."],
  "warnings": ["...", "..."]
}
PROMPT;
    }

    private function fallbackInsight(Offers $offer): array
    {
        return [
            'category' => 'General Freelance',
            'experience_level' => 'Mid',
            'urgency' => 'Medium',
            'risk' => 'Moderate',
            'score' => 68,
            'summary' => 'Analyse automatique temporairement indisponible. Les informations affichées sont basées sur une estimation locale.',
            'strengths' => ['Titre détecté', 'Description disponible'],
            'warnings' => ['Analyse IA non confirmée'],
        ];
    }
}