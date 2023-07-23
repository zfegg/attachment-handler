<?php

declare(strict_types = 1);

namespace ZfeggTest\AttachmentHandler;

use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\ServiceManager\ServiceManager;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Zfegg\AttachmentHandler\AttachmentHandler;
use Zfegg\AttachmentHandler\ConfigProvider;

class AttachmentHandlerTest extends TestCase
{

    private ServiceManager $container;

    public function initContainer(): void
    {
        $this->container = new ServiceManager();

        $providers = [
            ConfigProvider::class,
            (function () {
                return [
                    Filesystem::class => [
                        'path' => 'null://null'
                    ]
                ];
            })
        ];

        $aggregator = new ConfigAggregator($providers);
        $config = $aggregator->getMergedConfig();
        $this->container->setService('config', $config);
        $this->container->configure($config['dependencies']);
    }

    public function testUpload(): void
    {
        $this->initContainer();

        $files = [
            'file' => [
                'tmp_name' => __DIR__ . '/images/Test.png',
                'size' => 1,
                'error' => 0,
                'name' => 'Test.png',
                'type' => 'application/image'
            ]
        ];
        $req = (ServerRequestFactory::fromGlobals(files: $files))
            ->withMethod("POST");

        /** @var AttachmentHandler $handler */
        $handler = $this->container->get(AttachmentHandler::class);
        $response = $handler->handle($req);

        $result = json_decode((string)$response->getBody(), true);
        self::assertArrayHasKey('name', $result['file']);
        self::assertArrayHasKey('path', $result['file']);
        self::assertArrayHasKey('type', $result['file']);
    }
}
