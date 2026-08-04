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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectTo(
            guests: fn (\Illuminate\Http\Request $request) => ($request->is('api/*') || $request->expectsJson()) ? null : route('login')
        );

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $previous = $e->getPrevious();
                if ($previous instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $modelName = class_basename($previous->getModel());
                    return response()->json([
                        'message' => "{$modelName} not found."
                    ], 404);
                }

                return response()->json([
                    'message' => 'The requested endpoint or resource was not found.'
                ], 404);
            }
        });

        $exceptions->render(function (\Modules\Core\Exceptions\CannotDeleteException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'details' => $e->getDetails()
                ], 409);
            }
        });

        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            if ($e->getCode() == "23000" && str_contains($e->getMessage(), 'foreign key constraint')) {
                if ($request->is('api/*') || $request->expectsJson()) {
                    if ($request->isMethod('delete')) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'لا يمكن حذف السجل لارتباطه بسجلات أخرى في النظام.'
                        ], 409);
                    }

                    return response()->json([
                        'status' => 'error',
                        'message' => 'أحد المعرفات الممررة (ID) غير موجود أو مرتبط ببيانات مفتاح أجنبي غير صحيحة.'
                    ], 422);
                }
            }
        });
    })->create();
