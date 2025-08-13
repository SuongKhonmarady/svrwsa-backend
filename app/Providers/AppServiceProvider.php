<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use App\Models\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use App\Observers\GlobalActivityObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        
        $modelPath = app_path('Models');
        $namespace = 'App\\Models\\';

        if (File::exists($modelPath)) {
            foreach (File::files($modelPath) as $file) {
                $modelClass = $namespace . pathinfo($file->getFilename(), PATHINFO_FILENAME);

                if (class_exists($modelClass) && is_subclass_of($modelClass, Model::class)) {
                    $reflection = new ReflectionClass($modelClass);

                    // Skip abstract classes
                    if (!$reflection->isAbstract()) {
                        $modelClass::observe(GlobalActivityObserver::class);
                    }
                }
            }
        }
    }
}
