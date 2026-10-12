<?php

namespace App\Http\Resources\Pharmacy;

use App\Models\HIPUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacyOrderCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $author = $this->author_id
            ? HIPUser::query()->find($this->author_id)
            : null;

        $name = trim((string) (($author->first_name ?? '').' '.($author->last_name ?? '')));

        return [
            'id' => (int) $this->id,
            'author_type' => $this->author_type,
            'author_name' => $name !== '' ? $name : null,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
            'read' => $this->read_at !== null,
        ];
    }
}
