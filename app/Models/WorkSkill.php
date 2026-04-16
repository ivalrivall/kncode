<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('work_skills')]
#[Guarded([])]
class WorkSkill extends Model
{
    protected function casts(): array
    {
        return [
            'work_id' => 'integer',
            'skill_id' => 'integer',
        ];
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}
