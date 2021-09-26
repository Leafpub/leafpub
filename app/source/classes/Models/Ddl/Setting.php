<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models\Ddl;

use Zend\Db\Sql\Ddl\CreateTable;

class Setting extends CreateTable
{
    protected $table = 'settings';

    public function __construct($t = null)
    {
        $this->table = \Leafpub\Models\Tables\TableGateway::$prefix . $this->table;

        $this->columns = [
            new \Zend\Db\Sql\Ddl\Column\Varchar('name', 191),
            new Column\Longtext('value'),
        ];

        $this->constraints = [
            new \Zend\Db\Sql\Ddl\Constraint\UniqueKey('name'),
        ];
    }

    public function setTable($t)
    {
        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }
}
