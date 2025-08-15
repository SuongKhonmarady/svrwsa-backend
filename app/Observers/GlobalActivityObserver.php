<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

class GlobalActivityObserver
{
    public function created(Model $model)
    {
        $this->logActivityAsync('create', $model, null, $model->toArray());
    }

    public function updated(Model $model)
    {
        $this->logActivityAsync('update', $model, $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model)
    {
        $this->logActivityAsync('delete', $model, $model->toArray(), null);
    }

    protected function logActivityAsync($action, Model $model, $oldData = null, $newData = null)
    {
        $user = auth()->user();

        // Only track admin/staff
        if (!$user || !in_array($user->role, ['admin', 'staff'])) {
            return;
        }

        try {
            // Create activity log asynchronously without blocking the main process
            $ip = Request::ip();
            
            // Get location quickly or set default
            $location = $this->getLocationQuickly($ip);

            // Use a direct database insert without Eloquent for better performance
            \DB::table('activity_logs')->insert([
                'user_id'    => $user->id,
                'role'       => $user->role,
                'action'     => $action,
                'table_name' => $model->getTable(),
                'record_id'  => $model->id,
                'ip_address' => $ip,
                'location'   => $location,
                'user_agent' => Request::header('User-Agent'),
                'old_data'   => $oldData ? json_encode($oldData) : null,
                'new_data'   => $newData ? json_encode($newData) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log the error but don't let it break the main process
            Log::warning('Activity logging failed: ' . $e->getMessage(), [
                'action' => $action,
                'model' => get_class($model),
                'user_id' => $user->id ?? null
            ]);
        }
    }

    protected function getLocationQuickly($ip)
    {
        // For local/private IPs, return immediately without external calls
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost']) || 
            preg_match('/^192\.168\./', $ip) || 
            preg_match('/^10\./', $ip) || 
            preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip)) {
            return 'Local Network (' . $ip . ')';
        }

        // For public IPs, try a single quick lookup with very short timeout
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 1, // Very short timeout
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=city,country,status", false, $context);
            
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['status']) && $data['status'] === 'success') {
                    $city = $data['city'] ?? '';
                    $country = $data['country'] ?? '';
                    if ($city || $country) {
                        return trim($city . ', ' . $country, ', ');
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently handle errors and fall back to IP
        }
        
        // Fallback to just showing the IP if location lookup fails
        return 'IP: ' . $ip;
    }
}

