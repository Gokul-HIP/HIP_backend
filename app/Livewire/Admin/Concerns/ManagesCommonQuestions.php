<?php

namespace App\Livewire\Admin\Concerns;

use App\Support\CommonQuestions;

trait ManagesCommonQuestions
{
    /** @var array<int, array{question: string, answer: string}> */
    public array $commonQuestions = [];

    public function addCommonQuestionRow(): void
    {
        $this->commonQuestions[] = ['question' => '', 'answer' => ''];
    }

    public function removeCommonQuestionRow(int $index): void
    {
        unset($this->commonQuestions[$index]);
        $this->commonQuestions = array_values($this->commonQuestions);

        if ($this->commonQuestions === []) {
            $this->resetCommonQuestions();
        }
    }

    protected function resetCommonQuestions(): void
    {
        $this->commonQuestions = CommonQuestions::defaultRows();
    }

    protected function loadCommonQuestions(mixed $stored): void
    {
        $normalized = CommonQuestions::normalize($stored);
        $this->commonQuestions = $normalized !== [] ? $normalized : CommonQuestions::defaultRows();
    }

    protected function commonQuestionsPayload(): array
    {
        return CommonQuestions::normalize($this->commonQuestions);
    }
}
