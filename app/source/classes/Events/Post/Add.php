<?php
namespace Postleaf\Events\Post;

use Symfony\Component\EventDispatcher\Event;

class Add extends Event {
    const NAME = 'post.add';
    
    protected $post;
    
    public function __construct(Array &$postData){
        $this->post = $postData;
    }
    
    public function &getPost(){
        return $this->post;
    }
}

?>