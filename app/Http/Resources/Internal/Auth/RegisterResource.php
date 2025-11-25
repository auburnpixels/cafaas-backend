<?php

declare(strict_types=1);

namespace App\Http\Resources\Internal\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{access_token: string, expires_in: int, user: \App\Models\User, operator: \App\Models\Operator} $resource
 */
final class RegisterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->resource['access_token'],
            'token_type' => 'bearer',
            'expires_in' => $this->resource['expires_in'],
            'user' => [
                'id' => $this->resource['user']->id,
                'uuid' => $this->resource['user']->uuid,
                'name' => $this->resource['user']->name,
                'email' => $this->resource['user']->email,
                'role' => $this->resource['user']->role,
                'operator_id' => $this->resource['user']->operator_id,
            ],
            'operator' => [
                'id' => $this->resource['operator']->id,
                'name' => $this->resource['operator']->name,
                'slug' => $this->resource['operator']->slug,
                'is_active' => $this->resource['operator']->is_active,
            ],
        ];
    }
}





