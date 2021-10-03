<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models\Ddl\Column;

use Zend\Db\Sql\Ddl\Column\Column;

class Enum extends Column
{
    /**
     * @var string
     */
    protected $type = 'ENUM';

    /**
     * @var null|mixed[]
     */
    protected ?array $possibilities = null;

    /**
     * {@inheritDoc}
     *
     * @param array $possibilities
     */
    public function __construct($name, $possibilities = [], $nullable = false, $default = null, array $options = [])
    {
        $this->setPossibilities($possibilities);

        parent::__construct($name, $nullable, $default, $options);
    }

    /**
     * @param array $possibilities
     */
    public function setPossibilities($possibilities): self
    {
        $this->possibilities = (array) (array) (array) (array) (array) $possibilities;

        return $this;
    }

    public function getPossibilities(): ?array
    {
        return $this->possibilities;
    }

    /**
     * @return array
     */
    public function getExpressionData()
    {
        $data = parent::getExpressionData();

        if ($this->getPossibilitiesExpression() !== '') {
            $data[0][1][1] .= '(' . $this->getPossibilitiesExpression() . ')';
        }

        return $data;
    }

    protected function getPossibilitiesExpression(): string
    {
        return implode(',', $this->possibilities);
    }
}
