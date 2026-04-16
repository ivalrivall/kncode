<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('freelancer_skills')]
#[Guarded([])]
class FreelanceSkill extends Model
{
    protected function casts(): array
    {
        return [
            'freelancer_id' => 'integer',
            'skill_id' => 'integer',
        ];
    }

    public function freelance()
    {
        return $this->belongsTo(Freelance::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}
