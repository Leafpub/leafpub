<?php
namespace Leafpub\Events;

interface ILeafpubEvent {
    // returns the event data
    public function getEventData();
    // sets the event data
    public function setEventData($data);
}
?>