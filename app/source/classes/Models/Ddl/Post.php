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
    

class Post extends CreateTable {
    protected $table = 'posts';

    public function __construct($t = null){
        $this->table = \Leafpub\Models\Tables\TableGateway::$prefix . $this->table;

        $this->columns = [
            new \Zend\Db\Sql\Ddl\Column\Integer('id', false, null, ['auto_increment' => true]), 
            new \Zend\Db\Sql\Ddl\Column\Varchar('slug', 191), 
            new \Zend\Db\Sql\Ddl\Column\Datetime('created', false), //new \Zend\Db\Sql\Expression('NOW()')), NEEDS MySQL 5.6+
            new \Zend\Db\Sql\Ddl\Column\Datetime('pub_date', false), //new \Zend\Db\Sql\Expression('NOW()')), NEEDS MySQL 5.6+
            new \Zend\Db\Sql\Ddl\Column\Integer('author'),
            new \Zend\Db\Sql\Ddl\Column\Text('title'),
            new Column\Longtext('content'),
            new \Zend\Db\Sql\Ddl\Column\Text('image'),
            new \Zend\Db\Sql\Ddl\Column\Text('meta_title'),
            new \Zend\Db\Sql\Ddl\Column\Text('meta_description'),
            new Column\Enum('status', ["'published'", "'draft'"], false, 'published'),
            new Column\Boolean('page', null, false, 0),
            new Column\Boolean('featured', null, false, 0),
            new Column\Boolean('sticky', null, false, 0) 
        ];

        $this->constraints = [
            new \Zend\Db\Sql\Ddl\Constraint\PrimaryKey('id'),
            new \Zend\Db\Sql\Ddl\Constraint\UniqueKey('slug'),
            new \Zend\Db\Sql\Ddl\Index\Index('pub_date'),
            new Constraint\Fulltext(['slug', 'title'], 'title_fts'),
            new Constraint\Fulltext(['content'], 'content'),
            new Constraint\Fulltext(['slug', 'title', 'content'], 'all_fts')
        ];
    }

     public function setTable($t){
        return $this;
    }

    public function getTable(){
        return $this->table;
    }
}