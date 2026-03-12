<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Response::macro('apiResponse', function (\Throwable $e, array $data = [], string $message = '', int $code = 500) {
            $exception = $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine();

            $data['status'] = false;

            $data['message'] = $message == '' ? $exception : $message;

            return response()->json($data, $code);
        });

    }
}