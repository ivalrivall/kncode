<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('freelancers')]
#[Fillable(['user_id', 'fullname', 'headline', 'bio', 'experience_years', 'hourly_rate', 'availability', 'location', 'rating_avg', 'total_reviews'])]
class Freelance extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'rating_avg' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'freelancer_skills', 'freelancer_id', 'skill_id')
            ->withPivot('level')
            ->withTimestamps();
    }

    public function portfolios()
    {
        return $this->hasMany(Portfolio::class, 'freelancer_id');
    }

    public function workApplications()
    {
        return $this->hasMany(WorkApplication::class, 'freelancer_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'freelancer_id');
    }
}
