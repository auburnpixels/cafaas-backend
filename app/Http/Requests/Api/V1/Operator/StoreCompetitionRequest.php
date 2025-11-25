<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Operator;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCompetitionRequest extends FormRequest
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
        $operatorId = $this->operator?->id;

        return [
            'external_id' => [
                'required',
                'string',
                'max:255',
                'unique:competitions,external_id,NULL,id,operator_id,'.$operatorId,
            ],
            'name' => 'required|string|max:255',
            'max_tickets' => 'required|integer|min:1',
            'draw_at' => 'required|date',
            'status' => 'nullable|in:pending,active,ended',
            'prizes' => 'required|array|min:1',
            'prizes.*.external_id' => 'required|string|max:255',
            'prizes.*.name' => 'required|string|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'external_id.required' => 'The external ID is required.',
            'external_id.max' => 'The external ID must not exceed 255 characters.',
            'external_id.unique' => 'A competition with this external ID already exists for your operator.',
            'name.required' => 'The competition name is required.',
            'max_tickets.required' => 'The maximum number of tickets is required.',
            'max_tickets.min' => 'The maximum number of tickets must be at least 1.',
            'draw_datetime.required' => 'The draw datetime is required.',
            'draw_datetime.date' => 'The draw datetime must be a valid date.',
            'prizes.required' => 'At least one prize is required.',
            'prizes.min' => 'At least one prize is required.',
            'prizes.*.external_id.required' => 'Each prize must have an external prize ID.',
            'prizes.*.name.required' => 'Each prize must have a name.',
        ];
    }

    /**
     * Validate that prize external IDs are unique within this competition.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $prizes = $this->input('prizes', []);
            $externalIds = array_column($prizes, 'external_id');

            if (count($externalIds) !== count(array_unique($externalIds))) {
                $validator->errors()->add('prizes', 'Prize external IDs must be unique within the competition.');
            }
        });
    }
}
