<?php
//
// Admin helpers for Handlebars
//
return [
    // Gets an author and changes context
    'admin_author' => function($slug, $options = null) {
        $author = \Leafpub\Models\User::getOne($slug);

        // Do {{else}} if no author is found
        if(!$author) {
            return $options['inverse'] ? $options['inverse']() : '';
        }

        // Remove sensitive data
        unset($author['password'], $author['reset_token']);

        return $options['fn']($author);
    },

    // Gets all admin menu items
    'admin_menu' => function() {
        $args = func_get_args();
        $options = end($args);
        $items = \Leafpub\Admin::getMenuItems();

        // Generate `current` value for each item
        foreach($items as $key => $value) {
            $items[$key]['current'] = \Leafpub\Leafpub::isCurrentUrl($value['link']);
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

    // Outputs admin scripts
    'admin_scripts' => function() {
        $args = func_get_args();
        $options = end($args);
        $html = '';

        foreach((array) $options['_this']['scripts'] as $script) {
            // If this is a fully qualified URL, return is as-is
            if(preg_match('/^(http:|https:|mailto:|\/\/:)/i', $script)) {
                $src = $script;
            } else {
                $src =
                    self::url('source/assets/js', $script) . '?v=' .
                    $options['data']['leafpub']['version'];
            }
            $html .= '<script src="' . htmlspecialchars($src) . '"></script>';
        }

        if (isset($options['_this']['plugin_scripts'])){
             foreach((array) $options['_this']['plugin_scripts'] as $name => $script) {
                  $src =
                    self::url('content/plugins/' . $name, $script) . '?v=' .
                    $options['data']['leafpub']['version'];
                 $html .= '<script src="' . htmlspecialchars($src) . '"></script>';
             }
        }

        return new \LightnCandy\SafeString($html);
    },

    // Outputs admin styles
    'admin_styles' => function() {
        $args = func_get_args();
        $options = end($args);
        $html = '';

        foreach((array) $options['_this']['styles'] as $style) {
            $href =
                self::url('source/assets/css', $style) . '?v=' .
                $options['data']['leafpub']['version'];
            $html .= '<link rel="stylesheet" href="' . htmlspecialchars($href) . '">';
        }

        return new \LightnCandy\SafeString($html);
    },

     'widget' => function($options){
        $widget = $content = $options['_this']['widget'];
        return new \LightnCandy\SafeString($widget);
    },
];