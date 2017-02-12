<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2017 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models\Ddl\Column;

use \Zend\Db\Sql\Ddl\Column\Column;

class Enum extends Column {
    /**
     * @var string
     */
    protected $type = 'ENUM';

    /**
     * @var array
     */
    protected $possibilities;

    /**
     * {@inheritDoc}
     *
     * @param array $possibilities
     */
    public function __construct($name, $possibilities = [], $nullable = false, $default = null, array $options = []){
        $this->setPossibilities($possibilities);

        parent::__construct($name, $nullable, $default, $options);
    }

    /**
     * @param  array $possibilities
     *
     * @return self
     */
    public function setPossibilities($possibilities){
        $this->possibilities = (array) $possibilities;

        return $this;
    }

    /**
     * @return int
     */
    public function getPossibilities(){
        return $this->possibilities;
    }

    /**
     * @return string
     */
    protected function getPossibilitiesExpression() {
        return (string) implode(',', $this->possibilities);
    }

    /**
     * @return array
     */
    public function getExpressionData(){
        $data = parent::getExpressionData();

        if ($this->getPossibilitiesExpression()) {
            $data[0][1][1] .= '(' . $this->getPossibilitiesExpression() . ')';
        }

        return $data;
    }
}
