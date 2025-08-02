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
    ];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
