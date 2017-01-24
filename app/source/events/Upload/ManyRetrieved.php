<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2017 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */
namespace Leafpub\Events\Upload;

use Leafpub\Events\LeafpubEvent;

class ManyRetrieved extends LeafpubEvent {
    const NAME = 'uploads.retrieved';
}

?>