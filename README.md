附件上传处理器
================

[![GitHub Actions: Run tests](https://github.com/zfegg/attachment-handler/workflows/qa/badge.svg)](https://github.com/zfegg/attachment-handler/actions?query=workflow%3A%22qa%22)
[![Coverage Status](https://coveralls.io/repos/github/zfegg/attachment-handler/badge.svg?branch=master)](https://coveralls.io/github/zfegg/attachment-handler?branch=master)
[![Latest Stable Version](https://poser.pugx.org/zfegg/attachment-handler/v/stable.png)](https://packagist.org/packages/zfegg/attachment-handler)

附件上传处理器

安装 / Installation
------------

```bash
composer require zfegg/attachment-handler
```

使用 / Usage
------


### 在Mezzio中使用

```php

// File config/config.php
// Add ConfigProvider 

new ConfigAggregator([
  Zfegg\AttachmentHandler\ConfigProvider::class,
]);
```

配置示例:

```php
use Zfegg\AttachmentHandler\AttachmentHandler;
use League\Flysystem\Filesystem;

return [
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
        // 上传目录，支持 url schema
        // ftp://user:pass@127.0.0.1/uploads
        // sftp://user:pass@127.0.0.1/uploads
        // memory://temp
        'path' => 'public/uploads'
    ]
]
```