<?php

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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\LogRoute::class,
        ]);

        $middleware->alias([
            // 'jwt.auth' => \PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate::class,
            'jwt.refresh' => \PHPOpenSourceSaver\JWTAuth\Http\Middleware\RefreshToken::class,
            'jwt.check' => \PHPOpenSourceSaver\JWTAuth\Http\Middleware\Check::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // N'ajoutez pas le middleware jwt.auth ici
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException $e, $request) {
            return response()->json(['error' => 'Token is Invalid'], 401);
        });

        $exceptions->renderable(function (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e, $request) {
            return response()->json(['error' => 'Token is Expired'], 401);
        });

        $exceptions->renderable(function (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $e, $request) {
            return response()->json(['error' => 'Token not provided'], 401);
        });
    })
    ->create();
