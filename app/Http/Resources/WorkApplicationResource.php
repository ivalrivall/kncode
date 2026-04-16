<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WorkApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_id' => $this->work_id,
            'freelancer_id' => $this->freelancer_id,
            'cover_letter' => $this->cover_letter,
            'proposed_rate' => $this->proposed_rate,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'work' => new WorkResource($this->whenLoaded('work')),
            'freelance' => new FreelanceResource($this->whenLoaded('freelance')),
        ];
    }
}
