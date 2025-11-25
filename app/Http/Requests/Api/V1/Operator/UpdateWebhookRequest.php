<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Operator;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateWebhookRequest extends FormRequest
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
            'url' => 'sometimes|url',
            'events' => 'sometimes|array',
            'events.*' => 'string|in:draw.completed,audit.published,raffle.created,raffle.published,entry.created,complaint.submitted',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.url' => 'The webhook URL must be a valid URL.',
            'events.array' => 'Events must be an array.',
            'events.*.in' => 'Invalid event type specified.',
            'is_active.boolean' => 'The is_active field must be a boolean.',
        ];
    }
}
