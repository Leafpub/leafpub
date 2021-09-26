<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models\Ddl\Constraint;

use Zend\Db\Sql\Ddl\Index\Index;

class Fulltext extends Index
{
    /**
     * @var string
     */
    protected $specification = 'FULLTEXT KEY %s(...)';
}
