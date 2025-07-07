<?php

declare(strict_types = 1);

namespace Zfegg\AttachmentHandler;

use Iidestiny\Flysystem\Oss\OssAdapter;
use League\Flysystem\FilesystemAdapter;

class FlysystemAdapterFactory
{
    /**
     * @var callable[]
     */
    private static array $factories = [];

    public static function registerFactory(string $schema, callable $factory): void
    {
        self::$factories[$schema] = $factory;
    }

    public static function createFromUri(string $uri): FilesystemAdapter
    {
        $info = parse_url($uri);
        $query = [];
        parse_str($info['query'] ?? '', $query);
        if (! isset($info['scheme'])) {
            $schema = 'file';
            $info['path'] = $uri;
        } else {
            $schema = $info['scheme'];
        }

        switch ($schema) {
            case 'oss':
            case 'osss':
                $prefix = trim($info['path'], '/') ?? '';
                $host = $info['host'];

                if (str_ends_with($host, '.aliyuncs.com') && ($names = explode('.', $host)) && count($names) === 4) {
                    $bucket = $names[0];
                    $host = substr($host, strlen($names[0]) + 1);
                } elseif (isset($query['bucket'])) {
                    $bucket = $query['bucket'];
                    unset($query['bucket']);
                } else {
                    throw new \InvalidArgumentException('"bucket" not found.');
                }

                $endpoint = ($schema === 'osss' || ! empty($query['ssl']) ? 'https://' : 'http://') . $host;
                $isCName = $query['isCName'] ?? false;
                unset($query['isCName']);

                return new OssAdapter(
                    $info['user'],
                    $info['pass'],
                    $endpoint,
                    $bucket,
                    $isCName,
                    $prefix,
                    [],
                    ...$query
                );
            case 'ftp':
            case 'ftps':
                if (isset($info['user'])) {
                    $info['username'] = $info['user'];
                    unset($info['user']);
                }
                if (isset($info['pass'])) {
                    $info['password'] = $info['pass'];
                    unset($info['pass']);
                }
                $info['root'] = $info['path'];
                $info['ssl'] = $info['scheme'] === 'ftps';
                $config = $info + $query;

                return new \League\Flysystem\Ftp\FtpAdapter(
                    \League\Flysystem\Ftp\FtpConnectionOptions::fromArray($config),
                );
            case "sftp":
                if (isset($info['port'])) {
                    $query['port'] = $info['port'];
                }
                return new \League\Flysystem\PhpseclibV3\SftpAdapter(
                    new \League\Flysystem\PhpseclibV3\SftpConnectionProvider(
                        $info['host'],
                        $info['user'],
                        $info['pass'] ?? null,
                        ...$query,
                    ),
                    $info['path'] ?? '/tmp',
                );
            case "file":
                return new \League\Flysystem\Local\LocalFilesystemAdapter($info['path'], ...$query);
            case "null":
            case "memory":
                return new \League\Flysystem\InMemory\InMemoryFilesystemAdapter();
            case "zip":
                return new \League\Flysystem\ZipArchive\ZipArchiveAdapter(
                    new \League\Flysystem\ZipArchive\FilesystemZipArchiveProvider($info['path']),
                    ...$query,
                );
            default:
                if (isset(self::$factories[$schema])) {
                    return (self::$factories[$schema])($info + $query, $uri);
                }

                throw new \InvalidArgumentException("Invalid uri argument \"$uri\"");
        }
    }
}
