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

class User extends CreateTable
{
    /**
     * @var string
     */
    protected $table = 'users';

    public function __construct()
    {
        $this->table = \Leafpub\Models\Tables\TableGateway::$prefix . $this->table;
        $this->columns = [
            new \Zend\Db\Sql\Ddl\Column\Integer('id', false, null, ['auto_increment' => true]),
            new \Zend\Db\Sql\Ddl\Column\Varchar('slug', 191),
            new \Zend\Db\Sql\Ddl\Column\Datetime('created'),
            new \Zend\Db\Sql\Ddl\Column\Varchar('name', 191),
            new \Zend\Db\Sql\Ddl\Column\Varchar('email', 191),
            new \Zend\Db\Sql\Ddl\Column\Varchar('password', 191),
            new \Zend\Db\Sql\Ddl\Column\Varchar('reset_token', 191),
            new Column\Enum('role', ['\'owner\'', '\'admin\'', '\'editor\'', '\'author\''], false, 'author'),
            new \Zend\Db\Sql\Ddl\Column\Text('bio'),
            new \Zend\Db\Sql\Ddl\Column\Text('cover'),
            new \Zend\Db\Sql\Ddl\Column\Text('avatar'),
            new \Zend\Db\Sql\Ddl\Column\Varchar('twitter', 191),
            new \Zend\Db\Sql\Ddl\Column\Text('location'),
            new \Zend\Db\Sql\Ddl\Column\Text('website'),
        ];
        $this->constraints = [
            new \Zend\Db\Sql\Ddl\Constraint\PrimaryKey('id'),
            new \Zend\Db\Sql\Ddl\Index\Index('slug'),
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
