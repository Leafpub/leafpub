<?php
//
// Postleaf\Blog: methods for working with the blog
//
namespace Postleaf;

class Blog extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Render blog index page
    public static function render($page = 1) {
        // Get the posts
        $posts = Post::getMany([
            'page' => $page,
            'items_per_page' => Setting::get('posts_per_page')
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
                    'title'=> Setting::get('title'),
                    'description' => Setting::get('tagline'),
                    // JSON linked data (schema.org)
                    'ld_json' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'Website',
                        'publisher' => Setting::get('title'),
                        'url' => parent::url(),
                        'image' => !empty(Setting::get('cover')) ?
                            parent::url(Setting::get('cover')) : null,
                        'description' => Setting::get('tagline')
                    ],
                    // Open Graph
                    'open_graph' => [
                        'og:type' => 'website',
                        'og:site_name' => Setting::get('title'),
                        'og:title' => Setting::get('title'),
                        'og:description' => Setting::get('tagline'),
                        'og:url' => parent::url(),
                        'og:image' => !empty(Setting::get('cover')) ?
                            parent::url(Setting::get('cover')) : null
                    ],
                    // Twitter Card
                    'twitter_card' => [
                        'twitter:card' => !empty(Setting::get('cover')) ?
                            'summary_large_image' : 'summary',
                        'twitter:site' => !empty(Setting::get('twitter')) ?
                            '@' . Setting::get('twitter') : null,
                        'twitter:title' => Setting::get('title'),
                        'twitter:description' => Setting::get('tagline'),
                        'twitter:url' => parent::url(),
                        'twitter:image' => !empty(Setting::get('cover')) ?
                            parent::url(Setting::get('cover')) : null
                    ]
                ],
            ],
            'helpers' => ['url', 'utility', 'theme']
        ]);
    }

    // Returns a blog URL
    public static function url($page = 1) {
        if(!mb_strlen(Setting::get('homepage'))) {
            // Default homepage
            return $page > 1 ?
                // example.com/page/2
                parent::url(Setting::get('frag_page'), $page) :
                // example.com
                parent::url();
        } else {
            // Custom homepage
            return $page > 1 ?
                // example.com/posts/page/2
                parent::url(
                    Setting::get('frag_blog'),
                    Setting::get('frag_page'),
                    $page
                ) :
                // example.com/posts/
                parent::url(Setting::get('frag_blog'));
        }
    }

}