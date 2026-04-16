<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WorkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'title' => $this->title,
            'description' => $this->description,
            'budget_min' => $this->budget_min,
            'budget_max' => $this->budget_max,
            'type' => $this->type,
            'experience_level' => $this->experience_level,
            'status' => $this->status,
            'deadline_date' => $this->deadline_date?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'company' => new CompanyResource($this->whenLoaded('company')),
        ];
    }
}
