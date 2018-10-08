<?php
/**
 * Created by PhpStorm.
 * User: Caojianfei
 * Date: 2018/10/8/008
 * Time: 15:11
 */

namespace Caojianfei\QcloudCos;

use Qcloud\Cos\Client;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use Illuminate\Container\Container;

class QcloudCosAdapter extends AbstractAdapter
{
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


    public function __construct(Client $client, Container $app, array $config)
    {
        $this->client = $client;
        $this->app = $app;
        $this->config = $config;
    }

    public function getClient()
    {
        return $this->client;
    }


    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        $opts = [
            'Key' => $path,
            'Bucket' => $config->get('bucket') ?? $this->config['default_bucket'],
            'Body' => $contents,
        ];

        $this->mergeHeaders($config, $opts);

        return $this->client->putObject($opts);
    }

    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        $opts = [
            'Key' => $path,
            'Bucket' => $config->get('bucket') ?? $this->config['default_bucket'],
            'Body' => $resource
        ];

        $this->mergeHeaders($config, $opts);

        return $this->client->putObject($opts);
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * Update a file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        return false;
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        $opts = [
            'Bucket' => $this->config['default_bucket'],
            'CopySource' => $path,
            'Key' => $newpath
        ];


        // $this->mergeHeaders($config, $opts);

        return $this->client->copyObject($opts);
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {

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

    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {

    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        if ($path === 'caojf-1257704352.cos.ap-shanghai.myqcloud.com/RJNGmEcjQxMJCgGSReBjQNJsrlhPYu7PSJULy9D2.png') {
            return true;
        }
        return false;
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {

    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {

    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {

    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {

    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {

    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {

    }

    /**
     * Get the last modified time of a file as a timestamp.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {

    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {

    }

    protected function mergeHeaders(Config $config, array &$opts)
    {
        foreach (static::UPLOAD_HEADERS as $header) {
            if ($headerVal = $config->get($header)) {
                $opts[$header] = $headerVal;
            }
        }
    }
}