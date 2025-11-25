<?php

declare(strict_types=1);

namespace App\Http\Resources\Internal\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $access_token
 * @property string $token_type
 * @property int $expires_in
 */
final class RefreshTokenResource extends JsonResource
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
        ];
    }
}
