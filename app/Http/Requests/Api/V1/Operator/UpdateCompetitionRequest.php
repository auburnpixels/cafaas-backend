<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Operator;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCompetitionRequest extends FormRequest
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
        $externalId = $this->route('externalId');

        // Find current competition to get its ID for unique rule
        $competition = \App\Models\Competition::where('operator_id', $operatorId)
            ->where('external_id', $externalId)
            ->first();

        $competitionId = $competition?->id ?? 'NULL';

        return [
            'external_id' => [
                'sometimes',
                'string',
                'max:255',
                'unique:competitions,external_id,'.$competitionId.',id,operator_id,'.$operatorId,
            ],
            'name' => 'sometimes|string|max:255',
            'max_tickets' => 'sometimes|integer|min:1',
            'draw_at' => 'sometimes|date',
            'status' => 'sometimes|in:pending,active,ended,closed,awaiting_draw,completed',
            'prizes' => 'sometimes|array|min:1',
            'prizes.*.external_id' => 'required_with:prizes|string|max:255',
            'prizes.*.name' => 'required_with:prizes|string|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'external_id.string' => 'The external ID must be a string.',
            'external_id.max' => 'The external ID must not exceed 255 characters.',
            'external_id.unique' => 'A competition with this external ID already exists for your operator.',
            'name.string' => 'The competition name must be a string.',
            'name.max' => 'The competition name must not exceed 255 characters.',
            'max_tickets.integer' => 'The maximum number of tickets must be an integer.',
            'max_tickets.min' => 'The maximum number of tickets must be at least 1.',
            'draw_datetime.date' => 'The draw datetime must be a valid date.',
            'status.in' => 'The status is invalid.',
            'prizes.array' => 'Prizes must be an array.',
            'prizes.min' => 'At least one prize is required.',
            'prizes.*.external_id.required_with' => 'Each prize must have an external prize ID.',
            'prizes.*.name.required_with' => 'Each prize must have a name.',
        ];
    }

    /**
     * Validate that prize external IDs are unique within this competition.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('prizes')) {
                $prizes = $this->input('prizes', []);
                $externalIds = array_column($prizes, 'external_id');

                if (count($externalIds) !== count(array_unique($externalIds))) {
                    $validator->errors()->add('prizes', 'Prize external IDs must be unique within the competition.');
                }
            }
        });
    }
}
