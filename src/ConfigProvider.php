<?php

declare(strict_types = 1);

namespace Zfegg\AttachmentHandler;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            AttachmentHandler::class => [
                'rules' => [
                    'UploadExtension' => [
                        'options' => ['allowed' => ['jpg', 'jpeg', 'png', 'gif', 'bmp']],
                        'messageTemplate' => '文件必须为图片格式 (jpg, jpeg, png, gif, bmp)',
                    ],
                    'UploadSize' => [
                        'options' => ['size' => '2M'],
                        'messageTemplate' => '上传文件必须小于 {max}'
                    ],
                ],
                'storage' => 'images/{date}/{uniqid}.{ext}',
                'url' => '/uploads'
            ],
            Filesystem::class => [
                'path' => 'public/uploads'
            ]
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'invokables' => [
            ],
            'factories'  => [
                AttachmentHandler::class => Factory\AttachmentHandlerFactory::class,
                Filesystem::class => Factory\FilesystemFactory::class,
            ],
            'aliases' => [
                FilesystemInterface::class => Filesystem::class,
            ],
        ];
    }
}
