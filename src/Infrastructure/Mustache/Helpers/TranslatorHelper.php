<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Infrastructure\Mustache\Helpers;

use Gettext\Translator;

class TranslatorHelper
{
    /**
     * @var Translator static for all instances
     */
    private static $t;
    /**
     * @var string sprintf type of template. Language is one of the args to replace (i.e. /this/is/%s/messages.php)
     */
    private $path;

    /**
     * TranslatorHelper constructor.
     * @param string $i18nPath
     */
    public function __construct(string $i18nPath)
    {
        $this->path = $i18nPath;
    }

    /**
     * @param string $term
     *
     * @return string
     */
    public function get(string $term): string
    {
        return $this->getTranslator()->gettext($term);
    }

    /**
     * @param string $lang
     */
    public function setLanguage(string $lang): void
    {
        $translations = sprintf($this->path, $lang);

        if (is_file($translations)) {
            $this->getTranslator()->loadTranslations($translations);
        }
    }

    /**
     * @return Translator
     */
    public function getTranslator(): Translator
    {
        if (null === self::$t) {
            self::$t = new Translator();
        }

        return self::$t;
    }
}
