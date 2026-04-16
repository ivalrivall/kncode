<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FreelanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fullname' => $this->fullname,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'experience_years' => $this->experience_years,
            'hourly_rate' => $this->hourly_rate,
            'availability' => $this->availability,
            'location' => $this->location,
            'rating_avg' => $this->rating_avg,
            'total_reviews' => $this->total_reviews,
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'portfolios' => PortfolioResource::collection($this->whenLoaded('portfolios')),
        ];
    }
}
