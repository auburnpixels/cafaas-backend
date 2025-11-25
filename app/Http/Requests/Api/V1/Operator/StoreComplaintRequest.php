<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Operator;

use Illuminate\Foundation\Http\FormRequest;

final class StoreComplaintRequest extends FormRequest
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
            'competition_external_id' => 'required|string',
            'complainant_reference' => 'required|string',
            'category' => 'required|string|in:draw_fairness,entry_issue,prize_issue,payment_issue,other',
            'description' => 'required|string|max:2000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'competition_external_id.required' => 'The competition external ID is required.',
            'complainant_reference.required' => 'The complainant reference is required.',
            'category.required' => 'The complaint category is required.',
            'category.in' => 'Invalid complaint category.',
            'description.required' => 'The complaint description is required.',
            'description.max' => 'The complaint description must not exceed 2000 characters.',
        ];
    }
}
