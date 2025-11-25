<?php

declare(strict_types=1);

namespace App\Http\Resources\Internal\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read User $resource
 */
final class MeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'id' => $this->resource->id,
                'name' => $this->resource->name,
                'email' => $this->resource->email,
                'role' => $this->resource->role,
                'operator_id' => $this->resource->operator_id,
                'created_at' => $this->resource->created_at->toIso8601String(),
            ],
        ];
    }
}
