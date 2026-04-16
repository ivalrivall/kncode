<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('portfolios')]
#[Guarded([])]
class Portfolio extends Model
{
    use HasFactory;

    public function freelance()
    {
        return $this->belongsTo(Freelance::class, 'freelancer_id');
    }
}
