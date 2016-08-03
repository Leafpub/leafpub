<?php
//
// Theme helpers for Handlebars
//
return [

    // Changes context to the specified author
    'author' => function($slug, $options = null) {
        if(!$options) {
            $options = $slug;

            if(is_array($options['_this']['author'])) {
                // Try this.author (array)
                $author = $options['_this']['author'];
            } elseif(is_string($options['_this']['author'])) {
                // Try this.author
                $author = \Postleaf\User::get($options['_this']['author']);
            }
        } else {
            // Get the author by slug
            $author = \Postleaf\User::get($slug);
        }

        // Do {{else}} if no author is found
        if(!$author) {
            return $options['inverse'] ? $options['inverse']() : '';
        }

        // Remove sensitive data
        unset($author['password'], $author['reset_token']);

        return $options['fn']($author);
    },

    // Output author bios as HTML instead of markdown
    'bio' => function($options) {
        return new \LightnCandy\SafeString(
            \Postleaf\Postleaf::markdownToHtml($options['_this']['bio'])
        );
    },

    // Returns the CSS classes that apply to the current body element
    'body_class' => function($options) {
        // Template class
        $class = $options['data']['template'] . '-template';

        // Homepage class
        if(\Postleaf\Postleaf::isHomepage()) {
            $class .= ' homepage';
        }

        // Pagination class
        if(isset($options['_this']['pagination'])) {
            $class .= ' page-' . $options['_this']['pagination']['current_page'];
        }

        return $class;
    },

    // Returns the current post's content
    'content' => function($options) {
        $content = $options['_this']['content'];
        $editable = mb_strtolower($options['hash']['editable']) === 'true';

        // Is the post being rendered in the editor?
        if($editable && $options['data']['meta']['editable']) {
            // If so, wrap in editable tags
            //
            // Note that content is also being inserted into the data-postleaf-html attribute inside
            // the div. We do this so we can grab the original markup once it's loaded into the
            // editor, as the code may have been altered by scripts.
            return new \LightnCandy\SafeString(
                '<div data-postleaf-id="post:content" data-postleaf-type="post-content" ' .
                'data-postleaf-html="' . htmlspecialchars($content) . '">' .
                $content .
                '</div>'
            );
        } else {
            // Otherwise, just return the raw HTML
            return new \LightnCandy\SafeString($content);
        }
    },

    // Output descriptions as HTML instead of markdown
    'description' => function($options) {
        return new \LightnCandy\SafeString(
            \Postleaf\Postleaf::markdownToHtml($options['_this']['description'])
        );
    },

    // Gets the website's navigation
    'navigation' => function($options) {
        // Decode nav from settings
        $items = (array) json_decode(\Postleaf\Setting::get('navigation'), true);

        // Generate `slug` and `current` values for each nav item
        foreach($items as $key => $value) {
            $items[$key]['slug'] = \Postleaf\Postleaf::slug($value['label']);
            $items[$key]['current'] = \Postleaf\Postleaf::isCurrentUrl($value['link']);
        }

        if(count($items)) {
            return $options['fn']([
                'items' => $items
            ]);
        } else {
            // No items, do {{else}}
            return $options['inverse'] ? $options['inverse']() : '';
        }
    },

    // Gets the next post and changes context
    'next_post' => function($slug, $options = null) {
        if(!$options) {
            $options = $slug;

            if(is_array($options['_this']['post'])) {
                // Try this.post.slug
                $slug = $options['_this']['post']['slug'];
            } elseif(isset($options['_this']['slug'])) {
                // Try this.slug
                $slug = $options['_this']['slug'];
            } else {
                // Nothing to fallback to
                $slug = null;
            }
        }

        // Get the previous post
        $post = \Postleaf\Post::getAdjacent($slug, [
            'direction' => 'next',
            'author' => $options['hash']['author'],
            'tag' => $options['hash']['tag']
        ]);

        // Was a post found?
        if(is_array($post)) {
            // Yep, change context
            return $options['fn']((array) $post);
        } else {
            // No post, do {{else}}
            return $options['inverse'] ? $options['inverse']() : '';
        }
    },

    // Changes context to the specified post
    'post' => function($slug, $options = null) {
        if(!$options) {
            $options = $slug;

            if(is_array($options['_this']['post'])) {
                // Try this.post as array
                $post = $options['_this']['post'];
            }
        } else {
            // Get the post by slug
            $post = \Postleaf\Post::get($slug);
        }

        // Do {{else}} if no post is found
        if(!$post) {
            return $options['inverse'] ? $options['inverse']() : '';
        }

        return $options['fn']($post);
    },

    // Returns the CSS classes that apply to the current post
    'post_class' => function($options) {
        $post = $options['_this'];

        // Build class
        $class = 'post';
        if($post['type'] === 'page') $class .= ' page';
        if($post['featured']) $class .= ' post-featured';
        if($post['sticky']) $class .= ' post-sticky';
        if($post['image']) $class .= ' post-image';
        foreach((array) $post['tags'] as $tag) {
            $class .= ' tag-' . $tag;
        }

        return $class;
    },

    // Handles the output for {{postleaf_foot}}
    'postleaf_foot' => function($options) {
        $html = '';

        // If we're editing a post, add required code
        if($options['data']['meta']['editable']) {
            // Inject TinyMCE
            $html .=
                '<!--{{postleaf_foot}}-->' .
                '<script src="' . htmlspecialchars(
                    \Postleaf\Postleaf::url(
                        'source/vendor/tinymce/tinymce/tinymce.min.js?v=' .
                        $options['data']['postleaf']['version']
                    )
                ) . '"></script>';
        }

        // Inject foot code
        $html .= "\n" . \Postleaf\Setting::get('foot_code');

        // Inject admin toolbar if the user is logged in and the post isn't editable or a preview
        if(
            \Postleaf\Session::isAuthenticated() &&
            !$options['data']['meta']['editable'] &&
            !$options['data']['meta']['preview']
        ) {
            // Render it
            $html .= \Postleaf\Renderer::render([
                'template' => \Postleaf\Postleaf::path(
                    'source/templates/partials/admin-toolbar.hbs'
                ),
                'data' => [
                    'items' => \Postleaf\Admin::getAdminToolbarItems(
                        $options['data']['template'],
                        $options['_this']
                    )
                ],
                'helpers' => ['url', 'utility', 'theme']
            ]);
        }

        // Return raw HTML
        return new \LightnCandy\SafeString($html);
    },

    // Handles the output for {{postleaf_head}}
    'postleaf_head' => function($options) {
        $html = '';

        // If we're editing a post, add required code
        if($options['data']['meta']['editable']) {
            // Inject Postleaf data and editor stylesheet
            $html .=
                '<!--{{postleaf_head}}-->' .
                '<script>window.postleaf = true;</script>' .
                '<link rel="stylesheet" href="' . htmlspecialchars(
                    \Postleaf\Postleaf::url(
                        'source/assets/css/editor.css?v=' .
                        $options['data']['postleaf']['version']
                    )
                ) . '">';
        }

        // Inject admin toolbar styles if the user is logged in and it's not a preview/editable post
        if(
            \Postleaf\Session::isAuthenticated() &&
            !$options['data']['meta']['editable'] &&
            !$options['data']['meta']['preview']
        ) {
            $html .=
                '<link rel="stylesheet" href="' .
                htmlspecialchars(
                    \Postleaf\Postleaf::url(
                        'source/assets/css/admin-toolbar.css?v=' .
                        $options['data']['postleaf']['version']
                    )
                ) .
                '">';
        }

        // Inject head code
        $html .= "\n" . \Postleaf\Setting::get('head_code');

        // Inject JSON linked data (schema.org)
        if(isset($options['data']['meta']['ld_json'])) {
            $html .=
                "\n<script type=\"application/ld+json\">" .
                json_encode($options['data']['meta']['ld_json'], JSON_PRETTY_PRINT) .
                "</script>";
        }

        // Inject Open Graph data
        if(is_array($options['data']['meta']['open_graph'])) {
            foreach($options['data']['meta']['open_graph'] as $key => $value) {
                if($value === null) continue;
                $html .= "\n<meta property=\"" . htmlspecialchars($key) . "\" content=\"" . htmlspecialchars($value) . "\">";
            }
        }

        // Inject Twitter Card data
        if(is_array($options['data']['meta']['twitter_card'])) {
            foreach($options['data']['meta']['twitter_card'] as $key => $value) {
                if($value === null) continue;
                $html .= "\n<meta name=\"" . htmlspecialchars($key) . "\" content=\"" . htmlspecialchars($value) . "\">";
            }
        }

        // Return raw HTML
        return new \LightnCandy\SafeString($html);
    },

    // Gets the previous post and changes context
    'previous_post' => function($slug, $options = null) {
        if(!$options) {
            $options = $slug;

            if(is_array($options['_this']['post'])) {
                // Try this.post.slug
                $slug = $options['_this']['post']['slug'];
            } elseif(isset($options['_this']['slug'])) {
                // Try this.slug
                $slug = $options['_this']['slug'];
            } else {
                // Nothing to fallback to
                $slug = null;
            }
        }

        // Get the previous post
        $post = \Postleaf\Post::getAdjacent($slug, [
            'direction' => 'previous',
            'author' => $options['hash']['author'],
            'tag' => $options['hash']['tag']
        ]);

        // Was a post found?
        if(is_array($post)) {
            // Yep, change context
            return $options['fn']((array) $post);
        } else {
            // No post, do {{else}}
            return $options['inverse'] ? $options['inverse']() : '';
        }
    },

    // Returns the approximate number of minutes to read $text
    'reading_time' => function($string, $options = null) {
        if(!$options) {
            $options = $string;

            if(isset($options['_this']['content'])) {
                // Try this.content
                $string = strip_tags($options['_this']['content']);
            } else {
                $string = '';
            }
        }

        // Get words per minute
        $words_per_minute = (int) $options['hash']['words_per_minute'];
        if($words_per_minute < 1) $words_per_minute = 225;

        // Get number of words
        $num_words = str_word_count($string);

        // Calculate average reading time in minutes (minimum 1 min)
        return max(1, ceil($num_words / $words_per_minute));
    },

    // Gets suggested posts
    'suggested_posts' => function($slug, $options = null) {
        if(!$options) {
            $options = $slug;

            if(is_array($options['_this']['post'])) {
                // Try this.post.slug
                $slug = $options['_this']['post']['slug'];
            } elseif(isset($options['_this']['slug'])) {
                // Try this.slug
                $slug = $options['_this']['slug'];
            } else {
                // Nothing to fallback to
                $slug = null;
            }
        }

        // Get count (defaults to 5)
        $count = (int) $options['hash']['count'];
        if($count < 1) $count = 5;

        // Get suggested posts
        $posts = \Postleaf\Post::getSuggested($slug, [
            'max' => $count,
            'author' => $options['hash']['author'],
            'tag' => $options['hash']['tag']
        ]);

        // Were any posts found?
        if(count($posts)) {
            return $options['fn'](['posts' => $posts]);
        } else {
            // No posts, do {{else}}
            return $options['inverse'] ? $options['inverse']() : '';
        }
    },

    // Changes context to the specified tag
    'tag' => function($slug, $options = null) {
        if(!$options) {
            $options = $slug;

            if(is_array($options['_this']['tag'])) {
                // Try this.tag as array
                $tag = $options['_this']['tag'];
            } elseif(isset($options['_this']['slug'])) {
                // Try this.slug
                $tag = \Postleaf\Tag::get($options['_this']['slug']);
            }
        } else {
            // Get the tag by slug
            $tag = \Postleaf\Tag::get($slug);
        }

        // Do {{else}} if no tag is found
        if(!$tag) {
            return $options['inverse'] ? $options['inverse']() : '';
        }

        return $options['fn']($tag);
    },

    // Returns a formatted list of tags
    'tags' => function($slugs, $options = null) {
        if(!$options) {
            $options = $slugs;

            if(isset($options['_this']['tags'])) {
                // Try this.tags
                $slugs = $options['_this']['tags'];
            } else {
                $slugs = null;
            }
        }

        // Convert CSV slugs to array
        if(is_string($slugs)) {
            $slugs = array_map('trim', explode(',', $slugs));
        }

        // Get data for each tag
        $tags = [];
        foreach((array) $slugs as $slug) {
            $tag = \Postleaf\Tag::get($slug);
            if($tag) $tags[] = $tag;
        }

        // Get attributes
        $before = (string) $options['hash']['before'];
        $after = (string) $options['hash']['after'];
        $and = (string) $options['hash']['and'];
        $autolink = mb_strtolower($options['hash']['autolink']) !== 'false';
        $separator = isset($options['hash']['separator']) ? $options['hash']['separator'] : ', ';

        // Sort tags by name
        if(is_array($tags)) {
            usort($tags, function($a, $b) {
                return mb_strtolower($a['name']) > mb_strtolower($b['name']);
            });
        }

        // Append each tag
        $content = [];
        foreach((array) $tags as $tag) {
            $c = '';
            if($autolink) {
                $c .=
                    '<a href="' . htmlspecialchars( \Postleaf\Tag::url($tag['slug']) ) . '">' .
                    htmlspecialchars($tag['name']) .
                    '</a>';
            } else {
                $c .= htmlspecialchars($tag['name']);
            }

            $content[] = $c;
        }

        // Add separators
        if(count($tags) > 1 && !empty($and)) {
            // If $and is set: tag1, tag2 and tag4
            $left = array_slice($content, 0, count($content) - 1);
            $right = $content[count($content) - 1];
            $content = implode($separator, $left) . $and . $right;
        } else {
            // If $and isn't set: tag1, tag2, tag3
            $content = implode($separator, $content);
        }

        // Add before/after if at least one tag exists
        if(count($tags)) $content = $before . $content . $after;

        return $autolink ? new \LightnCandy\SafeString($content) : $content;
    },

    // Returns the current post's title
    'title' => function($options) {
        $title = $options['_this']['title'];
        $editable = mb_strtolower($options['hash']['editable']) === 'true';

        // Is the post being rendered in the editor?
        if($editable && $options['data']['meta']['editable']) {
            // If so, wrap in editable tags and output raw
            //
            // Note that content is also being inserted into the data-postleaf-html attribute inside
            // the div. We do this so we can grab the original markup once it's loaded into the
            // editor, as the code may have been altered by scripts.
            return new \LightnCandy\SafeString(
                '<div data-postleaf-id="post:title" data-postleaf-type="post-title" ' .
                'data-postleaf-html="' . htmlspecialchars($title) . '">' .
                htmlspecialchars($title) .
                '</div>'
            );
        } else {
            // Otherwise, just return the title as-is
            return $title;
        }
    }

];