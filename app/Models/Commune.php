<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commune extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'district_id',
    ];

    /**
     * Get the district that owns the commune.
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Get the villages for the commune.
     */
    public function villages()
    {
        return $this->hasMany(Village::class);
    }
}
