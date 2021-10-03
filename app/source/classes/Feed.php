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
 * Feed
 *
 * methods for working with the RSS feed
 *
 **/
class Feed extends Leafpub
{
    /**
     * Renders an RSS feed
     *
     * @param null $options
     *
     * @return string
     *
     **/
    public static function render($options = null)
    {
        // Merge options
        $options = array_merge([
            'author' => null,
            'tag' => null,
        ], $options);

        // Get posts for feed
        $posts = Post::getMany([
            'author' => $options['author'],
            'tag' => $options['tag'],
            'items_per_page' => Setting::getOne('posts_per_page'),
        ]);

        // Open feed
        $feed = '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $feed .= '    <channel>' . "\n";
        $feed .= '      <title>' . htmlspecialchars(Setting::getOne('title')) . '</title>' . "\n";
        $feed .= '      <atom:link href="' . htmlspecialchars(self::url($options)) . '" rel="self" type="application/rss+xml" />' . "\n";
        $feed .= '      <link>' . htmlspecialchars(self::url()) . '</link>' . "\n";
        $feed .= '      <description>' . htmlspecialchars(Setting::getOne('tagline')) . '</description>' . "\n";

        // Add feed items
        foreach ($posts as $post) {
            // Meta title or title
            $title = !empty($post['meta_title']) ?
                $post['meta_title'] :
                $post['title'];

            // Meta description or first 100 characters of content
            $description = !empty($post['meta_description']) ?
                $post['meta_description'] :
                self::getChars(strip_tags($post['content']), 200) . '…';

            $feed .= '      <item>' . "\n";
            $feed .= '        <title>' . htmlspecialchars($title) . '</title>' . "\n";
            $feed .= '        <link>' . htmlspecialchars(Post::url($post['slug'])) . '</link>' . "\n";
            $feed .= '        <pubDate>' . htmlspecialchars(date('r', strtotime($post['pub_date']))) . '</pubDate>' . "\n";
            $feed .= '        <guid>' . htmlspecialchars(Post::url($post['slug'])) . '</guid  >' . "\n";
            $feed .= '        <description>' . htmlspecialchars($description) . '</description>' . "\n";
            $feed .= '      </item>' . "\n";
        }

        // Close feed
        $feed .= '    </channel>' . "\n";
        $feed .= '</rss>';

        return $feed;
    }

    /**
     * Returns the feed URL
     *
     * @param null $options
     *
     * @return string
     *
     **/
    public static function url($options = null)
    {
        if (count($options) > 0) {
            // example.com/feed?author=name&tag=name
            return
                parent::url(Setting::getOne('frag_feed')) .
                '?' .
                http_build_query($options, "", '&', PHP_QUERY_RFC3986);
        }
        // example.com/feed
        return parent::url(Setting::getOne('frag_feed'));
    }
}
