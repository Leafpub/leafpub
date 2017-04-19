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
    

class Upload extends CreateTable {
    protected $table = 'uploads';

    public function __construct($t = null){
        $this->table = \Leafpub\Models\Tables\TableGateway::$prefix . $this->table;

        $this->columns = [
            new \Zend\Db\Sql\Ddl\Column\Integer('id', false, null, ['auto_increment' => true]), 
            new \Zend\Db\Sql\Ddl\Column\Varchar('caption', 191, true), 
            new \Zend\Db\Sql\Ddl\Column\Datetime('created'),
            new \Zend\Db\Sql\Ddl\Column\Varchar('path', 191),
            new \Zend\Db\Sql\Ddl\Column\Varchar('filename', 90),
            new \Zend\Db\Sql\Ddl\Column\Varchar('extension', 10),
            new \Zend\Db\Sql\Ddl\Column\Integer('size'),
            new \Zend\Db\Sql\Ddl\Column\Integer('width'),
            new \Zend\Db\Sql\Ddl\Column\Integer('height')
        ];

        $this->constraints = [
            new \Zend\Db\Sql\Ddl\Constraint\PrimaryKey('id'),
            new \Zend\Db\Sql\Ddl\Constraint\UniqueKey('filename')
        ];
    }

     public function setTable($t){
        return $this;
    }

    public function getTable(){
        return $this->table;
    }
}