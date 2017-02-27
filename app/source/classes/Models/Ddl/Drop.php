<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models\Ddl;

use \Zend\Db\Sql\Ddl\DropTable;

class Drop extends DropTable {
    /**
     * @var array
     */
    protected $specifications = [
        self::TABLE => 'DROP TABLE IF EXISTS %1$s'
    ];
}