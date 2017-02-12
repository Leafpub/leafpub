<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2017 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models\Ddl;

use \Zend\Db\Sql\Ddl\CreateTable;
    

class Tag extends CreateTable {
    protected $table = 'tags';

    public function __construct($t = null){
        $this->table = \Leafpub\Models\Tables\TableGateway::$prefix . $this->table;

        $this->columns = [
            new \Zend\Db\Sql\Ddl\Column\Integer('id', false, null, ['auto_increment' => true]), 
            new \Zend\Db\Sql\Ddl\Column\Varchar('slug', 191), 
            new \Zend\Db\Sql\Ddl\Column\Datetime('created'),
            new \Zend\Db\Sql\Ddl\Column\Text('name'),
            new \Zend\Db\Sql\Ddl\Column\Text('description'),
            new \Zend\Db\Sql\Ddl\Column\Text('cover'),
            new \Zend\Db\Sql\Ddl\Column\Text('meta_title'),
            new \Zend\Db\Sql\Ddl\Column\Text('meta_description'),
            new Column\Enum('type', ["'post'", "'upload'"], false, 'post'),
        ];

        $this->constraints = [
            new \Zend\Db\Sql\Ddl\Constraint\PrimaryKey('id'),
            new \Zend\Db\Sql\Ddl\Constraint\UniqueKey('slug')
        ];
    }

     public function setTable($t){
        return $this;
    }

    public function getTable(){
        return $this->table;
    }
}