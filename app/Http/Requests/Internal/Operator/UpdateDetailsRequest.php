<?php

declare(strict_types=1);

namespace App\Http\Requests\Internal\Operator;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDetailsRequest extends FormRequest
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
            'name' => 'required|string|min:3|max:255',
            'url' => 'nullable|url|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The operator name is required.',
            'name.min' => 'The operator name must be at least 3 characters.',
            'name.max' => 'The operator name must not exceed 255 characters.',
            'url.url' => 'The website must be a valid URL.',
            'url.max' => 'The website URL must not exceed 255 characters.',
        ];
    }
}






