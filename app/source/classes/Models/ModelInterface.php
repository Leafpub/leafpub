<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2017 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models;

interface ModelInterface {
    public static function getModel();
    public static function getMany(array $options = [], &$pagination = null);
    public static function getOne($data);
    public static function create($data);
    public static function edit($data);
    public static function delete($data);
}