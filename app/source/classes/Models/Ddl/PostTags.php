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
    

class PostTags extends CreateTable {
    protected $table = 'post_tags';

    public function __construct($t = null){
        $this->table = \Leafpub\Models\Tables\TableGateway::$prefix . $this->table;

        $this->columns = [
            new \Zend\Db\Sql\Ddl\Column\Integer('post'), 
            new \Zend\Db\Sql\Ddl\Column\Integer('tag') 
        ];

        $this->constraints = [
            new \Zend\Db\Sql\Ddl\Index\Index('post'),
            new \Zend\Db\Sql\Ddl\Index\Index('tag')
        ];
    }

     public function setTable($t){
        return $this;
    }
    
    public function getTable(){
        return $this->table;
    }
}