<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */
 
namespace Leafpub\Importer\Dropins;
use Leafpub\Importer\AbstractImporter;

/**
* Ghost
*
* Parses a Ghost export XML
* @package Leafpub\Importer\Dropins
*
**/
class Ghost extends AbstractImporter {
    private $userKeys = array(
        'id' => 'id',
        'slug' => 'slug',
        'name' => 'name', 
        'password' => 'password',
        'email' => 'email', 
        'created_at' => 'created'
    );
    
    private $tagKeys = array(
        'id' => 'id', 
        'slug' => 'slug',
        'name' => 'name',
        'description' => 'description',
        'image' => 'cover', 
        'meta_title' => 'meta_title', 
        'meta_description' => 'meta_description', 
        'created_at' => 'created'
    );
    
    private $postKeys = array(
        'id' => 'id',
        'slug' => 'slug',
        'html' => 'content',
        'image' => 'image',
        'author_id' => 'author',
        'meta_title' => 'meta_title',
        'meta_description' => 'meta_description',
        'page' => 'page',
        'sticky' => 'sticky',
        'featured' => 'featured',
        'created_at' => 'created',
        'published_at' => 'pub_Date'
    );
    
    private $mediaKeys = array(
        'id' => 'id'    
    );
    
    private $ptKeys = array(
        'post_id' => 'post',
        'tag_id' => 'tag'
    );
    
    public function parseFile(){
        $data = json_decode(file_get_contents($this->_fileToParse));
        $posts = $data->db[0]->data->posts;
        $media = $data->db[0]->data->media;
        $users = $data->db[0]->data->users;
        $tags = $data->db[0]->data->tags;
        $pt = $data->db[0]->data->post_tags;
        
        foreach ($users as $u){
            $u = get_object_vars($u);
            $user = array();
            foreach($this->userKeys as $source => $target){
                $user[$target] = $u[$source];
            }
            $this->_user[$u['slug']] = $user;
        }
        
        foreach ($posts as $p){
            $p = get_object_vars($p);
            $post = array();
            foreach($this->postKeys as $source => $target){
                $post[$target] = $p[$source];
            }
            //$post['author'] = $this->_user[$p['author_id']]['slug'];
            $this->filterContent($post['content']);
            $this->_posts[$p['slug']] = $post;
        }
        
        foreach($media as $m){
            $m = get_object_vars($m);
            $medium = array();
            foreach($this->mediaKeys as $source => $target){
                $medium[$target] = $m[$source];
            }
            $this->_media[] = $medium;
        }
        
        foreach ($tags as $t){
            $t = get_object_vars($t);
            $tag = array();
            foreach($this->tagKeys as $source => $target){
                $tag[$target] = $t[$source];
            }
            $this->_tags[$t['slug']] = $tag;
        }
        
        foreach ($pt as $p_t){
            $p_t = get_object_vars($p_t);
            $t_p = array();
            foreach($this->ptKeys as $source => $target){
                $t_p[$target] = $p_t[$source];
            }
            $this->_post_tags[] = $tag;
        }
    }
}
?>