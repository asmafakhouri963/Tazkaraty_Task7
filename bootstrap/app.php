<?php

use App\Traits\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error($e->errors(), 'The entered data is incorrect!', 422);
            }
        });


        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(['global' => ['The requested resource was not found.']],
                                                        'The requested resource was not found.',
                                                        404);
            }
        });


        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(['database' => ['A database operation failed.']],
                                          'A database error occurred. Please try again later.',
                                          500 );
            }
        });

        $exceptions->render(function (Throwable $e, Request $request){
            if ($request->is('api/*') || $request->wantsJson()) {
                if ($e instanceof HttpExceptionInterface) {
                    $msg = $e->getMessage() ?: 'HTTP Error occurred.';
                    return ApiResponse::error(['http' => [$msg]],
                                                $msg  ,
                                                $e->getStatusCode());
                }
                return ApiResponse::error(['server' => ['An unexpected internal error occurred.']],
                                            'An unexpected server error occurred. Please try again later.' ,
                                            500);
            }
        });








    })->create();
