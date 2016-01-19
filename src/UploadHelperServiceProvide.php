<?php

namespace Bitdev\UploadHelper;

use Illuminate\Support\ServiceProvider;
use App;
class UploadHelperServiceProvide extends ServiceProvider
{
    protected $defer = false;

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('UploadHelper',function ()
        {
            return $this->app['Bitdev\UploadHelper\UploadHelper'];
        });

        // App::alias('UploadHelper',\Providers\UploadHelper::class);
    }
    public function providers()
    {
        return ['Bitdev\UploadHelper\UploadHelper'];
    }
}
