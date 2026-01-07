<?php

declare(strict_types=1);

use App\Http\Middleware\AbsoluteSessionTimeout;
use App\Http\Middleware\ConcurrentSessionLimit;
use App\Http\Middleware\RequireAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA 認証のために api ミドルウェアグループにセッションを追加
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // カスタムミドルウェアをエイリアス登録
        $middleware->alias([
            'absolute.timeout' => AbsoluteSessionTimeout::class,
            'concurrent.session' => ConcurrentSessionLimit::class,
            'require.admin' => RequireAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
