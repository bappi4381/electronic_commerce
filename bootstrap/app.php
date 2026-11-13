<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\UserMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminAuth::class,
            'user' => UserMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'set_locale' => \App\Http\Middleware\SetLocale::class,
            'courier.webhook' => \App\Http\Middleware\VerifyCourierWebhook::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsNotBlocked::class,
        ]);
        // Gateway callbacks are posted cross-site to /{locale}/ssl-*
        $middleware->validateCsrfTokens(except: [
            '*/ssl-success',
            '*/ssl-fail',
            '*/ssl-cancel',
            '*/ssl-ipn',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
