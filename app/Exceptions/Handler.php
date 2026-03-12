<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use ErrorException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use BadMethodCallException;
use Error;

class Handler extends ExceptionHandler
{


    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // error_reporting(0);

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                'message' => 'File Not Found.'
                ], 404);
            }
            return redirect(config('app.error_404'));
        });
    }

    public function render($request, Throwable $e)
    {
        // error_reporting(0);
        if (Str::contains($request->url(), '/api')) {
            if ($e instanceof ErrorException)
                return response()->apiResponse($e);
            elseif ($e instanceof MethodNotAllowedHttpException)
                return response()->apiResponse($e);
            elseif ($e instanceof BadMethodCallException)
                return response()->apiResponse($e);
            elseif ($e instanceof Error)
                return response()->apiResponse($e);
        }

        return parent::render($request, $e);
    }
}