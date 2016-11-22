<?php
//
// URL helpers for Handlebars
//
return [

    // Returns an admin URL
    'admin_url' => function($path, $options = null) {
        if(!$options) {
            $options = $path;
            $path = null;
        }

        // Add hash attributes as query string data
        if(is_array($options) && count($options['hash'])) {
            $path = rtrim($path, '/') . '/?' . http_build_query($options['hash'], null, '&', PHP_QUERY_RFC3986);
        }

        return \Leafpub\Admin::url($path);
    },

    // Returns an author URL
    'author_url' => function($author, $options = null) {
        if(!$options) {
            $options = $author;

            if(isset($options['_this']['author'])) {
                // Try this.author
                $author = $options['_this']['author'];
            } elseif(isset($options['_this']['slug'])) {
                // Try this.slug
                $author = $options['_this']['slug'];
            } else {
                // Nothing to fall back to
                return '';
            }
        }

        return \Leafpub\User::url($author, (int) $options['hash']['page']);
    },

    // Returns the blog index URL
    'blog_url' => function($path, $options = null) {
        return \Leafpub\Blog::url($options['hash']['page']);
    },

    // Returns the feed URL
    'feed_url' => function($options) {
        // Get hash arguments
        $author = $options['hash']['author'];
        $tag = $options['hash']['tag'];

        // Set feed options
        $feed_options = [];
        if($author) $feed_options['author'] = $author;
        if($tag) $feed_options['tag'] = $tag;

        return \Leafpub\Feed::url($feed_options);
    },

    // Returns a post URL
    'post_url' => function($slug, $options = null) {
        if(!$options) {
            $options = $slug;

            if(isset($options['_this']['slug'])) {
                // Try this.slug
                $slug = $options['_this']['slug'];
            } else {
                return '';
            }
        }

        return \Leafpub\Post::url($slug);
    },

    // Returns a search URL
    'search_url' => function($options) {
        $query = (string) $options['hash']['query'];
        $page = (int) $options['hash']['page'];

        // Empty queries get pushed to page 1
        if(!$query) $page = 1;

        return \Leafpub\Search::url($query, $page);
    },

    // Returns a tag URL
    'tag_url' => function($tag, $options = null) {
        if(!$options) {
            $options = $tag;

            if(isset($options['_this']['slug'])) {
                // Try this.slug
                $tag = $options['_this']['slug'];
            } else {
                return '';
            }
        }

        return \Leafpub\Tag::url($tag, (int) $options['hash']['page']);
    },

    // Returns the current theme's URL
    'theme_url' => function($path, $options = null) {
        return $options ?
            \Leafpub\Leafpub::url('content/themes', \Leafpub\Setting::get('theme'), $path) :
            \Leafpub\Leafpub::url('content/themes', \Leafpub\Setting::get('theme'));
    },

    // Returns the website's base URL
    'url' => function($path, $options = null) {
        if(!$options) {
            $options = $path;
            $path = '';
        }

        // If this is a fully qualified URL, return is as-is
        if(preg_match('/^(http:|https:|mailto:|\/\/:)/i', $path)) {
            return $path;
        } else {
            return \Leafpub\Leafpub::url($path);
        }
    }

];