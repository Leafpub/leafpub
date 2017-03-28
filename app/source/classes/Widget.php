<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use DirectoryIterator,
    Leafpub\Models\Setting;

/**
* Widget
*
* methods for working with widgets
* @package Leafpub
*
**/
class Widget extends Leafpub {
    protected static $widgets = [];

    /**
    * Adds a useable widget to the $widgets array
    *
    * @param array $widget - Format: ['name' => widgetName, 'class' => entryClassName]
    * @return void
    * @throws \Exception
    *
    */
    public static function addWidget($widgetData){
        self::$widgets[$widgetData['name']] = $widgetData['class'];
    }

    /**
    * Returns a rendered widget
    *
    * @param String $widgetName
    * @return String
    *
    */
    public static function getWidget($widgetName){
        $widgetClass = self::$widgets[$widgetName];

        return $widgetClass::render();
    }

    public static function renderDashboard($userSlug){
        $data = Setting::getOne('dashboard_' . $userSlug);
        
        $widgets = json_decode($data);
        foreach ($widgets as $widget){
            self::getLogger()->debug('Widget Id: ' . $widget->id);
        }
        
        return [
            [
                    'widget' => '<div class="grid-stack-item"
                data-gs-x="4" data-gs-y="0"
                data-gs-width="4" data-gs-height="4" id="zwei">
                <div class="grid-stack-item-content card">
                    <div class="card-header">
                        LaLaLand
                    </div>
                    <div class="card-block">
                        <h4 class="card-title">Title2</h4>
                        <p class="card-text">
                            Test Text
                        </p>
                    </div>
                </div>
            </div>'
            ]
        ];
    }
}
