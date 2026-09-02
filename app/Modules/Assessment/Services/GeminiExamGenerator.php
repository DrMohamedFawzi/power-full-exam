<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Services;

use App\Modules\Assessment\Enums\QuestionType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Adapter over the Gemini REST API. Reads only `config('aegis.ai.*')` — never
 * `env()` directly. Every failure mode (unconfigured, timeout, HTTP error,
 * malformed JSON) is normalised into an {@see AiGenerationException} carrying
 * an Arabic message; this class never lets an exam-authoring request 500.
 */
final class GeminiExamGenerator
{
    /**
     * @param  list<QuestionType>  $types
     * @return list<array<string, mixed>>
     */
    public function generate(
        string $topic,
        int $count,
        string $difficulty,
        string $language,
        array $types,
    ): array {
        if (! config('aegis.ai.enabled')) {
            throw new AiGenerationException('ميزة توليد الأسئلة بالذكاء الاصطناعي غير مُفعّلة حالياً.');
        }

        $apiKey = config('aegis.ai.api_key');

        if (blank($apiKey)) {
            throw new AiGenerationException(
                'لم يتم إعداد مفتاح Gemini API بعد. يرجى التواصل مع مسؤول النظام لتفعيل هذه الميزة.'
            );
        }

        $count = min($count, (int) config('aegis.ai.max_questions'));

        $raw = $this->call($apiKey, $this->buildPrompt($topic, $count, $difficulty, $language, $types));

        return $this->parse($raw);
    }

    private function call(string $apiKey, string $prompt): string
    {
        $model = config('aegis.ai.model');
        $endpoint = rtrim((string) config('aegis.ai.endpoint'), '/')."/models/{$model}:generateContent";

        try {
            $response = Http::timeout((int) config('aegis.ai.timeout_seconds'))
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$endpoint}?key={$apiKey}", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['responseMimeType' => 'application/json'],
                ]);
        } catch (Throwable) {
            throw new AiGenerationException('تعذّر الاتصال بخدمة الذكاء الاصطناعي. حاول مرة أخرى لاحقاً.');
        }

        if ($response->failed()) {
            throw new AiGenerationException('فشل توليد الأسئلة. حاول مرة أخرى لاحقاً أو أنشئ الأسئلة يدوياً.');
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            throw new AiGenerationException('استجابة غير متوقعة من خدمة الذكاء الاصطناعي.');
        }

        return $text;
    }

    /** @return list<array<string, mixed>> */
    private function parse(string $raw): array
    {
        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || $decoded === []) {
            throw new AiGenerationException('تعذّر قراءة الأسئلة الناتجة عن الذكاء الاصطناعي.');
        }

        $questions = [];

        foreach ($decoded as $item) {
            if (! is_array($item) || ! isset($item['type'], $item['prompt'], $item['correct_answer'])) {
                continue;
            }

            $type = QuestionType::tryFrom((string) $item['type']);

            if ($type === null) {
                continue;
            }

            $questions[] = [
                'type' => $type->value,
                'prompt' => (string) $item['prompt'],
                'options' => is_array($item['options'] ?? null) ? array_values($item['options']) : null,
                'correct_answer' => is_array($item['correct_answer']) ? array_values($item['correct_answer']) : [$item['correct_answer']],
                'explanation' => isset($item['explanation']) ? (string) $item['explanation'] : null,
                'points' => isset($item['points']) ? (float) $item['points'] : 1.0,
            ];
        }

        if ($questions === []) {
            throw new AiGenerationException('لم يُنتج الذكاء الاصطناعي أي أسئلة صالحة. حاول بصياغة مختلفة.');
        }

        return $questions;
    }

    /** @param list<QuestionType> $types */
    private function buildPrompt(string $topic, int $count, string $difficulty, string $language, array $types): string
    {
        $typeList = implode(', ', array_map(static fn (QuestionType $t): string => $t->value, $types));

        return Str::of('You are an exam question generator. Produce a JSON array (no markdown, no prose) of exactly ')
            ->append((string) $count)
            ->append(' exam questions about "'.$topic.'" in language "'.$language.'" at "'.$difficulty.'" difficulty. ')
            ->append('Only use these question types: '.$typeList.'. ')
            ->append('Each array item must be an object with keys: type (one of '.$typeList.'), prompt (string), ')
            ->append('options (array of strings, omit or null for short_answer, exactly ["صحيح","خطأ"] for true_false), ')
            ->append('correct_answer (array — indexes into options for multiple_choice/true_false, array of indexes for ')
            ->append('multiple_select, a single reference string for short_answer), explanation (string), points (number).')
            ->toString();
    }
}
