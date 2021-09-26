<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use Leafpub\Models\Setting;

/**
 * Widget
 *
 * methods for working with widgets
 *
 **/
class Widget extends Leafpub
{
    protected static array $widgets = [];

    /**
     * Adds a useable widget to the $widgets array
     *
     * @param array $widget - Format: ['name' => widgetName, 'class' => entryClassName]
     *
     * @throws \Exception
     *
     * @return void
     */
    public static function addWidget($widgetData)
    {
        static::$widgets[$widgetData['name']] = $widgetData;
    }

    /**
     * Returns a rendered widget
     *
     * @param String/array $widgetName
     *
     * @return string
     */
    public static function getWidget($widget)
    {
        $data = [];
        if (is_array($widget)) {
            $widgetName = $widget['id'];
            $data = $widget;
        } else {
            $widgetName = $widget;
            $data['id'] = $widget;
        }

        if (isset(self::$widgets[$widgetName])) {
            $widgetClass = self::$widgets[$widgetName]['class'];

            return $widgetClass::renderWidget($data);
        }

        return false;
    }

    public static function getWidgets()
    {
        return self::$widgets;
    }

    public static function renderDashboard($userSlug)
    {
        $data = Setting::getOne('dashboard_' . $userSlug);

        if ($data) {
            $widgets = json_decode($data, true);
            foreach ($widgets as $widget) {
                self::getLogger()->debug('Widget Id: ' . $widget->id);
                $html = self::getWidget($widget);
                if ($html) {
                    $ret[] = ['widget' => $html];
                }
            }
        }

        return $ret;
    }
}
