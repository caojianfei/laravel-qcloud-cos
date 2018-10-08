<?php
/**
 * Created by PhpStorm.
 * User: Caojianfei
 * Date: 2018/10/8/008
 * Time: 16:11
 */

namespace Caojianfei\QcloudCos;

use League\Flysystem\AdapterInterface;


abstract class AbstractAdapter implements AdapterInterface
{
    const UPLOAD_HEADERS = [
        'ACL', 'GrantFullControl', 'GrantRead', 'GrantWrite', 'StorageClass', 'Expires', 'CacheControl', 'ContentType',
        'ContentDisposition', 'ContentEncoding', 'ContentLanguage', 'ContentLength', 'ContentMD5', 'Metadata',
        'ServerSideEncryption'
    ];
}