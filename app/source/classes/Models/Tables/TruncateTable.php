<?php

namespace Leafpub\Models\Tables;

use \Zend\Db\Adapter\ParameterContainer,
    \Zend\Db\Adapter\Platform\PlatformInterface,
    \Zend\Db\Adapter\Driver\DriverInterface,
    \Zend\Db\Sql\AbstractPreparableSql,
     \Zend\Db\Sql\TableIdentifier;

class TruncateTable extends AbstractPreparableSql {
    /**@#+
     * @const string
     */
    const SPECIFICATION_TRUNCATE = 'truncate';
    /**@#-*/

    /**
     * @var string[]
     */
    protected $specifications = [
        self::SPECIFICATION_TRUNCATE => /* @lang SQL */ 'TRUNCATE TABLE %1$s',
    ];

    /**
     * @var string|TableIdentifier
     */
    protected $table = '';

    /**
     * @param null|string|TableIdentifier $table
     */
    public function __construct($table = null){
        if ($table) {
            $this->table($table);
        }
    }

    /**
     * @param  string|TableIdentifier $table
     * @return self
     */
    public function table($table){
        $this->table = $table;
        return $this;
    }

    /**
     * @param  PlatformInterface       $platform
     * @param  DriverInterface|null    $driver
     * @param  ParameterContainer|null $parameterContainer
     * @return string
     */
    protected function processTruncate(PlatformInterface $platform, DriverInterface $driver = null, ParameterContainer $parameterContainer = null){
        return sprintf(
            $this->specifications[static::SPECIFICATION_TRUNCATE],
            $this->resolveTable($this->table, $platform, $driver, $parameterContainer)
        );
    }
}