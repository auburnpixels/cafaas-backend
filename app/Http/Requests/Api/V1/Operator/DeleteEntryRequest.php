<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Operator;

use Illuminate\Foundation\Http\FormRequest;

final class DeleteEntryRequest extends FormRequest
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
            'reason' => 'required|string|in:refund,cancellation,duplicate,fraud,other',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'A reason for deletion is required.',
            'reason.in' => 'Invalid deletion reason. Must be one of: refund, cancellation, duplicate, fraud, other.',
            'notes.max' => 'Notes must not exceed 500 characters.',
        ];
    }
}





