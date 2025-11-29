<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom validation exception handler
        $exceptions->render(function (ValidationException $e) {
            // Get all validation errors
            $errors = $e->errors();

            // Get the first error message for the message field
            $firstField = array_key_first($errors);
            $firstError = $errors[$firstField][0] ?? 'Validation failed';

            return response()->json([
                'status' => false,
                'message' => $firstError,
                'errors' => $errors,
            ], 422);
        });
    })->create();
