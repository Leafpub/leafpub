<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2017 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models;

class Plugin implements ModelInterface {
    protected static $_instance = '';

    public static function getModel(){
		if (self::$_instance == null){
			self::$_instance	=	new Tables\Plugin();
		}
		return self::$_instance;
	}

    public static function getMany(array $options = [], &$pagination = null){
        return self::getModel()->select();
    }
    public static function getOne($data){}
    public static function create($data){}
    public static function delete($data){}
    
}