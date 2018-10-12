<?php
/**
 * Created by PhpStorm.
 * User: lele.wang
 * Date: 2018/10/12
 * Time: 18:50
 */

namespace Wanglelecc\Weather;

use Illuminate\Support\ServiceProvider;

class WeatherServiceProvider extends ServiceProviderc
{
    protected $defer = true;
    
    public function register()
    {
        $this->app->singleton(Weather::class, function (){
            return new Weather(config('services.weather.key'));
        });
        
        $this->app->alias(Weather::class, 'weather');
    }
    
    public function provides()
    {
        return [Weather::class, 'weather'];
    }
    
}