<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Operator;

use Illuminate\Foundation\Http\FormRequest;

final class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'url' => 'required|url',
            'events' => 'required|array',
            'events.*' => 'string|in:draw.completed,audit.published,raffle.created,raffle.published,entry.created,complaint.submitted',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'The webhook URL is required.',
            'url.url' => 'The webhook URL must be a valid URL.',
            'events.required' => 'At least one event must be specified.',
            'events.array' => 'Events must be an array.',
            'events.*.in' => 'Invalid event type specified.',
        ];
    }
}
