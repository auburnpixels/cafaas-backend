<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Operator;

use Illuminate\Foundation\Http\FormRequest;

final class RunDrawRequest extends FormRequest
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
            'prize_id' => 'nullable|string|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prize_id.string' => 'The prize ID must be a string.',
            'prize_id.max' => 'The prize ID must not exceed 255 characters.',
        ];
    }
}
