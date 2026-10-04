<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'topic' => ['required', Rule::in(array_keys(config('portfolio.contact_topics')))],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            // Asked only when the topic is a project, and optional even then.
            'budget' => ['nullable', Rule::in(array_keys(config('portfolio.contact_budgets')))],
            'timeline' => ['nullable', Rule::in(array_keys(config('portfolio.contact_timelines')))],
            // Honeypot: hidden from people, irresistible to bots.
            'website' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please tell me your name.',
            'email.required' => "I'll need an email address to reply to.",
            'email.email' => "That email address doesn't look right.",
            'message.required' => 'The message is empty.',
            'message.min' => 'Could you add a little more detail?',
        ];
    }

    /**
     * The fields to store: budget and timeline are kept only for a project enquiry.
     *
     * @return array<string, string|null>
     */
    public function details(): array
    {
        $extras = $this->input('topic') === 'project' ? ['budget', 'timeline'] : [];

        return $this->safe()->only(['name', 'email', 'topic', 'message', ...$extras]);
    }

    public function isSpam(): bool
    {
        return filled($this->input('website'));
    }
}
