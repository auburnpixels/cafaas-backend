<?php

declare(strict_types=1);

namespace App\Http\Resources\Internal\Operator;

use App\Models\OperatorApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OperatorApiKey
 */
final class ApiKeyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // If secret is available (decrypted automatically by model cast), use it.
        // Otherwise fall back to legacy logic (plain text if recently created, or masked hash)
        // Note: $this->key is the HASH from DB unless we manually set it to plain text on creation.
        $plainKey = $this->secret ?? ($this->wasRecentlyCreated ? $this->key : null);

        return [
            'id' => $this->id,
            'operator_id' => $this->operator_id,
            'name' => $this->name,
            'key' => $plainKey ?? ('****'.substr($this->key, -8)),
            'is_revealed' => ! empty($plainKey), // Helper for frontend to know if full key is available
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
