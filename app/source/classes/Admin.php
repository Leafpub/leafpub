<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use Leafpub\Events\Navigation\AdminMenu,
    Leafpub\Models\Setting;

/**
* Admin
*
* methods for working with the admin panel
* @package Leafpub
*
**/
class Admin extends Leafpub {

    /**
    * Returns an array of default and registered toolbar items
    *
    * @param String $template
    * @param array $data
    * @return array
    *
    **/
    public static function getAdminToolbarItems($template, $data) {
        $items = [];

        // Home
        $items[] = [
            'label' => 'Leafpub',
            'link' => self::url(),
            'class' => 'lp-home'
        ];

        // New post
        $items[] = [
            'label' => Language::term('new_post'),
            'link' => self::url('posts/new'),
            'class' => 'lp-new-post'
        ];

        // Edit post (owners, admins, editors, and the post author)
        if(
            ($template === 'post' || $template === 'page') && (
                Session::isRole(['owner', 'admin', 'editor']) ||
                Session::user('slug') === $data['post']['author']
            )
        ) {
            $items[] = [
                'label' => Language::term('edit'),
                'link' => self::url('posts/' . rawurlencode($data['post']['slug'])),
                'class' => 'lp-edit-post'
            ];
        }

        // Edit author (owners, admins, or the author)
        if(
            $template === 'author' &&
            (
                Session::isRole(['owner', 'admin']) ||
                Session::user('slug') === $data['author']['slug']
            )
        ) {
            $items[] = [
                'label' => Language::term('edit'),
                'link' => self::url('users/' . rawurlencode($data['author']['slug'])),
                'class' => 'lp-edit-author'
            ];
        }

        // Edit tag (owners, admins, and editors)
        if($template === 'tag' && Session::isRole(['owner', 'admin', 'editor'])) {
            $items[] = [
                'label' => Language::term('edit'),
                'link' => self::url('tags/' . rawurlencode($data['tag']['slug'])),
                'class' => 'lp-edit-tag'
            ];
        }

        return $items;
    }

    /**
    * Returns an array of default and registered menu items
    *
    * @return array
    *
    **/
    public static function getMenuItems() {
        $items = [];

        // Edit profile
        $items[] = [
            'class' => 'edit-profile',
            'title' => Language::term('edit_profile'),
            'link' => Admin::url('users/' . rawurlencode(Session::user('slug'))),
            'icon' => 'fa fa-info-circle',
            'avatar' => empty(Session::user('avatar')) ? null : Session::user('avatar')
        ];

        if (Setting::getOne('showDashboard') === 'on'){
            // Home
            $items[] = [
                'title' => Language::term('dashboard'),
                'link' => Admin::url(),
                'icon' => 'fa fa-home'
            ];
        }

        // New post
        $items[] = [
            'title' => Language::term('new_post'),
            'link' => Admin::url('posts/new'),
            'icon' => 'fa fa-plus'
        ];

        // Posts
        $items[] = [
            'title' => Language::term('posts'),
            'link' => Admin::url('posts'),
            'icon' => 'fa fa-file-text'
        ];

        // Uploads
        $items[] = [
            'title' => Language::term('uploads'),
            'link' => Admin::url('uploads'),
            'icon' => 'fa fa-camera'
        ];

        // Tags
        if(Session::isRole(['owner', 'admin', 'editor'])) {
            $items[] = [
                'title' => Language::term('tags'),
                'link' => Admin::url('tags'),
                'icon' => 'fa fa-tags'
            ];
        }

        // Navigation
        if(Session::isRole(['owner', 'admin'])) {
            $items[] = [
                'title' => Language::term('navigation'),
                'link' => Admin::url('navigation'),
                'icon' => 'fa fa-map'
            ];
        }

        // Users
        if(Session::isRole(['owner', 'admin'])) {
            $items[] = [
                'title' => Language::term('users'),
                'link' => Admin::url('users'),
                'icon' => 'fa fa-user'
            ];
        }

        // Plugins
        if (Session::isRole(['owner', 'admin'])){
            $items[] = [
                'title' => Language::term('plugins'),
                'link' => Admin::url('plugins'),
                'icon' => 'fa fa-plug'
            ];
        }

        // Settings
        if(Session::isRole(['owner', 'admin'])) {
            $items[] = [
                'title' => Language::term('settings'),
                'link' => Admin::url('settings'),
                'icon' => 'fa fa-cog'
            ];
        }

        // Logout
        $items[] = [
            'title' => Language::term('logout'),
            'link' => Admin::url('logout'),
            'icon' => 'fa fa-power-off'
        ];

        $evt = new AdminMenu($items);
        Leafpub::dispatchEvent(AdminMenu::NAME, $evt);
        return $evt->getEventData();
    }

    /**
    * Renders an admin page
    *
    * @param String $template
    * @param null $data
    * @return mixed
    *
    **/
    public static function render($template, $data = null) {
        return Renderer::render([
            'template' => self::path("source/templates/$template.hbs"),
            'data' => $data,
            'special_vars' => [],
            'helpers' => ['admin', 'url', 'utility']
        ]);
    }

    /**
    * Returns an admin URL
    *
    * @param null $path
    * @return String
    *
    **/
    public static function url($path = null) {
        return parent::url(Setting::getOne('frag_admin'), $path);
    }

}