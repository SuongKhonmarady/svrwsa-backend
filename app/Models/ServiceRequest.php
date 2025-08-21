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
        'phone',
        'service_type',
        'details',
        'status_id',
        'id_card',
        'family_book',
        'family_members',
        'female_members',
        'village',
        'commune_id',
        'district_id',
        'province_id',
        'occupation_id',
        'usage_type_id',
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

    /**
     * Get the status associated with the service request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Get the village name (user input text).
     *
     * @return string
     */
    public function getVillageAttribute($value)
    {
        return $value;
    }

    /**
     * Get the commune associated with the service request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    /**
     * Get the district associated with the service request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Get the province associated with the service request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get the occupation associated with the service request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function occupation()
    {
        return $this->belongsTo(Occupation::class);
    }

    /**
     * Get the usage type associated with the service request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function usageType()
    {
        return $this->belongsTo(UsageType::class);
    }
}
