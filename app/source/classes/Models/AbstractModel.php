<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models;

abstract class AbstractModel implements ModelInterface
{
    protected static $allowedCaller;

    public static function truncate()
    {
        if (!Session::isRole(['owner', 'admin'])) {
            throw new \Exception('Only owner and admin are allowed to truncate tables!');
        }

        return self::getModel()->truncate();
    }

    abstract protected static function getModel();

    /**
     * Checks if the caller is allowed to call a method
     */
    protected static function isAllowedCaller(): bool
    {
        if ($_REQUEST['cmd'] === 'install') {
            return true;
        }
        $data = debug_backtrace();

        return in_array($data[2]['class'], static::$allowedCaller);
    }
}
