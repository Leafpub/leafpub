<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */
namespace Leafpub\Events\Tag;

use Leafpub\Events\LeafpubEvent;

class Deleted extends LeafpubEvent {
    const NAME = 'tag.deleted';
}

?>