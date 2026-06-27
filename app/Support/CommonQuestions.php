<?php

namespace App\Support;

class CommonQuestions
{
    /**
     * @param  mixed  $items
     * @return array<int, array{question: string, answer: string}>
     */
    public static function normalize(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(function ($item) {
                if (! is_array($item)) {
                    return null;
                }

                return [
                    'question' => trim((string) ($item['question'] ?? '')),
                    'answer' => trim((string) ($item['answer'] ?? '')),
                ];
            })
            ->filter(fn (?array $item) => $item && ($item['question'] !== '' || $item['answer'] !== ''))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    public static function defaultRows(int $count = 1): array
    {
        return array_fill(0, max(1, $count), ['question' => '', 'answer' => '']);
    }
}
