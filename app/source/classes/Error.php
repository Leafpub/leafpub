<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

/**
* Error
*
* methods for working with error pages
* @package Leafpub
*
**/
class Error extends Leafpub {
    const STD_ERR_CODE = '404';
    /**
    * Renders the error page
    *
    * @param String $code - The http status code
    * @param array $data - data to use in view
    * @param array $special - data for special_vars
    * @return mixed
    *
    **/
    public static function render($code = self::STD_ERR_CODE, $data = array(), $special = array()) {
        // Render it
        return Renderer::render([
            'template' => Theme::getErrorTemplate($code),
            'data' => $data,
            'special_vars' => array_merge([
                'meta' => [
                    'title' => Language::term('not_found'),
                    'description' => ''
                ]
            ], $special),
            'helpers' => ['url', 'utility', 'theme']
        ]);
    }

    /**
    * Renders the system error template and exits. This method should be called before any output
    * is sent to the browser and only when an irrecoverable error occurs.
    *
    * Important: This method should not use the handlebars engine, database resources, or any
    * assets over HTTP because it may be triggered any time, even before a database connection is
    * established.
    *
    * @param array $data
    * @return mixed
    *
    **/
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