<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Infrastructure\SwitftMailer;

use Interop\Queue\Context;
use Interop\Queue\Exception;
use Interop\Queue\Queue;
use ReflectionProperty;
use Swift_ByteStream_FileByteStream;
use Swift_ConfigurableSpool;
use Swift_IoException;
use Swift_Mime_SimpleMessage;
use Swift_Mime_SimpleMimeEntity;
use Swift_Transport;

final class EnqueueSpool extends Swift_ConfigurableSpool
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var Queue
     */
    private $queue;

    /**
     * @param Context $context
     * @param Queue|string $queue
     */
    public function __construct(Context $context, $queue = 'swiftmailer_spool')
    {
        $this->context = $context;
        if (false === $queue instanceof Queue) {
            $queue = $this->context->createQueue($queue);
        }
        $this->queue = $queue;
    }

    /**
     * @inheritdoc
     */
    public function start()
    {
        // not required
    }

    /**
     * @inheritdoc
     */
    public function stop()
    {
        // not required
    }

    public function isStarted(): bool
    {
        return true;
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @throws Swift_IoException
     */
    public function queueMessage(Swift_Mime_SimpleMessage $message): void
    {
        try {
            $serialized = serialize($message);
            $message = $this->context->createMessage($serialized);
            $this->context->createProducer()->send($this->queue, $message);
        } catch (Exception $e) {
            throw new Swift_IoException(sprintf('Unable to send message to message queue.'), null, $e);
        }
    }

    /**
     * @param Swift_Transport $transport
     * @param null $failedRecipients
     * @throws \ReflectionException
     * @return int
     */
    public function flushQueue(Swift_Transport $transport, &$failedRecipients = null): int
    {
        $consumer = $this->context->createConsumer($this->queue);
        $isTransportStarted = false;
        $failedRecipients = (array)$failedRecipients;
        $count = 0;
        $time = time();
        while (true) {
            if ($queueMessage = $consumer->receive(1000)) {
                if (false === $isTransportStarted) {
                    $transport->start();
                    $isTransportStarted = true;
                }
                $message = unserialize($queueMessage->getBody(), Swift_Mime_SimpleMessage::class);
                $count += $transport->send($message, $failedRecipients);
                $consumer->acknowledge($queueMessage);
                $this->purgeAttachments($message);
            }
            if ($this->getMessageLimit() && $count >= $this->getMessageLimit()) {
                break;
            }
            if ($this->getTimeLimit() && (time() - $time) >= $this->getTimeLimit()) {
                break;
            }
        }

        return $count;
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @throws \ReflectionException
     */
    private function purgeAttachments(Swift_Mime_SimpleMessage $message): void
    {
        $reflectionProperty = new ReflectionProperty(Swift_Mime_SimpleMimeEntity::class, 'body');
        $reflectionProperty->setAccessible(true);
        foreach ($message->getChildren() as $child) {
            $byteStream = $reflectionProperty->getValue($child);
            if ($byteStream instanceof Swift_ByteStream_FileByteStream) {
                unlink($byteStream->getPath());
                $directory = dirname($byteStream->getPath());
                rmdir($directory);
            }
        }
    }
}
