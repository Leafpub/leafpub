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

class History extends CreateTable {
    protected $table = 'history';

    public function __construct($t = null){
        $this->table = \Leafpub\Models\Tables\TableGateway::$prefix . $this->table;

        $this->columns = [
            new \Zend\Db\Sql\Ddl\Column\Integer('id', false, null, ['auto_increment' => true]), 
            new \Zend\Db\Sql\Ddl\Column\Integer('post'),
            new \Zend\Db\Sql\Ddl\Column\Datetime('rev_date'),
            new Column\Longtext('post_data'), 
            new \Zend\Db\Sql\Ddl\Column\Binary('initial', null, false, 0), 
        ];

        $this->constraints = [
            new \Zend\Db\Sql\Ddl\Constraint\PrimaryKey('id'),
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