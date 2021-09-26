<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models\Tables;

use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\AbstractPreparableSql;
use Zend\Db\Sql\TableIdentifier;

class TruncateTable extends AbstractPreparableSql
{
    /**@#+
     * @const string
     */
    public const SPECIFICATION_TRUNCATE = 'truncate';
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
     * @param string|TableIdentifier|null $table
     */
    public function __construct($table = null)
    {
        if ($table) {
            $this->table($table);
        }
    }

    /**
     * @param string|TableIdentifier $table
     *
     * @return self
     */
    public function table($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @return string
     */
    protected function processTruncate(PlatformInterface $platform, DriverInterface $driver = null, ParameterContainer $parameterContainer = null)
    {
        return sprintf(
            $this->specifications[static::SPECIFICATION_TRUNCATE],
            $this->resolveTable($this->table, $platform, $driver, $parameterContainer)
        );
    }
}
