# 扩展说明

该扩展是对`laravel`框架中的文件存储自定义驱动的扩展，实现`腾讯云cos 对象存储`驱动。目前是在laravel 5.6 版本测试与开发的，其他版本暂时没有亲测。


# 使用

安装扩展包

```
composer require caojianfei/laravel-qcloud-cos
```

配置 filesystems.php

```
'disks' => [
       .
       .
       .
       
    'qcloud-cos' => [
        /* 驱动名称 */
        'driver' => 'qcloud-cos',
        /* 地域 */
        'region' =>  env('QCLOUD_COS_REGION', 'ap-shanghai'),
        /* 认证信息 */
        'credentials' => [
            'app_id' =>  env('QCLOUD_COS_APP_ID'),
            'secret_id' =>  env('QCLOUD_COS_SECRET_ID'),
            'secret_key' => env('QCLOUD_COS_SECRET_KEY'),
            'token' => env('QCLOUD_COS_TOKEN', null)
        ],
        /* 默认存储桶 */
        'default_bucket' =>  env('QCLOUD_COS_DEFAULT_BUCKET'),
        'timeout' => env('QCLOUD_COS_TIMEOUT', 3600),
        'connect_timeout' =>  env('QCLOUD_COS_CONNECT_TIMEOUT', 3600)
    ],

],
```

配置好自己的配置之后就可以开始使用了，具体使用方法可以参考[laravel官方文档](https://laravel-china.org/docs/laravel/5.6/filesystem/1390)

# 官方文档

[laravel 文件存储](https://laravel-china.org/docs/laravel/5.6/filesystem/1390)

[腾讯云对象存储](https://cloud.tencent.com/document/product/436)

# 结尾

如果喜欢，欢迎 star，如果发现了任何问题或有更好的意见和建议，欢迎联系或者`pull request`

# License

MIT

