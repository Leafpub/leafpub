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
        static::$widgets[$widgetData['name']] = $widgetData;
    }

    /**
    * Returns a rendered widget
    *
    * @param String/array $widgetName
    * @return String
    *
    */
    public static function getWidget($widget){
        $data = [];
        if (is_array($widget)){
            $widgetName = $widget['id'];
            $data = $widget;
        } else {
            $widgetName = $widget;
            $data['id'] = $widget;
        }

        if (isset(self::$widgets[$widgetName])){
            $widgetClass = self::$widgets[$widgetName]['class'];
            return $widgetClass::renderWidget($data);
        } else {
            return false;
        }
    }

    public static function getWidgets(){
        return self::$widgets;
    }

    public static function renderDashboard($userSlug){
        $data = Setting::getOne('dashboard_' . $userSlug);
        
        if ($data){
            $widgets = json_decode($data, true);
            foreach ($widgets as $widget){
                self::getLogger()->debug('Widget Id: ' . $widget->id);
                $html = self::getWidget($widget);
                if ($html){
                    $ret[] = ['widget' => $html];
                }
            }
        }
        
        return $ret;
    }
}
