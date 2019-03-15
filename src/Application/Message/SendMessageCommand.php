<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Application\Message;

final class SendMessageCommand
{
    /**
     * @var array
     */
    private $config;

    /**
     * SendMessageCommand constructor.
     * @param array $params
     * @param array $attachments
     */
    public function __construct(array $params, array $attachments)
    {
        $this->config['params'] = $params;
        $this->config['attachments'] = $attachments;
    }

    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->config;
    }

    /**
     * @return array
     */
    public function getAttachments(): array
    {
        return $this->config['attachments'];
    }

    /**
     * @param array $attachments
     * @return SendMessageCommand
     */
    public function withAttachments(array $attachments): SendMessageCommand
    {
        $cloned = clone $this;

        $cloned->config['attachments'] = $attachments;

        return $cloned;
    }
}
