<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinicityRefresh extends Model
{
    use HasFactory;
    
    protected $table = 'finicity_refreshes';
    protected $fillable = [
        'status',
        'finicity_refreshable_id',
        'finicity_refreshable_type',
        'error',
        'update_type'
    ];

    public function finicity_refreshable()
    {
        return $this->morphTo();
    }
}
