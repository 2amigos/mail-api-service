<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Application\Message;

use App\Infrastructure\Enqueue\Message\FsMessageProducer;
use Exception;
use League\Flysystem\Filesystem;
use Mustache_Engine;
use Mustache_Exception_UnknownTemplateException;
use Slim\Http\UploadedFile;

final class SendMessageFsQueueHandler
{
    /**
     * @var FsMessageProducer
     */
    private $fsMessageProducer;
    /**
     * @var Mustache_Engine
     */
    private $mustache;
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * SendMessageQueueHandler constructor.
     * @param FsMessageProducer $fsMessageProducer
     * @param Mustache_Engine $mustache
     * @param Filesystem $filesystem
     */
    public function __construct(
        FsMessageProducer $fsMessageProducer,
        Mustache_Engine $mustache,
        Filesystem $filesystem
    ) {
        $this->fsMessageProducer = $fsMessageProducer;
        $this->mustache = $mustache;
        $this->fs = $filesystem;
    }

    /**
     * @param SendMessageCommand $command
     * @throws SendMessageException
     * @throws \Interop\Queue\Exception
     * @return array
     */
    public function handle(SendMessageCommand $command): array
    {
        try {
            $config = $command->getConfiguration();
            $message = $this->parseMessage($config);

            // push it to the queue
            $this->fsMessageProducer->send($message, null);
        } catch (Exception $e) {
            throw new SendMessageException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return ['success' => true];
    }

    /**
     * @param array $config
     * @throws Exception
     * @return array
     */
    private function parseMessage(array $config): array
    {
        $params = $config['params'];
        $from = $params['from'];
        $to = $params['to'];
        $subject = $params['subject'];
        $context = $params['data'] ?? [];
        $attachments = $config['attachments']?? [];

        $html = $this->loadTemplate($params['template'], $context, 'html');
        $txt = $this->loadTemplate($params['template'], $context, 'txt');

        if (null === $html && null === $txt) { // not templates found
            throw new Mustache_Exception_UnknownTemplateException($params['template'] . '.mustache');
        }

        if (!empty($attachments)) {
            $attachments = [];
            $folder = bin2hex(random_bytes(16));
            /** @var UploadedFile $attachment */
            foreach ($config['attachments'] as $attachment) {
                // work to move the files to temp folder which they will be cleared
                // by another command
                $name = trim(preg_replace('/[^a-z0-9_.]+/', '-', strtolower($attachment->getClientFilename())), '-');
                $path = $folder . '/' . $name;
                $this->fs->write($path, file_get_contents($attachment->file));
                $attachments[] = $this->fs->getAdapter()->getPathPrefix() . $path;
            }
        }

        return [
            'template' => $params['template'],
            'language' => $params['language'],
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'data' => $context,
            'attachments' => $attachments
        ];
    }

    /**
     * @param string $template
     * @param array $context
     * @param string $type
     * @return string|null
     */
    private function loadTemplate(string $template, array $context, string $type): ?string
    {
        $content = null;

        try {
            $mustacheTemplate = $this->mustache->loadTemplate($type . '/' . $template);
            $content = $mustacheTemplate->render($context);
        } catch (Mustache_Exception_UnknownTemplateException $exception) {
            return null;
        }

        return $content;
    }
}
