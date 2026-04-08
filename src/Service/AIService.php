<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Multi-provider AI service with cascading fallback.
 * Mirrors the Java ZAIService architecture:
 *   1. Z.AI (GLM-5 / GLM-4.7-Flash)
 *   2. Groq (Llama 3.3-70B / 3.1-8B)
 *   3. OpenCode Zen (GLM-5 / Qwen3-coder)
 *   4. OpenRouter (free Llama / Gemma)
 */
class AIService
{
    private array $providers;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $zaiApiKey = '',
        string $groqApiKey = '',
        string $openrouterApiKey = '',
        string $opencodeApiKey = '',
    ) {
        $this->providers = [
            ['name' => 'Z.AI glm-5',           'url' => 'https://api.z.ai/api/paas/v4/chat/completions',        'key' => $zaiApiKey,        'model' => 'glm-5'],
            ['name' => 'Z.AI glm-4.7-flash',   'url' => 'https://api.z.ai/api/paas/v4/chat/completions',        'key' => $zaiApiKey,        'model' => 'glm-4.7-flash'],
            ['name' => 'Groq llama-3.3-70b',    'url' => 'https://api.groq.com/openai/v1/chat/completions',      'key' => $groqApiKey,       'model' => 'llama-3.3-70b-versatile'],
            ['name' => 'Groq llama-3.1-8b',     'url' => 'https://api.groq.com/openai/v1/chat/completions',      'key' => $groqApiKey,       'model' => 'llama-3.1-8b-instant'],
            ['name' => 'OpenCode glm-5',        'url' => 'https://opencode.ai/zen/v1/chat/completions',          'key' => $opencodeApiKey,   'model' => 'glm-5'],
            ['name' => 'OpenCode qwen3-coder',  'url' => 'https://opencode.ai/zen/v1/chat/completions',          'key' => $opencodeApiKey,   'model' => 'qwen3-coder'],
            ['name' => 'OpenRouter llama-free',  'url' => 'https://openrouter.ai/api/v1/chat/completions',        'key' => $openrouterApiKey, 'model' => 'meta-llama/llama-3.3-70b-instruct:free'],
            ['name' => 'OpenRouter gemma-free',  'url' => 'https://openrouter.ai/api/v1/chat/completions',        'key' => $openrouterApiKey, 'model' => 'google/gemma-2-9b-it:free'],
        ];
    }

    /**
     * Send a chat completion request with automatic provider fallback.
     * Returns the assistant's message content, or null if all providers fail.
     */
    public function chat(string $systemPrompt, string $userMessage, float $temperature = 0.7, int $maxTokens = 2048): ?string
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userMessage],
        ];

        foreach ($this->providers as $provider) {
            if (empty($provider['key'])) {
                continue;
            }

            try {
                $response = $this->httpClient->request('POST', $provider['url'], [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $provider['key'],
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model'       => $provider['model'],
                        'messages'    => $messages,
                        'temperature' => $temperature,
                        'max_tokens'  => $maxTokens,
                    ],
                    'timeout' => 30,
                ]);

                $data = $response->toArray();
                $content = $data['choices'][0]['message']['content'] ?? null;

                if ($content) {
                    $this->logger->info('AI response from {provider}', ['provider' => $provider['name']]);
                    return $content;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('AI provider {provider} failed: {error}', [
                    'provider' => $provider['name'],
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        $this->logger->error('All AI providers failed.');
        return null;
    }

    /* ─── Convenience methods matching Java ZAIService ─── */

    public function reviewCode(string $code, string $language): string
    {
        $system = "You are an expert code reviewer. Analyze the code and return a structured review in Markdown with these sections:\n"
            . "1. **Overall Quality** (score /10)\n2. **Bugs & Issues**\n3. **Security Concerns**\n"
            . "4. **Performance**\n5. **Code Style & Best Practices**\n6. **Suggestions**\n"
            . "Be concise but thorough.";
        $user = "Language: {$language}\n\n```{$language}\n{$code}\n```";

        return $this->chat($system, $user) ?? $this->fallbackCodeReview($code, $language);
    }

    public function composeEmail(string $recipient, string $purpose, string $keyPoints, string $tone): string
    {
        $system = "You are a professional email composer for a corporate HR/project management platform called SynergyGig. "
            . "Write a complete, ready-to-send email. Tone: {$tone}. Include Subject line.";
        $user = "Recipient: {$recipient}\nPurpose: {$purpose}\nKey points:\n{$keyPoints}";

        return $this->chat($system, $user, 0.7, 1024) ?? $this->fallbackEmail($recipient, $purpose, $keyPoints, $tone);
    }

    public function summarizeMeeting(string $transcript): string
    {
        $system = "You are a meeting summarizer. Produce a structured Markdown summary with: "
            . "**Attendees**, **Key Discussion Points**, **Decisions Made**, **Action Items** (with owners if identifiable), "
            . "and **Follow-up Timeline**. Be concise.";

        return $this->chat($system, $transcript) ?? $this->fallbackMeetingSummary($transcript);
    }

    public function parseResume(string $text): array
    {
        $system = "You are an expert resume parser. Extract structured data from the resume text and return ONLY valid JSON with these keys:\n"
            . '{"name":"","email":"","phone":"","location":"","summary":"","skills":[],'
            . '"experience":[{"title":"","company":"","period":"","description":""}],'
            . '"education":[{"degree":"","institution":"","year":""}],'
            . '"certifications":[],"languages":[]}'
            . "\nReturn ONLY the JSON object, no markdown code fences.";

        $result = $this->chat($system, $text, 0.3, 2048);
        if ($result) {
            // Strip markdown fences if present
            $result = preg_replace('/^```(?:json)?\s*|```\s*$/m', '', trim($result));
            $parsed = json_decode($result, true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        return $this->fallbackResumeParse($text);
    }

    public function interviewQuestion(string $role, string $level, string $action, string $answer, int $qNum): array
    {
        if ($action === 'start') {
            $system = "You are an expert interview coach. Generate the first interview question for a {$level}-level {$role} position. "
                . "Return JSON: {\"type\":\"question\",\"message\":\"<markdown>\",\"question\":1}";
            $result = $this->chat($system, "Begin the mock interview.", 0.8, 512);
            if ($result) {
                $result = preg_replace('/^```(?:json)?\s*|```\s*$/m', '', trim($result));
                $parsed = json_decode($result, true);
                if (is_array($parsed)) {
                    return $parsed;
                }
            }
        }

        if ($action === 'end') {
            $system = "You are an interview coach. Provide a performance summary for a {$level}-level {$role} interview "
                . "where the candidate completed {$qNum}/8 questions. Return JSON: {\"type\":\"summary\",\"message\":\"<markdown>\"}";
            $result = $this->chat($system, "Summarize the interview performance.", 0.7, 1024);
            if ($result) {
                $result = preg_replace('/^```(?:json)?\s*|```\s*$/m', '', trim($result));
                $parsed = json_decode($result, true);
                if (is_array($parsed)) {
                    return $parsed;
                }
            }
        }

        if ($action === 'answer' || $action === 'skip') {
            $nextQ = min($qNum + 1, 8);
            $feedbackReq = $action === 'answer'
                ? "Rate this answer (1-5 stars) and give brief feedback, then ask question {$nextQ}."
                : "The candidate skipped. Give a sample answer, then ask question {$nextQ}.";

            $system = "You are an interview coach for a {$level}-level {$role} position. "
                . "{$feedbackReq} Return JSON: {\"type\":\"feedback\",\"message\":\"<markdown>\",\"question\":{$nextQ}}";
            $user = $action === 'answer' ? "Candidate's answer: {$answer}" : "Candidate skipped question {$qNum}.";

            $result = $this->chat($system, $user, 0.7, 1024);
            if ($result) {
                $result = preg_replace('/^```(?:json)?\s*|```\s*$/m', '', trim($result));
                $parsed = json_decode($result, true);
                if (is_array($parsed)) {
                    return $parsed;
                }
            }
        }

        // Fallback
        return $this->fallbackInterview($role, $level, $action, $answer, $qNum);
    }

    public function chatHRPolicy(string $question, array $conversationHistory = []): string
    {
        $system = "You are an HR Policy Assistant for SynergyGig, a corporate HR and project management platform. "
            . "Answer questions about common HR policies: leave policies (annual: 21 days, sick: 15 days, maternity: 90 days, paternity: 14 days), "
            . "attendance rules, payroll procedures, training requirements, interview processes, contract management, "
            . "employee onboarding, performance reviews, and workplace conduct. "
            . "Be helpful, professional, and concise. If unsure, advise consulting the HR department directly.";

        $messages = [['role' => 'system', 'content' => $system]];
        foreach ($conversationHistory as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $question];

        foreach ($this->providers as $provider) {
            if (empty($provider['key'])) {
                continue;
            }
            try {
                $response = $this->httpClient->request('POST', $provider['url'], [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $provider['key'],
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model'       => $provider['model'],
                        'messages'    => $messages,
                        'temperature' => 0.5,
                        'max_tokens'  => 1024,
                    ],
                    'timeout' => 30,
                ]);
                $data = $response->toArray();
                $content = $data['choices'][0]['message']['content'] ?? null;
                if ($content) {
                    return $content;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('AI provider {provider} failed for HR chat: {error}', [
                    'provider' => $provider['name'],
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return "I'm sorry, I'm unable to process your question right now. Please try again later or contact the HR department directly.";
    }

    public function scanJobs(string $skills, string $location, string $level): string
    {
        $system = "You are a job market analyst AI. Given a candidate's skills, preferred location, and experience level, "
            . "generate a realistic list of 8-10 matching job opportunities in Markdown table format with columns: "
            . "Company | Position | Location | Match % | Salary Range | Key Requirements. "
            . "Also add a brief market analysis section and tips for the candidate.";
        $user = "Skills: {$skills}\nPreferred Location: {$location}\nExperience Level: {$level}";

        return $this->chat($system, $user, 0.8, 2048)
            ?? "Unable to scan jobs at this time. Please try again later.";
    }

    /* ─── Fallback generators (used when all AI providers fail) ─── */

    private function fallbackCodeReview(string $code, string $lang): string
    {
        $lines = substr_count($code, "\n") + 1;
        $hasFunc = preg_match('/function\s|def\s|void\s|public\s|private\s/', $code);
        $hasTry  = preg_match('/try\s*\{|except|catch\s*\(/', $code);
        $hasCom  = preg_match('/\/\/|#|\/\*/', $code);
        $score   = min(10, max(3, 5 + ($hasFunc ? 1 : 0) + ($hasTry ? 1 : -1) + ($hasCom ? 1 : 0)));

        return "## Code Review — {$lang}\n\n### Overall Quality: **{$score}/10**\n"
            . "Analyzed **{$lines} lines** of {$lang}.\n\n*(AI providers unavailable — basic static analysis used)*\n\n"
            . "### Bugs & Issues\n" . ($hasTry ? "- Error handling detected.\n" : "- No error handling found.\n")
            . "\n### Security\n" . (preg_match('/eval|exec|innerHTML/', $code) ? "- Potential security risk detected.\n" : "- No immediate issues.\n")
            . "\n### Suggestions\n1. Add input validation.\n2. Add unit tests.\n";
    }

    private function fallbackEmail(string $to, string $purpose, string $points, string $tone): string
    {
        $g = match ($tone) { 'Formal' => "Dear {$to},", 'Casual' => "Hey {$to},", 'Urgent' => "URGENT — {$to},", default => "Hi {$to}," };
        $c = match ($tone) { 'Formal' => "Yours sincerely,", 'Casual' => "Cheers,", default => "Best regards," };
        $body = "Subject: Regarding {$purpose}\n\n{$g}\n\nI am writing regarding {$purpose}.\n\n";
        if ($points) {
            foreach (explode("\n", $points) as $p) { if (trim($p)) $body .= "• " . trim($p) . "\n"; }
        }
        return $body . "\n{$c}\n[Your Name]\n\n*(AI providers unavailable — template used)*";
    }

    private function fallbackMeetingSummary(string $transcript): string
    {
        $words = str_word_count($transcript);
        return "## Meeting Summary\n\n**Words analyzed**: {$words}\n\n"
            . "### Key Points\n- Meeting covered multiple topics.\n\n"
            . "### Action Items\n1. Follow up on discussion.\n2. Schedule follow-up.\n\n"
            . "*(AI providers unavailable — basic analysis used)*";
    }

    private function fallbackResumeParse(string $text): array
    {
        preg_match('/[\w.+-]+@[\w-]+\.[\w.]+/', $text, $email);
        preg_match('/[\+]?[\d\s\-\(\)]{7,15}/', $text, $phone);
        $firstLine = trim(strtok($text, "\n"));
        $skills = [];
        foreach (['PHP','Java','Python','JavaScript','React','Angular','Vue','Node.js','SQL','Docker','AWS','Git','Symfony','Laravel','Spring'] as $kw) {
            if (stripos($text, $kw) !== false) $skills[] = $kw;
        }
        return [
            'name' => strlen($firstLine) < 60 ? $firstLine : 'Not detected',
            'email' => $email[0] ?? 'Not found', 'phone' => trim($phone[0] ?? 'Not found'),
            'location' => 'Not detected',
            'summary' => str_word_count($text) . ' words, ' . count($skills) . ' skills found.',
            'skills' => $skills ?: ['No skills detected'],
            'experience' => [['title' => 'AI unavailable', 'company' => 'Paste full resume for better results', 'period' => '', 'description' => '']],
            'education' => [['degree' => 'AI unavailable', 'institution' => '', 'year' => '']],
            'certifications' => [], 'languages' => [],
        ];
    }

    private function fallbackInterview(string $role, string $level, string $action, string $answer, int $qNum): array
    {
        $questions = [
            "Tell me about yourself and why you're interested in the **{$role}** position.",
            "Describe a challenging project you worked on.",
            "How do you handle tight deadlines?",
            "Explain a technical concept related to **{$role}** simply.",
            "Tell me about a disagreement with a teammate.",
            "What's your approach to learning new technologies?",
            "How do you ensure code quality?",
            "Where do you see yourself in 3-5 years?",
        ];
        if ($action === 'start') {
            return ['type' => 'question', 'message' => "Welcome! Mock interview for **{$role}** ({$level}).\n\n**Q1/8:**\n{$questions[0]}", 'question' => 1];
        }
        if ($action === 'end') {
            return ['type' => 'summary', 'message' => "## Interview Summary\n**Role**: {$role} ({$level})\n**Completed**: {$qNum}/8\n\n*(AI unavailable — connect a provider for detailed feedback)*"];
        }
        $nextQ = min($qNum + 1, 8);
        return ['type' => 'feedback', 'message' => ($action === 'skip' ? "Skipped." : "Good answer!") . "\n\n**Q{$nextQ}/8:**\n" . ($questions[$nextQ - 1] ?? end($questions)), 'question' => $nextQ];
    }
}
