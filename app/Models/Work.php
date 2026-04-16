<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('works')]
#[Fillable(['company_id', 'title', 'description', 'budget_min', 'budget_max', 'type', 'experience_level', 'deadline_date', 'status'])]
class Work extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'deadline_date' => 'date',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function applications()
    {
        return $this->hasMany(WorkApplication::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'work_skills')
            ->withTimestamps();
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
