<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Infrastructure\SwitftMailer;

use DirectoryIterator;
use ReflectionProperty;
use Swift_ByteStream_FileByteStream;
use Swift_FileSpool;
use Swift_Message;
use Swift_Mime_SimpleMimeEntity;
use Swift_Transport;

final class FileSpool extends Swift_FileSpool
{
    /** The spool directory */
    private $path;

    /**
     * FileSpool constructor.
     * @param string $path
     * @throws \Swift_IoException
     */
    public function __construct(string $path)
    {
        $this->path = $path;

        parent::__construct($path);
    }

    /**
     * @param Swift_Transport $transport
     * @param null $failedRecipients
     * @throws \ReflectionException
     * @return int
     */
    public function flushQueue(Swift_Transport $transport, &$failedRecipients = null)
    {
        $directoryIterator = new DirectoryIterator($this->path);

        /* Start the transport only if there are queued files to send */
        if (!$transport->isStarted()) {
            foreach ($directoryIterator as $file) {
                if ('.message' === substr($file->getRealPath(), -8)) {
                    $transport->start();
                    break;
                }
            }
        }

        $failedRecipients = (array)$failedRecipients;
        $count = 0;
        $time = time();
        $reflectionProperty = new ReflectionProperty(Swift_Mime_SimpleMimeEntity::class, 'body');
        $reflectionProperty->setAccessible(true);
        foreach ($directoryIterator as $file) {
            $file = $file->getRealPath();

            if (false === $file || '.message' !== substr($file, -8)) {
                continue;
            }

            /* We try a rename, it's an atomic operation, and avoid locking the file */
            if (rename($file, $file . '.sending')) {
                /** @var Swift_Message $message */
                $message = unserialize(file_get_contents($file . '.sending'), [Swift_Message::class]);

                $count += $transport->send($message, $failedRecipients);

                unlink($file . '.sending');

                foreach ($message->getChildren() as $child) {
                    $byteStream = $reflectionProperty->getValue($child);
                    if ($byteStream instanceof Swift_ByteStream_FileByteStream) {
                        unlink($byteStream->getPath());
                        $directory = dirname($byteStream->getPath());
                        rmdir($directory);
                    }
                }
            } else {
                /* This message has just been catched by another process */
                continue;
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
}
