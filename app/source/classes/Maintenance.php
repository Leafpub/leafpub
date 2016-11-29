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
* Maintenance
*
* methods for maintenance mode
* @package Leafpub
*
**/
class Maintenance extends Leafpub {

    /**
    * Renders the maintenance page. If the actual theme hasn't a maintenance.hbs, render our core maintenance page
    *
    * @return mixed
    *
    **/
    public function render(){
        // Check if the theme has a maintenancen.hbs
        $template = Theme::getPath('maintenance.hbs');
        if (!$source = file_get_contents($template)){
            // Fallback template
            $template = self::path('source/templates/maintenance.hbs');
        }
        
        // We provide the use of HTML in the message, 
        // so we can't use @settings.maintenance_message in the template file.
        // We need to use the content variable, because the content helper parses this variable
        // and returns the raw HTML.
        return Renderer::render([
            'template' => $template,
            'data' => [
                'content' => Setting::get('maintenance_message'), 
            ],
            'special_vars' => [
                'meta' => [
                    'title' => Language::term('maintenance'),
                    'description' => Language::term('maintenance')
                ]
            ],
            'helpers' => ['url', 'utility', 'theme']
        ]);
    }
}