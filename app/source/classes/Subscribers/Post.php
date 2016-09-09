<?php
namespace Postleaf\Subscribers;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Postleaf\Events\Post\Add;
use Postleaf\Events\Post\Added;

class Post implements EventSubscriberInterface {
    
    public static function getSubscribedEvents(){
        return array(
            Add::NAME => 'onPostAdd',
            Added::NAME => 'onPostAdded'
        );
    }
    
    public function onPostAdd(Add $add){
        $post = $add->getPost();
    }
    
    public function onPostAdded(Added $added){
        $post = $added->getPost();
    }
}
?>