<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Operator;

use Illuminate\Foundation\Http\FormRequest;

final class CloseCompetitionRequest extends FormRequest
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
            // No body parameters needed - competition identified by URL parameter
        ];
    }
}





