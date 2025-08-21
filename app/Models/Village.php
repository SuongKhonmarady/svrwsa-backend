<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'commune_id',
    ];

    /**
     * Get the commune that owns the village.
     */
    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }
}
