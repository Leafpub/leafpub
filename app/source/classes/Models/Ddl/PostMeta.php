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
    

class PostMeta extends CreateTable {
    protected $table = 'post_meta';

    public function __construct($t = null){
        $this->table = \Leafpub\Models\Tables\TableGateway::$prefix . $this->table;

        $this->columns = [
            new \Zend\Db\Sql\Ddl\Column\Integer('post'), 
            new \Zend\Db\Sql\Ddl\Column\Varchar('name', 50), 
            new \Zend\Db\Sql\Ddl\Column\Varchar('value', 191),
            new \Zend\Db\Sql\Ddl\Column\Timestamp('created', false, new \Zend\Db\Sql\Expression('NOW()')),
        ];

        $this->constraints = [
            new \Zend\Db\Sql\Ddl\Index\Index('post'),
        ];
    }

     public function setTable($t){
        return $this;
    }
    
    public function getTable(){
        return $this->table;
    }
}