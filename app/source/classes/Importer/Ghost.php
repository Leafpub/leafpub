<?php
namespace Postleaf\Importer;

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
        'author' => 'author',
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
            $user = array();
            foreach($userKeys as $source => $target){
                $user[$target] = $u[$source];
            }
            $this->_users[] = $user;
        }
        
        foreach ($posts as $p){
            $post = array();
            foreach($postKeys as $source => $target){
                $post[$target] = $p[$source];
            }
            $this->_posts[] = $post;
        }
        
        foreach($media as $m){
            $medium = array();
            foreach($mediaKeys as $source => $target){
                $medium[$target] = $m[$source];
            }
            $this->_media[] = $medium;
        }
        
        foreach ($tags as $t){
            $tag = array();
            foreach($tagKeys as $source => $target){
                $tag[$target] = $t[$source];
            }
            $this->_tags[] = $tag;
        }
        
        foreach ($pt as $p_t){
            $t_p = array();
            foreach($ptKeys as $source => $target){
                $t_p[$target] = $p_t[$source];
            }
            $this->_post_tags[] = $tag;
        }
    }
}
?>