<?php
//
// Postleaf\Error: methods for working with error pages
//
namespace Postleaf;

class Error extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Renders the error page
    public static function render() {
        // Render it
        return Renderer::render([
            'template' => Theme::getPath('error.hbs'),
            'data' => null,
            'special_vars' => [
                'meta' => [
                    'title' => Language::term('not_found'),
                    'description' => ''
                ]
            ],
            'helpers' => ['url', 'utility', 'theme']
        ]);
    }

    // Renders the system error template and exits. This method should be called before any output
    // is sent to the browser and only when an irrecoverable error occurs.
    //
    // Important: This method should not use the handlebars engine, database resources, or any
    // assets over HTTP because it may be triggered any time, even before a database connection is
    // established.
    public static function system($data) {
        // Get the template
        $template = self::path('source/templates/error.system.hbs');
        $html = file_get_contents($template);

        // Convert image to data URI
        $image = 'data:image/png;base64,' . base64_encode(
            file_get_contents(self::path('source/assets/img/logo-color.png'))
        );

        // Update placeholders
        $html = str_replace('{{logo}}', htmlspecialchars($image), $html);
        $html = str_replace('{{title}}', htmlspecialchars($data['title']), $html);
        $html = str_replace('{{message}}', htmlspecialchars($data['message']), $html);
        $html = str_replace('{{host}}', htmlspecialchars($_SERVER['HTTP_HOST']), $html);

        return $html;
    }

}