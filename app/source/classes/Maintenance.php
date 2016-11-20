<?php

namespace Postleaf;

class Maintenance extends Postleaf {

    public function render(){
        return Renderer::render([
            'template' => Theme::getPath('maintenance.hbs'),
            'data' => null,
            'special_vars' => [
                'meta' => [
                    'title' => Language::term('maintenance'),
                    'description' => ''
                ]
            ],
            'helpers' => ['url', 'utility', 'theme']
        ]);
    }
}