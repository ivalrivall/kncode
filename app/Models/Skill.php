<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('skills')]
#[Guarded([])]
class Skill extends Model
{
    use HasFactory;

    public function freelances()
    {
        return $this->belongsToMany(Freelance::class, 'freelancer_skills', 'skill_id', 'freelancer_id')
            ->withPivot('level')
            ->withTimestamps();
    }

    public function works()
    {
        return $this->belongsToMany(Work::class, 'work_skills')
            ->withTimestamps();
    }
}
