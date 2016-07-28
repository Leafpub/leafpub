<?php
//
// Postleaf\Feed: methods for working with the RSS feed
//
namespace Postleaf;

class Feed extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Renders an RSS feed
    public static function render($options = null) {
        // Merge options
        $options = array_merge([
            'author' => null,
            'tag' => null
        ], $options);

        // Get posts for feed
        $posts = Post::getMany([
            'author' => $options['author'],
            'tag' => $options['tag'],
            'items_per_page' => Setting::get('posts_per_page')
        ]);

        // Open feed
        $feed  = '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $feed .= '    <channel>' . "\n";
        $feed .= '      <title>' . htmlspecialchars(Setting::get('title')) . '</title>' . "\n";
        $feed .= '      <atom:link href="' . htmlspecialchars(self::url($options)) . '" rel="self" type="application/rss+xml" />' . "\n";
        $feed .= '      <link>' . htmlspecialchars(self::url()) . '</link>' . "\n";
        $feed .= '      <description>' . htmlspecialchars(Setting::get('tagline')) . '</description>' . "\n";

        // Add feed items
        foreach($posts as $post) {
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

    // Returns the feed URL
    public static function url($options = null) {
        if(count($options)) {
            // example.com/feed?author=name&tag=name
            return
                parent::url(Setting::get('frag_feed')) .
                '?' .
                http_build_query($options, null, '&', PHP_QUERY_RFC3986);
        } else {
            // example.com/feed
            return parent::url(Setting::get('frag_feed'));
        }
    }

}