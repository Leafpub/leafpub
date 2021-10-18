<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models;

class PostMeta extends AbstractModel
{
    protected static ?\Leafpub\Models\Tables\PostMeta $_instance = null;

    protected static array $allowedCaller = [
        'Leafpub\\Controller\\AdminController',
        'Leafpub\\Controller\\APIController',
        'Leafpub\\Models\\Post',
    ];

    public static function getMany(array $options = [], &$pagination = null)
    {
        $slug = $options['post'];
        if (!$slug) {
            throw new \Exception('No post present!');
        }

        try {
            return self::getModel()->select(['post' => $slug])->toArray();
        } catch (\Exception $e) {
        }
    }

    public static function getOne($data)
    {
        $slug = $data['post'];
        if (!$slug) {
            throw new \Exception('No post present!');
        }
        $name = $data['name'];
        if (!$name) {
            throw new \Exception('No meta name present!');
        }

        try {
            $data = self::getModel()->select(['post' => $slug, 'name' => $name]);
            if ($data) {
                return $data->current();
            }
        } catch (\Exception $e) {
        }
    }

    public static function create($data)
    {
        if (!isset($data['post'])) {
            throw new \Exception('No post present!');
        }

        try {
            return self::getModel()->insert($data);
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
    }

    public static function edit($data)
    {
        $slug = $data['post'];
        if (!$slug) {
            throw new \Exception('No post present!');
        }
        unset($data['slug']);
        try {
            return self::getModel()->update($data, ['post' => $slug]);
        } catch (\Exception $e) {
        }
    }

    public static function delete($data)
    {
        $post = $data['post'];
        $name = $data['name'];

        if (!$post) {
            throw new \Exception('No post present!');
        }

        if (!$name) {
            throw new \Exception('No post present!');
        }

        try {
            return self::getModel()->delete(['post' => $post, 'name' => $name]);
        } catch (\Exception $e) {
        }
    }

    protected static function getModel()
    {
        if (self::$_instance == null) {
            self::$_instance = new Tables\PostMeta();
        }

        return self::$_instance;
    }
}
