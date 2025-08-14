<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use App\Models\ActivityLog;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Empty - we'll register manually to avoid duplicates
    ];

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    public function boot(): void
    {
        // Register login/logout event listeners manually
        Event::listen(Login::class, function (Login $event) {
            if (!in_array($event->user->role, ['admin', 'staff'])) {
                return;
            }

            ActivityLog::create([
                'user_id'    => $event->user->id,
                'role'       => $event->user->role,
                'action'     => 'login',
                'table_name' => null,
                'record_id'  => null,
                'ip_address' => Request::ip(),
                'location'   => $this->getLocation(Request::ip()),
                'user_agent' => Request::header('User-Agent'),
                'old_data'   => null,
                'new_data'   => null,
            ]);
        });

        Event::listen(Logout::class, function (Logout $event) {
            if (!in_array($event->user->role, ['admin', 'staff'])) {
                return;
            }

            ActivityLog::create([
                'user_id'    => $event->user->id,
                'role'       => $event->user->role,
                'action'     => 'logout',
                'table_name' => null,
                'record_id'  => null,
                'ip_address' => Request::ip(),
                'location'   => $this->getLocation(Request::ip()),
                'user_agent' => Request::header('User-Agent'),
                'old_data'   => null,
                'new_data'   => null,
            ]);
        });
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
