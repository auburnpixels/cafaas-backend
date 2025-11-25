<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Operator;

use Illuminate\Foundation\Http\FormRequest;

final class StoreFreeEntryRequest extends FormRequest
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
            'external_id' => 'required|string',
            'user_reference' => 'required|string',
            'reason' => 'nullable|string|in:postal,promotional,compensation',
            'question_answered_correctly' => 'required|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'external_id.required' => 'The external entry ID is required.',
            'user_reference.required' => 'The user reference is required.',
            'reason.in' => 'The reason must be one of: postal, promotional, compensation.',
            'question_answered_correctly.required' => 'The question answered correctly field is required.',
            'question_answered_correctly.boolean' => 'The question answered correctly field must be true or false.',
        ];
    }
}
