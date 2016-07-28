<?php
//
// Postleaf\Admin: methods for working with the admin panel
//
namespace Postleaf;

class Admin extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Returns an array of default and registered toolbar items
    public static function getAdminToolbarItems($template, $data) {
        $items = [];

        // Home
        $items[] = [
            'label' => 'Postleaf',
            'link' => self::url(),
            'class' => 'pl-home'
        ];

        // New post
        $items[] = [
            'label' => Language::term('new_post'),
            'link' => self::url('posts/new'),
            'class' => 'pl-new-post'
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
                'class' => 'pl-edit-post'
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
                'class' => 'pl-edit-author'
            ];
        }

        // Edit tag (owners, admins, and editors)
        if($template === 'tag' && Session::isRole(['owner', 'admin', 'editor'])) {
            $items[] = [
                'label' => Language::term('edit'),
                'link' => self::url('tags/' . rawurlencode($data['tag']['slug'])),
                'class' => 'pl-edit-tag'
            ];
        }

        return $items;
    }

    // Returns an array of default and registered menu items
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

        // Tags
        if(Session::isRole(['owner', 'admin', 'editor'])) {
            $items[] = [
                'title' => Language::term('tags'),
                'link' => Admin::url('tags'),
                'icon' => 'fa fa-tag'
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

        return $items;
    }

    // Renders an admin page
    public static function render($template, $data = null) {
        return Renderer::render([
            'template' => self::path("source/templates/$template.hbs"),
            'data' => $data,
            'special_vars' => [],
            'helpers' => ['admin', 'url', 'utility']
        ]);
    }

    // Returns an admin URL
    public static function url($path = null) {
        return parent::url(Setting::get('frag_admin'), $path);
    }

}