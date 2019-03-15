<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Application\Console\Processor;

use App\Infrastructure\Mustache\Helpers\TranslatorHelper;
use Exception;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Mustache_Engine;
use Mustache_Exception_UnknownTemplateException;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_Message;

class SendMailProcessor implements Processor
{
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * MailProcessor constructor.
     * @param Swift_Mailer $mailer
     * @param Mustache_Engine $mustache
     * @param TranslatorHelper $translatorHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Swift_Mailer $mailer,
        Mustache_Engine $mustache,
        TranslatorHelper $translatorHelper,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->mustache = $mustache;
        $this->translator = $translatorHelper;
        $this->mustache->addHelper('i18n', function ($text) use ($translatorHelper) {
            return $translatorHelper->get($text);
        });
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function process(Message $message, Context $context)
    {
        $this->logger->info('Email message received');
        $sent = true;

        try {
            if (!$this->mailer->getTransport()->ping()) {
                $this->mailer->getTransport()->stop();
                $this->mailer->getTransport()->start();
            }

            $data = json_decode($message->getBody(), true);

            $mailMessage = $this->buildMessage($data);

            $this->logger->info(
                printf(
                'Sending email with subject "%s" to "%s"',
                $mailMessage->getSubject(),
                $data['to']
                )
            );

            if (!(bool)$this->mailer->send($mailMessage)) {
                $this->logger->warning('Unable to send mail message with data: ' . $message->getBody());

                $sent = false;
            } else {
                $this->logger->info('Email message sent.');
            }

            $this->mailer->getTransport()->stop();
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            $sent = false;
        }

        $this->purgeAttachments($data['attachments'] ?? []);

        return $sent ? self::ACK : self::REJECT;
    }

    /**
     * @param array $config
     * @throws Exception
     * @return Swift_Message
     */
    private function buildMessage(array $config): Swift_Message
    {
        $from = $config['from'];
        $to = $config['to'];
        $subject = $config['subject'];

        if (null !== $config['language']) {
            $this->translator->setLanguage($config['language']);
            $subject = $this->translator->get($subject);
        }

        $context = $config['data'] ?? [];

        $html = $this->loadTemplate($config['template'], $context, 'html');
        $txt = $this->loadTemplate($config['template'], $context, 'txt');

        if (null === $html && null === $txt) { // not templates found
            throw new Mustache_Exception_UnknownTemplateException($config['template'] . '.mustache');
        }

        $message = (new Swift_Message($subject))
            ->setFrom($from)
            ->setTo($to)
            ->setBody($html ?? $txt, null !== $html ? 'text/html' : 'text/plain');

        if (isset($html, $txt)) {
            $message->addPart($txt, 'text/plain');
        }

        if (isset($config['attachments'])) {
            foreach ($config['attachments'] as $attachment) {
                $message->attach(\Swift_Attachment::fromPath($attachment));
            }
        }

        return $message;
    }

    /**
     * @param array $attachments
     */
    private function purgeAttachments(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if (is_file($attachment)) {
                unlink($attachment);
            }
            $directory = dirname($attachment);
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
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
