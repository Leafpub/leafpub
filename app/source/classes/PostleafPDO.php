<?php
//
//  Postleaf\PostleafPDO: an extension of the PDO class that supports table prefixing
//
namespace Postleaf;

class PostleafPDO extends \PDO {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Properties
    ////////////////////////////////////////////////////////////////////////////////////////////////

    protected $table_prefix;

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Protected methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Adds the specified prefix to any word preceeded by two underscores in the statement
    protected function addTablePrefix($statement) {
        // Regex example: https://regex101.com/r/yC5kO5/3
        return preg_replace('/\b__([a-z0-9_]+)\b/i', $this->table_prefix . '$1', $statement);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Constructor
    public function __construct($dsn, $user = null, $password = null, $driver_options = array(), $prefix = null) {
        $this->table_prefix = $prefix;
        parent::__construct($dsn, $user, $password, $driver_options);

        // Tell PDO to throw exceptions
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    // Wrapper for exec()
    public function exec($statement) {
        $statement = $this->addTablePrefix($statement);
        return parent::exec($statement);
    }

    // Wrapper for prepare()
    public function prepare($statement, $driver_options = array()) {
        $statement = $this->addTablePrefix($statement);
        return parent::prepare($statement, $driver_options);
    }

    // Wrapper for query()
    public function query($statement) {
        $statement = $this->addTablePrefix($statement);
        $args = func_get_args();

        if(count($args) > 1) {
            return call_user_func_array(array($this, 'parent::query'), $args);
        } else {
            return parent::query($statement);
        }
    }

}