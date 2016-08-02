<?php
//
// Postleaf\Renderer: methods for rendering handlebar templates
//
namespace Postleaf;

class Renderer extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Private methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Return admin helpers
    private static function loadHelpers($helpers = []) {
        $array = [];

        foreach((array) $helpers as $helper) {
            $path = self::path("/source/templates/helpers/$helper.php");
            if(file_exists($path)) {
                $array = array_merge($array, include $path);
            }
        }

        return $array;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Renders the specified handlebar template and returns the resulting HTML
    public static function render($options = []) {
        // Extract options
        $template = $data = $special_vars = $helpers = '';
        extract($options, EXTR_IF_EXISTS);

        // Get template info
        $template_dir = dirname($template);          // ex: /path/to/theme
        $template_file = basename($template);        // ex: post.hbs
        $template_name = self::fileName($template); // ex: post
        $theme_slug = self::slug(Setting::get('theme'));

        // Load the template
        if(!file_exists($template) || !$source = file_get_contents($template)) {
            throw new \Exception("Template missing: $template_file");
        }

        // Generate cache filename
        $cache_file = "hbs.$template_name.$theme_slug.php";

        // Does a cached template exist or is caching disabled?
        //
        // Note: even if caching is disabled, a cache file will still be generated as it's used to
        // render the template. However, when caching is disabled the template will be recompiled
        // on every request, regardless of whether or not a cache file exists.
        //
        if(!Cache::get($cache_file) || Setting::get('hbs_cache') !== 'on') {
            // Compile the template
            try {
                $output = '<?php ' . \LightnCandy\LightnCandy::compile($source, [
                    'flags' =>
                        \LightnCandy\LightnCandy::FLAG_ERROR_EXCEPTION |
                        \LightnCandy\LightnCandy::FLAG_HANDLEBARS |
                        \LightnCandy\LightnCandy::FLAG_PROPERTY |
                        \LightnCandy\LightnCandy::FLAG_BESTPERFORMANCE |
                        \LightnCandy\LightnCandy::FLAG_RUNTIMEPARTIAL,
                    'partialresolver' => function($cx, $name) use ($template_dir) {
                        // Search these locations for partials
                        foreach([
                            "$template_dir/$name.hbs",
                            "$template_dir/partials/$name.hbs"
                        ] as $file ) {
                            if(file_exists($file)) return file_get_contents($file);
                        }

                        return null;
                    },
                    'helpers' => self::loadHelpers($helpers)
                ]);
            } catch(\Exception $e) {
                throw new \Exception(
                    "Failed to compile $template_file. The compiler said: " . $e->getMessage()
                );
            }

            // Delete old cache files for this template
            try {
                Cache::flush("hbs.$template_name.");
            } catch(\Exception $e) {
                // Do nothing
            }

            // Create the cache file
            try {
                Cache::put($cache_file, $output);
            } catch(\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }

        // Merge special variables
        $special_vars = array_merge((array) $special_vars, [
            'cookies' => $_COOKIE,
            'postleaf' => [
                'version' => POSTLEAF_VERSION === '{{version}}' ? 'dev' : POSTLEAF_VERSION
            ],
            'request' => [
                'get' => $_GET,
                'post' => $_POST,
                'host'=> $_SERVER['HTTP_HOST'],
                'homepage' => self::isHomepage(),
                'ip' => $_SERVER['REMOTE_ADDR'],
                'method' => $_SERVER['REQUEST_METHOD'],
                'referer' => $_SERVER['HTTP_REFERER'],
                'time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ],
            'settings' => Setting::getAll(),
            'template' => $template_name,
            'user' => isset($options['user']) ? $options['user'] : Session::user()
        ]);

        // We can't use `set_error_handler()` to catch fatal errors, but we can register a shutdown
        // function to display any errors that might occur while rendering.
        register_shutdown_function(function() use($template_file) {
            $error = error_get_last();

            switch($error['type']) {
                case E_ERROR:
                case E_USER_ERROR:
                    ob_end_clean();
                    exit(
                        Error::system([
                            'title' => 'Template Error',
                            'message' =>
                                "Failed to render $template_file due to a PHP error. This was " .
                                "most likely caused by an error in the template. Please check " .
                                "the template for syntax errors."
                        ])
                    );
                    break;
            }
        });

        // Render the template
        try {
            // Enable output buffering to capture output because errors may occur after it's sent
            ob_start();
            $renderer = eval('?>' . Cache::get($cache_file));
            $html = $renderer($data, ['data' => $special_vars]);
            ob_end_flush();
        } catch(\Exception $e) {
            throw new \Exception("Failed to render $template_file: " . $e->getMessage());
        }

        return $html;
    }

}