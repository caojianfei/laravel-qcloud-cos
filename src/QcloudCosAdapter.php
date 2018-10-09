<?php
/**
 * Created by PhpStorm.
 * User: Caojianfei
 * Date: 2018/10/8/008
 * Time: 15:11
 */

namespace Caojianfei\QcloudCos;

use Qcloud\Cos\Client;
use League\Flysystem\Util;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use Illuminate\Container\Container;
use Qcloud\Cos\Exception\CosException;
use League\Flysystem\AdapterInterface;
use Qcloud\Cos\Exception\NoSuchKeyException;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\CanOverwriteFiles;


class QcloudCosAdapter implements AdapterInterface, CanOverwriteFiles
{
    const PUBLIC_GRANT_URI = 'http://cam.qcloud.com/groups/global/AllUsers';

    protected static $resultMap = [
        'Body' => 'contents',
        'ContentLength' => 'size',
        'ContentType' => 'mimetype',
        'Size' => 'size',
        'Metadata' => 'metadata',
        'StorageClass' => 'storageclass',
        'ETag' => 'etag',
        'VersionId' => 'versionid'
    ];


    protected static $metaOptions = [
        'ACL',
        'GrantFullControl',
        'GrantRead',
        'GrantWrite',
        'StorageClass',
        'Expires',
        'CacheControl',
        'ContentType',
        'ContentDisposition',
        'ContentEncoding',
        'ContentLanguage',
        'ContentLength',
        'ContentMD5',
        'Metadata',
        'ServerSideEncryption'
    ];

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Container
     */
    protected $app;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $bucket;


    public function __construct(Client $client, Container $app, array $config)
    {
        $this->client = $client;
        $this->app = $app;
        $this->config = $config;
        $this->setBucket($config['default_bucket']);
    }

    /**
     * 获取 cos sdk 实例
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * 获取存储桶
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * 设置存储桶
     *
     * @param string $bucket
     */
    public function setBucket(string $bucket)
    {
        $this->bucket = $bucket;
    }

    /**
     * 获取资源url前缀
     *
     * @param bool $withHttps
     * @return string
     */
    public function getUrlPrefix($withHttps = false)
    {
        return ($withHttps ? "https://" : '') . "{$this->bucket}.{$this->config['region']}.myqcloud.com";
    }

    /**
     * 上传新文件
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, $config);
    }

    /**
     * 使用流上传新文件
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource, $config);
    }

    /**
     * 上传文件
     *
     * @param
     * @param $body
     * @param Config $config
     * @return mixed
     */
    protected function upload($path, $body, Config $config)
    {
        $opts = $this->mergeMetaOptions($config, [
            'Key' => $path,
            'Bucket' => $this->bucket,
            'Body' => $body
        ]);

        $response = $this->client->putObject($opts);

        return $this->normalizeResponse($response->toArray(), $path);
    }

    /**
     * 合并请求
     *
     * @param Config $config
     * @param array $opts
     * @return array
     */
    protected function mergeMetaOptions(Config $config, array $opts)
    {
        foreach (static::$metaOptions as $opt) {
            if (!$config->has($opt)) {
                continue;
            }
            $opts[$opt] = $val;
        }

        return $opts;
    }

