<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('work_applications')]
#[Fillable(['work_id', 'freelancer_id', 'cover_letter', 'proposed_rate', 'status'])]
class WorkApplication extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'proposed_rate' => 'decimal:2',
        ];
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function freelance()
    {
        return $this->belongsTo(Freelance::class, 'freelancer_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
