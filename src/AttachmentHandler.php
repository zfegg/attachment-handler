<?php

declare(strict_types = 1);

namespace Zfegg\AttachmentHandler;

use Laminas\Diactoros\Response\JsonResponse;
use League\Flysystem\FilesystemWriter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sirius\Validation\ValueValidator;

class AttachmentHandler implements RequestHandlerInterface
{

    private string $url;

    public function __construct(
        private ValueValidator $validator,
        private FilesystemWriter $filesystem,
        private string $path,
        string $url = '/',
    ) {
        $this->url = rtrim($url, '/');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \Psr\Http\Message\UploadedFileInterface[] $files */
        $files = $request->getUploadedFiles();
        $result = $this->upload($files);
        if ($result instanceof ResponseInterface) {
            return $result;
        }
        return new JsonResponse($result);
    }

    private function upload(array $files): array|ResponseInterface
    {
        $result = [];
        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $result[$key] = $this->upload($file);
                if ($result[$key] instanceof ResponseInterface) {
                    return $result[$key];
                }
            } else {
                $fileInfo = [
                    'name'     => $file->getClientFilename(),
                    'tmp_name' => $file->getStream()->getMetadata('uri'),
                    'type'     => $file->getClientMediaType(),
                    'error'    => $file->getError(),
                    'size'     => $file->getSize()
                ];

                if (! $this->validator->validate($fileInfo)) {
                    return new JsonResponse(
                        [
                            'status' => 422,
                            'message' => '验证失败.',
                            'validation_messages' => array_map(fn($m) => $m . '', $this->validator->getMessages()),
                        ],
                        422
                    );
                }

                $path = preg_replace_callback(
                    '/{([a-z0-9:]+)}/i',
                    function ($m) use ($fileInfo) {
                        [$fn, $args] = explode(':', $m[1]) + [null, ''];
                        switch ($fn) {
                            case 'date':
                                return date($args ?: 'Ym');
                            case 'hash':
                                return substr(hash_file('sha256', $fileInfo['tmp_name']), 0, $args ?: 32);
                            case 'ext':
                                $ext = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
                                return $ext;
                            case 'name':
                                return pathinfo($fileInfo['name'], PATHINFO_FILENAME);
                            case 'uniqid':
                                return uniqid();
                            default:
                                return '';
                        }
                    },
                    $this->path
                );
                $this->filesystem->writeStream($path, $file->getStream()->detach());
                $result[$key] = [
                    'name' => $fileInfo['name'],
                    'path' => $this->url . '/' . $path,
                    'type' => $fileInfo['type'],
                ];
            }
        }

        return $result;
    }
}
