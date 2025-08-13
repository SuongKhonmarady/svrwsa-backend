<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'role', 'action', 'table_name', 'record_id',
        'ip_address', 'location', 'user_agent', 'old_data', 'new_data'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];
}
