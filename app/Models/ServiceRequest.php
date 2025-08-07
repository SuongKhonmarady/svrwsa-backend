<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'service_type',
        'details',
        'address',
        'status_id',
        'id_card',
        'family_book',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id_card' => 'array',
        'family_book' => 'array',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
