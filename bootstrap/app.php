<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Exceptions\InvalidOrderException;

// PHP 8.5 deprecates PDO::MYSQL_ATTR_SSL_CA, which Laravel 11's own
// vendor/laravel/framework/config/database.php still uses. Config files are
// loaded by the LoadConfiguration bootstrapper, which runs BEFORE
// HandleExceptions resets error_reporting(-1) -- so this only silences
// deprecations during config loading, not for the rest of the request.
error_reporting(error_reporting() & ~E_DEPRECATED);

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // identitas user ikut tercatat di semua log request ini
        $middleware->web(append: [
            \App\Http\Middleware\LogUserContext::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'locale' => \App\Http\Middleware\LanguageSwitcher::class,
            'notification' => \App\Http\Middleware\NotificationMiddleware::class,
            'flow_access' => \App\Http\Middleware\FlowAccessMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (InvalidOrderException $e, Request $request) {
            return response()->view('errors.403', [], 403);
        });
    })
    ->create();
