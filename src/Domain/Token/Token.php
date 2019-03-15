<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Domain\Token;

class Token
{
    /**
     * @var array
     */
    public $decoded = [];

    /**
     * @param $decoded
     */
    public function populate(array $decoded): void
    {
        $this->decoded = $decoded;
    }

    /**
     * @param array $scope
     * @return bool
     */
    public function hasScope(array $scope): bool
    {
        return (bool)count(array_intersect($scope, $this->decoded['scope']));
    }
}
