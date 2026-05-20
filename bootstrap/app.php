<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\UserMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminAuth::class,
            'user' => UserMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'ssl-success',
            'ssl-fail',
            'ssl-cancel',
            'ssl-ipn',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
