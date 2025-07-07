<?php

declare(strict_types = 1);

namespace Zfegg\AttachmentHandler\Factory;

use League\Flysystem\Filesystem;
use Psr\Container\ContainerInterface;
use Zfegg\AttachmentHandler\FlysystemAdapterFactory;

class FilesystemFactory
{
    public function __invoke(ContainerInterface $container): Filesystem
    {
        $config = $container->get('config')[Filesystem::class] ?? [];
        return new Filesystem(
            FlysystemAdapterFactory::createFromUri($config['path']),
            $config['config'] ?? []
        );
    }
}
