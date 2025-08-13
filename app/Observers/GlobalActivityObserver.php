<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class GlobalActivityObserver
{
    public function created(Model $model)
    {
        $this->logActivity('create', $model, null, $model->toArray());
    }

    public function updated(Model $model)
    {
        $this->logActivity('update', $model, $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model)
    {
        $this->logActivity('delete', $model, $model->toArray(), null);
    }

    protected function logActivity($action, Model $model, $oldData = null, $newData = null)
    {
        $user = auth()->user();

        // Only track admin/staff
        if (!$user || !in_array($user->role, ['admin', 'staff'])) {
            return;
        }

        ActivityLog::create([
            'user_id'    => $user->id,
            'role'       => $user->role,
            'action'     => $action,
            'table_name' => $model->getTable(),
            'record_id'  => $model->id,
            'ip_address' => Request::ip(),
            'location'   => $this->getLocation(Request::ip()),
            'user_agent' => Request::header('User-Agent'),
            'old_data'   => $oldData,
            'new_data'   => $newData,
        ]);
    }

    protected function getLocation($ip)
    {
        try {
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=city,country");
            if ($response) {
                $data = json_decode($response);
                return ($data->city ?? '') . ', ' . ($data->country ?? '');
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }
}

