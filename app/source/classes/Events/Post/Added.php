<?php
namespace Postleaf\Events\Post;

use Symfony\Component\EventDispatcher\Event;

class Added extends Event {
    const NAME = 'post.added';
    
    protected $post;
    
    public function __construct(Array &$postData){
        $this->post = $postData;
    }
    
    public function &getPost(){
        return $this->post;
    }
}

?>