    /**
     * 更新文件
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * 使用流更新文件
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * 重命名文件，先复制后删除
     *
     * @param string $path
     * @param string $newpathdede
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        if (!$this->copy($path, $newpath)) {
            return false;
        }

        return $this->delete($path);
    }

    /**
     * 复制文件
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        $opts = [
            'Bucket' => $this->bucket,
            'CopySource' => $this->getUrlPrefix() . '/' . $path,
            'Key' => $newpath
        ];

        $response = $this->client->copyObject($opts);

        return $this->normalizeResponse($response->toArray(), $newpath);
    }

    /**
     * 删除文件
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $path,
        ]);

        return !$this->has($path);
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        // TODO 需要先删除目录下所有文件然后删除目录
        // cos 存储并没有目录的概念
        // see https://cloud.tencent.com/document/product/436/13324
        // php sdk 没有实现批量删除的接口，但是api是提供的(无语)

        $list = $this->listContents($dirname, true);

        if (count($list) === 1) {
            return $this->delete($dirname . '/');
        }

        for ($i = 0; $i < count($list); $i++) {

            if ($list[$i]['type'] === 'dir') {
                if (!$this->delete($list[$i]['path'] . '/')) {
                    return false;
                }
            } else {
                if (!$this->delete($list[$i]['path'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        // 根据文档意思是创建一个空的对象比如 abc/
        // 即使目录已经存在，也会返回成功
        $dirname = rtrim($dirname, '/') . '/';
        return $this->write($dirname, '', $config);
    }

    /**
     * 设置访问权限
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        try {
            $this->client->PutObjectAcl([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'ACL' => $visibility === AdapterInterface::VISIBILITY_PUBLIC ? 'public-read' : 'private',
            ]);
        } catch (CosException $e) {
            return false;
        }

        return compact('path', 'visibility');
    }

    /**
     * 检测文件是否存在
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        try {
            $response = $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path
            ]);
        } catch (CosException $e) {
            return false;
        }

        return $this->normalizeResponse($response->toArray(), $path);
    }

    /**
     * 读取文件
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        if ($response = $this->readObject($path)) {
            $response['contents'] = (string)$response['contents'];
            return $response;
        }

        return false;
    }


    /**
     * 读取文件流
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {
        // TODO 返回之后资源关闭了
        if ($response = $this->readObject($path)) {
            $response['stream'] = $response['contents']->getStream();
            return $response;
        }

        return false;
    }

    /**
     * 读取 cos 对象
     *
     * @param $path
     * @return array|bool
     */
    protected function readObject($path)
    {
        try {
            $response = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path
            ]);
        } catch (CosException $e) {
            return false;
        }

        return $this->normalizeResponse($response->toArray(), $path);
    }

    /**
     * 列出文件列表
     *
     * @param string $directory
     * @param bool $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        $opts = [
            'Bucket' => $this->bucket,
        ];

        // TODO 官方文档确实看的脑阔疼，参数不明其意，先这么处理
        if ($directory) {
            $opts['Prefix'] = $directory;
            if ($recursive) {
                $opts['Prefix'] = $directory . '/';
            }
        }

        $listing = $this->retrievePaginatedListing($opts);

        if (!$listing) {
            return [];
        }

        $normalizer = [$this, 'normalizeResponse'];
        $normalized = array_map($normalizer, $listing);
        return Util::emulateDirectories($normalized);

    }

    /**
     * 循环列出所有文件
     *
     * @param array $opts
     * @return array
     */
    public function retrievePaginatedListing(array $opts)
    {
        $contents = [];
        $response = $this->client->listObjects($opts);
        $contents = $response->get('Contents');

        while ($response->get('IsTruncated') && $response->get('NextMarker')) {
            $opts['Marker'] = $response->get('NextMarker');
            $response = $this->client->listObjects($opts);
            $contents = array_merge($contents, $response->get('Contents'));
        }
        return $contents;
    }

    /**
     * 获取文件信息
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        $response = $this->client->headObject([
            'Bucket' => $this->bucket,
            'Key' => $path
        ]);

        return $this->normalizeResponse($response->toArray(), $path);
    }

    /**
     * 获取文件大小
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * 获取文件 minetype
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * 获取文件 LastModified
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * 获取文件访问权限
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {
        $response = $this->client->getObjectAcl([
            'Bucket' => $this->bucket,
            'Key' => $path
        ]);

        $visibility = AdapterInterface::VISIBILITY_PRIVATE;

        foreach ($response->get('Grants') as $grant) {
            if (
                isset($grant['Grantee']['URI'])
                && $grant['Grantee']['URI'] === self::PUBLIC_GRANT_URI
                && $grant['Permission'] === 'READ'
            ) {
                $visibility = AdapterInterface::VISIBILITY_PUBLIC;
                break;
            }
        }

        return compact('visibility');
    }

    /**
     * 目录连接分隔符
     *
     * @param $directory
     * @return string
     */
    protected function applyDirectorySeparator($directory)
    {
        $directory = (string)$directory;

        if ($directory === '') {
            return $directory;
        }

        return $directory . $this->pathSeparator;
    }

    protected function formatContentsList($contents)
    {
        foreach ($contents as $key => $val) {
            $contents[$key]['path'] = $val['Key'];
        }

        return $contents;
    }

    /**
     * 格式化响应结果
     *
     * @param array $response
     * @param string $path
     *
     * @return array
     */
    protected function normalizeResponse(array $response, $path = null)
    {
        $result = [
            'path' => $path ?: (isset($response['Key']) ? $response['Key'] : (isset($response['Prefix']) ? $response['Prefix'] : null)),
        ];
        $result = array_merge($result, Util::pathinfo($result['path']));

        if (isset($response['LastModified'])) {
            $result['timestamp'] = strtotime($response['LastModified']);
        }

        if (substr($result['path'], -1) === '/') {
            $result['type'] = 'dir';
            $result['path'] = rtrim($result['path'], '/');

            return $result;
        }

        return array_merge($result, Util::map($response, static::$resultMap), ['type' => 'file']);
    }


}