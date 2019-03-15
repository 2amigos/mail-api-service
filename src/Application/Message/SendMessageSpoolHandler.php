<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Application\Message;

use App\Infrastructure\Mustache\Helpers\TranslatorHelper;
use Exception;
use League\Flysystem\Filesystem;
use Mustache_Engine;
use Mustache_Exception_UnknownTemplateException;
use Slim\Http\UploadedFile;
use Swift_Mailer;
use Swift_Message;

final class SendMessageSpoolHandler
{
    /**
     * @var Filesystem
     */
    private $fs;
    /**
     * @var Swift_Mailer
     */
    private $mailer;
    /**
     * @var Mustache_Engine
     */
    private $mustache;
    /**
     * @var TranslatorHelper
     */
    private $translator;

    /**
     * SendMessageHandler constructor.
     * @param Swift_Mailer $mailer
     * @param Mustache_Engine $mustache
     * @param TranslatorHelper $translatorHelper
     * @param Filesystem $filesystem
     *
     */
    public function __construct(
        Swift_Mailer $mailer,
        Mustache_Engine $mustache,
        TranslatorHelper $translatorHelper,
        Filesystem $filesystem
    ) {
        $this->fs = $filesystem;
        $this->mailer = $mailer;
        $this->mustache = $mustache;
        $this->translator = $translatorHelper;
    }

    /**
     * @param SendMessageCommand $command
     * @throws SendMessageException
     * @return array
     */
    public function handle(SendMessageCommand $command): array
    {
        $data = [];

        try {
            $config = $command->getConfiguration();
            $message = $this->buildMessage($config);

            // send email
            $data['success'] = (bool)$this->mailer->send($message);
        } catch (Exception $e) {
            throw new SendMessageException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $data;
    }

    /**
     * @param array $config
     * @throws Exception
     * @return Swift_Message
     */
    private function buildMessage(array $config): Swift_Message
    {
        $params = $config['params'];
        $from = $params['from'];
        $to = $params['to'];
        $subject = $params['subject'];

        if (null !== $params['language']) {
            $this->translator->setLanguage($params['language']);
            $subject = $this->translator->get($subject);
        }

        $context = $params['data'] ?? [];

        $html = $this->loadTemplate($params['template'], $context, 'html');
        $txt = $this->loadTemplate($params['template'], $context, 'txt');

        if (null === $html && null === $txt) { // not templates found
            throw new Mustache_Exception_UnknownTemplateException($params['template'] . '.mustache');
        }

        $message = (new Swift_Message($subject))
            ->setFrom($from)
            ->setTo($to)
            ->setBody($html ?? $txt, null !== $html ? 'text/html' : 'text/plain');

        if (isset($html, $txt)) {
            $message->addPart($txt, 'text/plain');
        }

        if (isset($config['attachments'])) {
            $folder = bin2hex(random_bytes(16));
            /** @var UploadedFile $attachment */
            foreach ($config['attachments'] as $attachment) {
                // work to move the files to temp folder which they will be cleared
                // by another command
                $name = trim(preg_replace('/[^a-z0-9_]+/', '-', strtolower($attachment->getClientFilename())), '-');
                $path = $folder . '/' . $name;
                $this->fs->write($path, file_get_contents($attachment->file));
                $message->attach(\Swift_Attachment::fromPath($this->fs->getAdapter()->getPathPrefix() . '/' . $path));
            }
        }

        return $message;
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
