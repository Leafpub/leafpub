<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use Leafpub\Models\Post;
use Leafpub\Models\Setting;

/**
 * Search
 *
 * methods for working with search pages
 *
 **/
class Search extends Leafpub
{
    /**
     * Renders a search page
     *
     * @param string $query
     * @param in     $page
     *
     * @return mixed
     *
     **/
    public static function render($query, $page = 1)
    {
        // Get the search's posts
        if (mb_strlen($query)) {
            $posts = Post::getMany([
                'query' => (string) $query,
                'page' => $page,
                'items_per_page' => Setting::getOne('posts_per_page'),
            ], $pagination);
        } else {
            $posts = false;
        }

        // Make sure the requested page exists
        if ($page > $pagination['total_pages']) {
            return false;
        }

        // Add previous/next links to pagination
        $pagination['next_page_url'] = $pagination['next_page'] ?
            self::url($query, $pagination['next_page']) : null;
        $pagination['previous_page_url'] = $pagination['previous_page'] ?
            self::url($query, $pagination['previous_page']) : null;

        // Determine meta title based on query
        if (mb_strlen($query)) {
            $meta_title = Language::term('search_results_for_{query}', [
                'query' => $query,
            ]);
        } else {
            $meta_title = Language::term('search');
        }

        // Render it
        return Renderer::render([
            'template' => Theme::getPath('search.hbs'),
            'data' => [
                'query' => $query,
                'posts' => $posts,
                'pagination' => $pagination,
            ],
            'special_vars' => [
                'meta' => [
                    'title' => $meta_title,
                    'description' => null,
                    // JSON linked data (schema.org)
                    'ld_json' => [
                        '@context' => 'http://schema.org',
                        '@type' => 'WebSite',
                        'url' => parent::url(),
                        'potentialAction' => [
                            '@type' => 'SearchAction',
                            'target' => self::url($query),
                            'query-input' => $query,
                        ],
                    ],
                    // Open Graph
                    'open_graph' => [
                        'og:type' => 'website',
                        'og:site_name' => Setting::getOne('title'),
                        'og:title' => 'Search &middot; ' . Setting::getOne('title'),
                        'og:description' => !empty($query) ?
                            'Search results for “' . htmlspecialchars($query) . '”' : null,
                        'og:url' => self::url($query),
                        'og:image' => !empty(Setting::getOne('cover')) ?
                            parent::url(Setting::getOne('cover')) : null,
                    ],
                    // Twitter Card
                    'twitter_card' => [
                        'twitter:card' => !empty(Setting::getOne('cover')) ?
                            'summary_large_image' : 'summary',
                        'twitter:site' => !empty(Setting::getOne('twitter')) ?
                            '@' . Setting::getOne('twitter') : null,
                        'twitter:title' => 'Search &middot; ' . Setting::getOne('title'),
                        'twitter:description' => !empty($query) ?
                            'Search results for “' . htmlspecialchars($query) . '”' : null,
                        'twitter:url' => self::url($query),
                        'twitter:image' => !empty(Setting::getOne('cover')) ?
                            parent::url(Setting::getOne('cover')) : null,
                    ],
                ],
            ],
            'helpers' => ['url', 'utility', 'theme'],
        ]);
    }

    // Returns a search URL
    public static function url($query = '', $page = 1)
    {
        // Remove slashes from search queries because they don't play nice with the server
        $query = str_replace(['/', '\\'], ' ', $query);

        return $page > 1 && mb_strlen($query) ?
            // example.com/search/query/page/2
            parent::url(
                Setting::getOne('frag_search'),
                rawurlencode($query),
                Setting::getOne('frag_page'),
                $page
            ) :
            // example.com/search/query
            parent::url(Setting::getOne('frag_search'), rawurlencode($query));
    }
}
