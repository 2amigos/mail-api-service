<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Infrastructure\Enqueue\Message;

use App\Infrastructure\Enqueue\AbstractProducer;
use Enqueue\Client\Config;

final class FsMessageProducer extends AbstractProducer
{
    /**
     * @param array $message
     * @param int|null $delay
     * @throws \Interop\Queue\Exception
     * @throws \Interop\Queue\Exception\InvalidDestinationException
     * @throws \Interop\Queue\Exception\InvalidMessageException
     */
    public function send(array $message, ?int $delay): void
    {
        $message = $this->resolver->resolve($message);
        $context = $this->getContext();
        $queueMessage = $context->createMessage(
            json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            [
                Config::PROCESSOR => 'enqueue.mail.processor'
            ]
            );
        $context->createProducer()->send($this->getQueue(), $queueMessage);
    }

    /**
     * @inheritdoc
     */
    protected function configureOptions(): void
    {
        $this->resolver
            ->setDefined(['template', 'language', 'from', 'to', 'subject', 'data', 'attachments'])
            ->setRequired(['from', 'to', 'subject', 'template'])
            ->setAllowedTypes('from', ['string', 'array'])
            ->setAllowedTypes('to', ['string', 'array'])
            ->setAllowedTypes('data', 'array')
            ->setAllowedTypes('template', 'string')
            ->setAllowedTypes('language', 'string')
            ->setAllowedTypes('attachments', ['null', 'array']);
    }
}
