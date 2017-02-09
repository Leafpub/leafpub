<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2017 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models;

class Upload implements ModelInterface {
    protected static $_instance = '';

    public static function getModel(){
		if (self::$_instance == null){
			self::$_instance	=	new Tables\Upload();
		}
		return self::$_instance;
	}

    public static function getMany(array $options = []){
        return self::getModel()->select();
    }
    public static function getOne(array $options){}
    public static function save(Zend\Db\RowGateway\RowGatewayInterface $row){}
    public static function delete(Zend\Db\RowGateway\RowGatewayInterface $row){}
    
    public static function getUploadsToPost($postId){
        try {
            $table = new Tables\PostUploads();
            $select1 = $table->getSql()->select()
                                        ->columns(['upload'])
                                        ->where(function($wh) use($postId){
                                            $wh->equalTo('post', $postId);
                                        });

            $model = self::getModel();
            $select = self::getModel()->getSql()->select()
                                                ->columns(['slug'])
                                                ->where(function($wh) use($select1){
                                                    $wh->in('id', $select1);
                                                });
           
            return $model->selectWith($select)->toArray();
        } catch(\Exception $e){
            return false;
        }
    }
}