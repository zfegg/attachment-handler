<?php

declare(strict_types = 1);

namespace Zfegg\AttachmentHandler\Factory;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Psr\Container\ContainerInterface;
use Sirius\Validation\ValueValidator;
use Zfegg\AttachmentHandler\AttachmentHandler;
use Zfegg\AttachmentHandler\FlysystemAdapterFactory;

class AttachmentHandlerFactory
{

    public function __invoke(ContainerInterface $container): AttachmentHandler
    {
        $config = $container->get('config')[AttachmentHandler::class] ?? [];

        $validator = new ValueValidator();

        foreach (($config['rules'] ?? []) as $name => $rule) {
            $validator->add(
                $name,
                ...$rule
            );
        }

        return new AttachmentHandler(
            $validator,
            ! isset($config['filesystem']) || is_string($config['filesystem'])
                ? $container->get($config['filesystem'] ?? FilesystemInterface::class)
                : new Filesystem(
                    FlysystemAdapterFactory::createFromUri($config['filesystem']['path']),
                    $config['filesystem']['config'] ?? null
                ),
            $config['storage'],
            $config['url'] ?? '/',
        );
    }
}
