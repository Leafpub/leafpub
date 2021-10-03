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

class Plugin extends CreateTable
{
    /**
     * @var string
     */
    protected $table = 'plugins';

    public function __construct()
    {
        $this->table = \Leafpub\Models\Tables\TableGateway::$prefix . $this->table;
        $this->columns = [
            new \Zend\Db\Sql\Ddl\Column\Integer('id', false, null, ['auto_increment' => true]),
            new \Zend\Db\Sql\Ddl\Column\Varchar('name', 51),
            new \Zend\Db\Sql\Ddl\Column\Varchar('description', 100),
            new \Zend\Db\Sql\Ddl\Column\Varchar('author', 51),
            new \Zend\Db\Sql\Ddl\Column\Varchar('version', 8),
            new \Zend\Db\Sql\Ddl\Column\Varchar('requires', 8),
            new \Zend\Db\Sql\Ddl\Column\Varchar('license', 8),
            new \Zend\Db\Sql\Ddl\Column\Varchar('dir', 51),
            new \Zend\Db\Sql\Ddl\Column\Varchar('img', 100),
            new \Zend\Db\Sql\Ddl\Column\Varchar('link', 100),
            new \Zend\Db\Sql\Ddl\Column\Binary('isAdminPlugin', null, false, 0),
            new \Zend\Db\Sql\Ddl\Column\Binary('isMiddleware', null, false, 0),
            new \Zend\Db\Sql\Ddl\Column\Datetime('install_date'),
            new \Zend\Db\Sql\Ddl\Column\Binary('enabled', null, false, 0),
            new \Zend\Db\Sql\Ddl\Column\Datetime('enable_date'),
        ];
        $this->constraints = [
            new \Zend\Db\Sql\Ddl\Constraint\PrimaryKey('id'),
            new \Zend\Db\Sql\Ddl\Constraint\UniqueKey('dir'),
        ];
    }

    public function setTable($t): self
    {
        return $this;
    }

    public function getTable(): string
    {
        return $this->table;
    }
}
