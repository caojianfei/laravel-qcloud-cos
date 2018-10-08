<?php
/**
 * Created by PhpStorm.
 * User: Caojianfei
 * Date: 2018/10/8/008
 * Time: 14:56
 */

namespace Caojianfei\QcloudCos;

use Storage;
use Qcloud\Cos\Client;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class LaravelServicePorvider extends ServiceProvider
{

    public function boot()
    {
        Storage::extend('qcloud-cos', function ($app, $config) {
            $credentials = [];

            foreach ($config['credentials'] as $key => $val) {
                $credentials[camel_case($key)] = $val;
            }

            $client = new Client([
                'region' => $config['region'],
                'credentials' => $credentials,
                'timeout' => $config['timeout'],
                'connect_timeout' => $config['connect_timeout']
            ]);

            return new Filesystem(new QcloudCosAdapter($client, $app, $config));
        });
    }

    public function register()
    {

    }

}