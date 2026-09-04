<?php

namespace App\Modules\Workflow\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create channel message templates (WorkflowMessageTemplate).
 * Used by FE "+ Add to Template" → POST /workflow/templates.
 */
class StoreWorkflowMessageTemplateRequest extends FormRequest
{
    /** @var list<string> */
    public const SUPPORTED_CHANNELS = [
        'email',
        'sms',
        'whatsapp',
        'push',
        'ai',
        'voice',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('channel') && is_string($this->input('channel'))) {
            $this->merge([
                'channel' => strtolower(trim($this->input('channel'))),
            ]);
        }

        if ($this->has('status') && is_string($this->input('status'))) {
            $this->merge([
                'status' => strtolower(trim($this->input('status'))),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'string', Rule::in(self::SUPPORTED_CHANNELS)],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['active', 'inactive', 'draft'])],
            'locale' => ['sometimes', 'nullable', 'string', 'max:16'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'subject' => ['sometimes', 'nullable', 'string'],
            'title' => ['sometimes', 'nullable', 'string'],
            'body' => ['sometimes', 'nullable', 'string'],
            'message' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:50'],
            'temperature' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:2'],
            'buttons' => ['sometimes', 'nullable'],
            'variables' => ['sometimes', 'nullable', 'array'],
            // Ownership fields are ignored by the service; listed only so they never pass into create mapping.
            'organization_id' => ['prohibited'],
            'hospital_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'owner_id' => ['prohibited'],
            'created_by' => ['prohibited'],
            'updated_by' => ['prohibited'],
            'id' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $channel = (string) $this->input('channel');
            $body = trim((string) ($this->input('body') ?? ''));
            $message = trim((string) ($this->input('message') ?? ''));
            $content = $body !== '' ? $body : $message;

            if ($content === '') {
                $validator->errors()->add(
                    'body',
                    'A non-empty body or message is required for this template.'
                );
            }

            if ($channel === 'email') {
                $subject = trim((string) ($this->input('subject') ?? ''));
                if ($subject === '') {
                    $validator->errors()->add('subject', 'Email templates require a subject.');
                }
            }

            if ($channel === 'push') {
                $title = trim((string) ($this->input('title') ?? ''));
                if ($title === '') {
                    $validator->errors()->add('title', 'Push templates require a title.');
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'channel.in' => 'The selected channel is invalid. Supported: '.implode(', ', self::SUPPORTED_CHANNELS),
            'organization_id.prohibited' => 'organization_id cannot be set by the client.',
            'hospital_id.prohibited' => 'hospital_id cannot be set by the client.',
        ];
    }
}
