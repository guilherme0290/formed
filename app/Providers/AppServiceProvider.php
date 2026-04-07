<?php

namespace App\Providers;

use App\Observers\ModelActivityObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

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
        $this->registerModelActivityObservers();
        $this->registerAuthActivityLogs();
    }

    private function registerModelActivityObservers(): void
    {
        foreach ($this->discoverModelClasses() as $modelClass) {
            $modelClass::observe(ModelActivityObserver::class);
        }
    }

    /**
     * @return array<int, class-string<Model>>
     */
    private function discoverModelClasses(): array
    {
        $modelsPath = app_path('Models');
        if (!is_dir($modelsPath)) {
            return [];
        }

        $classes = [];
        foreach (File::allFiles($modelsPath) as $file) {
            $relativePath = str_replace($modelsPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $className = 'App\\Models\\' . str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                $relativePath
            );

            if (!class_exists($className)) {
                continue;
            }

            if (!is_subclass_of($className, Model::class)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if ($reflection->isAbstract()) {
                continue;
            }

            $classes[] = $className;
        }

        return array_values(array_unique($classes));
    }

    private function registerAuthActivityLogs(): void
    {
        Event::listen(Login::class, function (Login $event) {
            activity('auth')
                ->causedBy($event->user)
                ->event('login')
                ->withProperties([
                    'guard' => $event->guard,
                    'ip' => request()?->ip(),
                    'url' => request()?->fullUrl(),
                ])
                ->log('login');
        });

        Event::listen(Logout::class, function (Logout $event) {
            activity('auth')
                ->causedBy($event->user)
                ->event('logout')
                ->withProperties([
                    'guard' => $event->guard,
                    'ip' => request()?->ip(),
                    'url' => request()?->fullUrl(),
                ])
                ->log('logout');
        });

        Event::listen(Failed::class, function (Failed $event) {
            activity('auth')
                ->event('login_failed')
                ->withProperties([
                    'guard' => $event->guard,
                    'email' => $event->credentials['email'] ?? null,
                    'login' => $event->credentials['login'] ?? null,
                    'ip' => request()?->ip(),
                    'url' => request()?->fullUrl(),
                ])
                ->log('login_failed');
        });
    }
}
