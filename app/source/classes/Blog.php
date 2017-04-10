<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use Leafpub\Models\Post,
    Leafpub\Models\Setting;

/**
* Blog
*
*  methods for working with the blog
* @package Leafpub
*
**/ 
class Blog extends Leafpub {

    /**
    * Render blog index page
    *
    * @param 1 $page
    * @return mixed
    *
    **/
    public static function render($page = 1) {
        // Get the posts
        $posts = Post::getMany([
            'page' => $page,
            'items_per_page' => Setting::getOne('posts_per_page'),
            //'end_date' => null
        ], $pagination);

        // Make sure the requested page exists
        if($page > $pagination['total_pages']) return false;

        // Add previous/next links to pagination
        $pagination['next_page_url'] = $pagination['next_page'] ?
            self::url($pagination['next_page']) : null;
        $pagination['previous_page_url'] = $pagination['previous_page'] ?
            self::url($pagination['previous_page']) : null;

        // Render it
        return Renderer::render([
            'template' => Theme::getPath('blog.hbs'),
            'data' => [
                'posts' => $posts,
                'pagination' => $pagination
            ],
            'special_vars' => [
                'meta' => [
                    'title'=> Setting::getOne('title'),
                    'description' => Setting::getOne('tagline'),
                    // JSON linked data (schema.org)
                    'ld_json' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'Website',
                        'publisher' => Setting::getOne('title'),
                        'url' => parent::url(),
                        'image' => !empty(Setting::getOne('cover')) ?
                            parent::url(Setting::getOne('cover')) : null,
                        'description' => Setting::getOne('tagline')
                    ],
                    // Open Graph
                    'open_graph' => [
                        'og:type' => 'website',
                        'og:site_name' => Setting::getOne('title'),
                        'og:title' => Setting::getOne('title'),
                        'og:description' => Setting::getOne('tagline'),
                        'og:url' => parent::url(),
                        'og:image' => !empty(Setting::getOne('cover')) ?
                            parent::url(Setting::getOne('cover')) : null
                    ],
                    // Twitter Card
                    'twitter_card' => [
                        'twitter:card' => !empty(Setting::getOne('cover')) ?
                            'summary_large_image' : 'summary',
                        'twitter:site' => !empty(Setting::getOne('twitter')) ?
                            '@' . Setting::getOne('twitter') : null,
                        'twitter:title' => Setting::getOne('title'),
                        'twitter:description' => Setting::getOne('tagline'),
                        'twitter:url' => parent::url(),
                        'twitter:image' => !empty(Setting::getOne('cover')) ?
                            parent::url(Setting::getOne('cover')) : null
                    ]
                ],
            ],
            'helpers' => ['url', 'utility', 'theme']
        ]);
    }

    /**
    * Returns a blog URL
    * 
    * @param 1 $page
    * @return String
    *
    **/
    public static function url($page = 1) {
        if(!mb_strlen(Setting::getOne('homepage'))) {
            // Default homepage
            return $page > 1 ?
                // example.com/page/2
                parent::url(Setting::getOne('frag_page'), $page) :
                // example.com
                parent::url();
        } else {
            // Custom homepage
            return $page > 1 ?
                // example.com/posts/page/2
                parent::url(
                    Setting::getOne('frag_blog'),
                    Setting::getOne('frag_page'),
                    $page
                ) :
                // example.com/posts/
                parent::url(Setting::getOne('frag_blog'));
        }
    }

}