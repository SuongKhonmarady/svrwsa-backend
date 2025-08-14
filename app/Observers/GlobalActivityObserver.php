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
        // If it's a local IP, try to get the real public IP
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost']) || 
            preg_match('/^192\.168\./', $ip) || 
            preg_match('/^10\./', $ip) || 
            preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip)) {
            
            // Try to get real public IP for development testing
            $realIp = $this->getRealPublicIP();
            if ($realIp && $realIp !== $ip) {
                return $this->getLocationByIP($realIp) . ' (via public IP: ' . $realIp . ')';
            }
            
            return 'Local Network (' . $ip . ')';
        }

        return $this->getLocationByIP($ip);
    }

    protected function getRealPublicIP()
    {
        try {
            // Try multiple services to get public IP
            $services = [
                'https://api.ipify.org',
                'https://icanhazip.com',
                'https://ipecho.net/plain',
                'https://myexternalip.com/raw'
            ];

            foreach ($services as $service) {
                $ip = @file_get_contents($service, false, stream_context_create([
                    'http' => ['timeout' => 3]
                ]));
                
                if ($ip && filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return trim($ip);
                }
            }
        } catch (\Exception $e) {
            // Silently handle errors
        }
        
        return null;
    }

    protected function getLocationByIP($ip)
    {
        try {
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=city,country,status", false, stream_context_create([
                'http' => ['timeout' => 5]
            ]));
            
            if ($response) {
                $data = json_decode($response);
                if (isset($data->status) && $data->status === 'success') {
                    $city = $data->city ?? '';
                    $country = $data->country ?? '';
                    if ($city || $country) {
                        return trim($city . ', ' . $country, ', ');
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently handle errors
        }
        
        return 'Unknown Location';
    }
}

