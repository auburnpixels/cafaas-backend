<?php

declare(strict_types=1);

namespace App\Http\Resources\Internal\Operator;

use App\Http\Resources\Api\V1\CompetitionResource;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{user: User, operator: Operator, compliance: array} $resource
 */
final class DashboardResource extends JsonResource
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
                'id' => $this->resource['user']->id,
                'name' => $this->resource['user']->name,
                'email' => $this->resource['user']->email,
                'role' => $this->resource['user']->role,
            ],
            'operator' => [
                'id' => $this->resource['operator']->id,
                'name' => $this->resource['operator']->name,
                'slug' => $this->resource['operator']->slug,
                'url' => $this->resource['operator']->url,
                'is_active' => $this->resource['operator']->is_active,
            ],
            'compliance' => $this->resource['compliance'],
            'recent_competitions' => isset($this->resource['recent_competitions']) 
                ? CompetitionResource::collection($this->resource['recent_competitions']) 
                : [],
        ];
    }
}
