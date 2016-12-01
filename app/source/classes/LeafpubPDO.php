<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

/**
* LeafpubPDO
*
* an extension of the PDO class that supports table prefixing
*
*/
class LeafpubPDO extends \PDO {

    /**
    * Properties
    **/
    protected $table_prefix;
    protected $transactionCount = 0;

    /**
    * Adds the specified prefix to any word preceeded by two underscores in the statement
    *
    * @param String $statement
    * @return String
    *
    **/
    protected function addTablePrefix($statement) {
        // Regex example: https://regex101.com/r/yC5kO5/3
        return preg_replace('/\b__([a-z0-9_]+)\b/i', $this->table_prefix . '$1', $statement);
    }

    /**
    * Constructor
    *
    * @param String $dsn
    * @param null $user
    * @param null $password
    * @param array $driver_options
    * @param null $prefix
    * @return void
    *
    **/
    public function __construct($dsn, $user = null, $password = null, $driver_options = array(), $prefix = null) {
        $this->table_prefix = $prefix;
        parent::__construct($dsn, $user, $password, $driver_options);

        // Tell PDO to throw exceptions
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
    * Wrapper for exec()
    *
    * @param String $statement
    * @return int
    *
    **/
    public function exec($statement) {
        $statement = $this->addTablePrefix($statement);
        return parent::exec($statement);
    }

    /**
    * Wrapper for prepare()
    *
    * @param String $statement
    * @param array $driver_options
    * @return void
    *
    **/
    public function prepare($statement, $driver_options = array()) {
        $statement = $this->addTablePrefix($statement);
        return parent::prepare($statement, $driver_options);
    }

    /**
    * Wrapper for query()
    *
    * @param String $statement
    * @return PDOStatement
    *
    **/
    public function query($statement) {
        $statement = $this->addTablePrefix($statement);
        $args = func_get_args();

        if(count($args) > 1) {
            return call_user_func_array(array($this, 'parent::query'), $args);
        } else {
            return parent::query($statement);
        }
    }

    /**
    * Begins a transaction
    *
    * @return bool
    *
    **/
    public function beginTransaction() {
        if (!$this->transactionCounter++) {
            return parent::beginTransaction();
        }
        $this->exec('SAVEPOINT trans'.$this->transactionCounter);
        return $this->transactionCounter >= 0;
    }

    /**
    * Commit a transaction
    *
    * @return bool
    *
    **/
    public function commit() {
        if (!--$this->transactionCounter) {
            return parent::commit();
        }
        return $this->transactionCounter >= 0;
    }

    /**
    * Rollback a transaction
    *
    * @return bool
    *
    **/
    public function rollBack() {
        if (--$this->transactionCounter) {
            $this->exec('ROLLBACK TO trans'.$this->transactionCounter + 1);
            return true;
        }
        return parent::rollBack();
    }
    
}