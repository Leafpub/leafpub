<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2017 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models;

use Leafpub\Leafpub;

class History extends AbstractModel {
    protected static $_instance;
    protected static $allowedCaller = [
        'Leafpub\\Controller\\APIController',
        'Leafpub\\Models\\Post'
    ];

    protected static function getModel(){
		if (self::$_instance == null){
			self::$_instance	=	new Tables\History();
		}
		return self::$_instance;
	}

     /**
    * Get all history for the specified post
    *
    * @param String $slug
    * @return mixed
    *
    **/
    public static function getMany(array $options = [], &$pagination = null){
        $history = [];
        try {
            $postId = Post::getOne($options['slug'])['id'];
            $model = self::getModel();
            $ret = $model->select(['post' => $postId])->toArray();
        } catch(\Exception $e){
            return false;
        }

        foreach($ret as $his){
            $history[] = self::normalize($his);
        }

        return $history;
    }

    /**
    * Get a history item
    *
    * @param int $id
    * @return mixed
    *
    **/
    public static function getOne($id){
        try {
            $history = self::getModel()->select(['id' => $id])->current();
            if (!$history) return false;
            $revision = self::normalize($history->getArrayCopy());
	        return $revision;
        } catch(\Exception $e){
            return false;
        }
    }

    /**
    * Adds a revision based on the specified post's current entry
    *
    * @param String $slug
    * @param bool $initial = false
    * @return mixed
    *
    **/
    public static function create($data){
        if (!self::isAllowedCaller()){
            return false;
        }
        list($slug, $initial) = $data;
        $post = Post::getOne($slug);
        if(!$post) return false;
        $post_id = $post['id'];
        $rev_date = Leafpub::localToUtc(date('Y-m-d H:i:s')); // convert to UTC
        $post_data = json_encode($post);
        $initial = $initial ? 1 : 0;

        try {
            $model = self::getModel();
            $model->insert([
                'post' => $post_id,
                'rev_date' => $rev_date,
                'post_data' => $post_data,
                'initial' => $initial
            ]);
            $id = (int) $model->getLastInsertValue();
        } catch(\Exception $e){
            return false;
        }

        return $id;
    }

    public static function edit($data){
        throw new \Exception('Not supported!');
    }

    /**
    * Delete a history record
    *
    * @param int $id
    * @return bool
    *
    **/
    public static function delete($id){
        if (!self::isAllowedCaller()){
            return false;
        }

        try {
            return self::getModel()->delete(['id' => $id]);
        } catch(\Exception $e){
            return false;
        }
    }

    /**
    * Remove all history linked to the specified post
    *
    * @param String $slug
    * @return bool
    *
    **/
    public static function flush($slug){
        if (!self::isAllowedCaller()){
            return false;
        }
        
        $postId = Post::getOne($slug)['id'];
         try {
            return self::getModel()->delete(['post' => $id]);
        } catch(\Exception $e){
            return false;
        }
    }

    /**
    * Normalize data types for certain fields
    *
    * @param array $revision
    * @return array
    *
    **/
    private static function normalize($revision) {
        // Cast to integer
        $revision['id'] = (int) $revision['id'];
        $revision['initial'] = (int) $revision['initial'];

        // Convert dates from UTC to local
        $revision['rev_date'] = Leafpub::utcToLocal($revision['rev_date']);

        // Decode JSON data
        $revision['post_data'] = json_decode($revision['post_data'], true);

        return $revision;
    }
    
}
