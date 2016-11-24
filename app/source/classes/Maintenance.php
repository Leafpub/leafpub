<?php

namespace Leafpub;

class Maintenance extends Leafpub {

    public function render(){
        // Check if the theme has a maintenancen.hbs
        $template = Theme::getPath('maintenance.hbs');
        if (!$source = file_get_contents($template)){
            // Fallback template
            $template = self::path('source/templates/maintenance.hbs');
        }
        
        // We provide the use of HTML in the message, 
        // so we can't use @settings.maintenance-message in the template file.
        // We need to use the content variable, because the content helper parses this variable
        // and returns the raw HTML.
        return Renderer::render([
            'template' => $template,
            'data' => [
                'content' => Setting::get('maintenance-message'), 
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