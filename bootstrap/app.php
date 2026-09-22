<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'user' => \App\Http\Middleware\CheckUserRole::class,
        'admin' => \App\Http\Middleware\CheckAdminRole::class,
        'lecturer' => \App\Http\Middleware\CheckLecturerRole::class,
    ]);

    // Ghi nhận hoạt động cuối của người dùng (trạng thái online/offline) — nhẹ, fail-open.
    $middleware->web(append: [
        \App\Http\Middleware\UpdateLastSeen::class,
    ]);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
