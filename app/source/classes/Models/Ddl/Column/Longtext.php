<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models\Ddl\Column;

use Zend\Db\Sql\Ddl\Column\AbstractLengthColumn;

class Longtext extends AbstractLengthColumn
{
    /**
     * @var string
     */
    protected $type = 'LONGTEXT';
}
