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

use Leafpub\Events\Tag\Add;
use Leafpub\Events\Tag\Added;
use Leafpub\Events\Tag\BeforeRender;
use Leafpub\Events\Tag\Delete;
use Leafpub\Events\Tag\Deleted;
use Leafpub\Events\Tag\ManyRetrieve;
use Leafpub\Events\Tag\ManyRetrieved;
use Leafpub\Events\Tag\Retrieve;
use Leafpub\Events\Tag\Retrieved;
use Leafpub\Events\Tag\Update;
use Leafpub\Events\Tag\Updated;
use Leafpub\Leafpub;
use Leafpub\Renderer;
use Leafpub\Theme;

class Tag extends AbstractModel
{
    /**
     * Constants
     **/
    public const ALREADY_EXISTS = 1;
    public const INVALID_NAME = 2;
    public const INVALID_SLUG = 3;
    public const NOT_FOUND = 4;
    protected static ?\Leafpub\Models\Tables\Tag $_instance = null;

    protected static $allowedCaller = [
        'Leafpub\\Controller\\AdminController',
        'Leafpub\\Controller\\APIController',
        'Leafpub\\Models\\Post',
        'Leafpub\\Models\\Tag',
        'Leafpub\\Importer\\AbstractImporter',
    ];

    /**
     * Gets multiple tags. Returns an array of tags on success, false if not found. If $pagination
     * is specified, it will be populated with pagination data generated by Leafpub::paginate().
     *
     * @param null $options
     * @param null $pagination
     *
     * @return mixed
     *
     **/
    public static function getMany(array $options = [], &$pagination = null)
    {
        // Merge options with defaults
        $options = array_merge([
            'query' => null,
            'page' => 1,
            'items_per_page' => 10,
            'sort' => 'ASC',
        ], (array) $options);

        $evt = new ManyRetrieve($options);
        Leafpub::dispatchEvent(ManyRetrieve::NAME, $evt);
        $options = $evt->getEventData();

        $model = self::getModel();

        $select = $model->getSql()->select();

        $where = function ($wh) use ($options) {
            $wh->nest->like('slug', '%' . $options['query'] . '%')
               ->or->like('name', '%' . $options['query'] . '%')
               ->or->like('description', '%' . $options['query'] . '%')
               ->or->like('meta_title', '%' . $options['query'] . '%')
               ->or->like('meta_description', '%' . $options['query'] . '%')
               ->unnest();

            if (array_key_exists('type', $options) && $options['type']) {
                $wh->equalTo('type', $options['type']);
            }
        };

        $select->where($where);

        $select->order('type')->order('name');

        $totalItems = self::count($where);

        $pagination = Leafpub::paginate(
            $totalItems,
            $options['items_per_page'],
            $options['page']
        );

        $offset = ($pagination['current_page'] - 1) * $pagination['items_per_page'];
        $count = $pagination['items_per_page'];

        $select->offset((int) $offset);
        $select->limit((int) $count);

        // Run the data query
        try {
            $tags = $model->selectWith($select)->toArray();
        } catch (\PDOException $e) {
            return false;
        }

        foreach ($tags as $key => $value) {
            $tags[$key] = self::normalize($value);
        }

        $evt = new ManyRetrieved($tags);
        Leafpub::dispatchEvent(ManyRetrieved::NAME, $evt);

        return $evt->getEventData();
    }

    /**
     * Gets a single tag. Returns an array on success, false if not found.
     *
     * @param string $slug
     *
     * @return mixed
     *
     **/
    public static function getOne($slug)
    {
        $evt = new Retrieve($slug);
        Leafpub::dispatchEvent(Retrieve::NAME, $evt);

        try {
            $tag = self::getModel()->select(['slug' => $slug])->current();
            if (!$tag) {
                return false;
            }
        } catch (\PDOException $e) {
            return false;
        }

        // Normalize fields
        $tag = self::normalize($tag->getArrayCopy());

        $evt = new Retrieved($tag);
        Leafpub::dispatchEvent(Retrieved::NAME, $evt);

        return $evt->getEventData();
    }

    /**
     * Creates a tag
     *
     * @param array $tag
     *
     * @throws \Exception
     *
     * @return bool
     *
     **/
    public static function create($tag)
    {
        if (!self::isAllowedCaller()) {
            return false;
        }

        $slug = $tag['slug'];
        // Enforce slug syntax
        $slug = Leafpub::slug($slug);

        // Is the slug valid?
        if (!mb_strlen($slug) || Leafpub::isProtectedSlug($slug)) {
            throw new \Exception('Invalid slug: ' . $slug, self::INVALID_SLUG);
        }

        // Does a tag already exist here?
        if (self::exists($slug)) {
            throw new \Exception('Tag already exists: ' . $slug, self::ALREADY_EXISTS);
        }

        // Must have a name
        if (!mb_strlen($tag['name'])) {
            throw new \Exception('No name specified', self::INVALID_NAME);
        }

        // Don't allow null properties
        $tag['slug'] = $slug;
        $tag['description'] = (string) $tag['description'];
        $tag['cover'] = (string) $tag['cover'];
        $tag['meta_title'] = (string) $tag['meta_title'];
        $tag['meta_description'] = (string) $tag['meta_description'];

        if (!$tag['created']) {
            $tag['created'] = Leafpub::localToUtc(date('Y-m-d H:i:s'));
        }

        $evt = new Add($tag);
        Leafpub::dispatchEvent(Add::NAME, $evt);
        $tag = $evt->getEventData();

        try {
            $ret = (self::getModel()->insert($tag) > 0);
        } catch (\PDOException $e) {
            throw new \Exception('Database error: ' . $e->getMessage());
        }

        $evt = new Added($slug);
        Leafpub::dispatchEvent(Added::NAME, $evt);

        return $ret;
    }

    /**
     * Updates a tag
     *
     * @param array $properties
     *
     * @throws \Exception
     *
     * @return bool
     *
     **/
    public static function edit($properties)
    {
        if (!self::isAllowedCaller()) {
            return false;
        }

        // Get the tag
        $slug = $properties['slug'];
        $tag = self::getOne($slug);
        if (!$tag) {
            throw new \Exception('Tag not found: ' . $slug, self::NOT_FOUND);
        }

        // Merge properties
        $tag = array_merge($tag, $properties);
        $evt = new Update($tag);
        Leafpub::dispatchEvent(Update::NAME, $evt);
        $tag = $evt->getEventData();

        // Must have a name
        if (!mb_strlen($tag['name'])) {
            throw new \Exception('No name specified', self::INVALID_NAME);
        }

        // Don't allow null properties
        $tag['description'] = (string) $tag['description'];
        $tag['cover'] = (string) $tag['cover'];
        $tag['meta_title'] = (string) $tag['meta_title'];
        $tag['meta_description'] = (string) $tag['meta_description'];

        // Change the slug?
        if ($tag['slug'] !== $slug) {
            // Enforce slug syntax
            $tag['slug'] = Leafpub::slug($tag['slug']);

            // Is the slug valid?
            if (!mb_strlen($tag['slug']) || Leafpub::isProtectedSlug($tag['slug'])) {
                throw new \Exception('Invalid slug: ' . $tag['slug'], self::INVALID_SLUG);
            }

            // Does a tag already exist with this slug?
            if (self::exists($tag['slug'])) {
                throw new \Exception('Tag already exists: ' . $tag['slug'], self::ALREADY_EXISTS);
            }
        }

        // Update the tag
        try {
            $ret = (self::getModel()->update($properties, ['slug' => $slug]) > 0);
        } catch (\PDOException $e) {
            return false;
        }

        $evt = new Updated($tag);
        Leafpub::dispatchEvent(Updated::NAME, $evt);

        return $ret;
    }

    /**
     * Deletes a tag
     *
     * @param string $slug
     *
     * @return bool
     *
     **/
    public static function delete($slug)
    {
        if (!self::isAllowedCaller()) {
            return false;
        }

        $evt = new Delete($slug);
        Leafpub::dispatchEvent(Delete::NAME, $evt);

        try {
            $ret = (self::getModel()->delete(['slug' => $slug]) > 0);
        } catch (\PDOException $e) {
            return false;
        }

        $evt = new Deleted($slug);
        Leafpub::dispatchEvent(Deleted::NAME, $evt);

        return $ret;
    }

    /**
     * Gets tags to a given post
     *
     * @param int $postId
     *
     * @return array
     *
     **/
    public static function getTagsToPost($postId)
    {
        $tags = [];
        try {
            $table = new Tables\PostTags();
            $select1 = $table->getSql()->select()
                                        ->columns(['tag'])
                                        ->where(function ($wh) use ($postId) {
                                            $wh->equalTo('post', $postId);
                                        });

            $model = self::getModel();
            $select = self::getModel()->getSql()->select()
                                                ->columns(['slug'])
                                                ->where(function ($wh) use ($select1) {
                                                    $wh->in('id', $select1);
                                                });

            $ret = $model->selectWith($select)->toArray();
            foreach ($ret as $itm) {
                $tags[] = $itm['slug'];
            }

            return $tags;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Gets tags to a given media file
     *
     * @param int $mediaId
     *
     * @return array
     *
     **/
    public static function getTagsToUpload($mediaId)
    {
        $tags = [];
        try {
            $table = new Tables\UploadTags();
            $select1 = $table->getSql()->select()
                                        ->columns(['tag'])
                                        ->where(function ($wh) use ($mediaId) {
                                            $wh->equalTo('upload', $mediaId);
                                        });

            $model = self::getModel();
            $select = self::getModel()->getSql()->select()
                                                ->columns(['slug'])
                                                ->where(function ($wh) use ($select1) {
                                                    $wh->in('id', $select1);
                                                });

            $ret = $model->selectWith($select)->toArray();
            foreach ($ret as $itm) {
                $tags[] = $itm['slug'];
            }

            return $tags;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Returns the total number of tags that exist
     *
     * @return mixed
     *
     **/
    public static function count($where)
    {
        try {
            $model = self::getModel();
            $select = $model->getSql()->select()->columns(['num' => new \Zend\Db\Sql\Expression('COUNT(*)')]);
            if ($where !== null) {
                $select->where($where);
            }
            $ret = $model->selectWith($select);

            return $ret->current()['num'];
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Tells whether a tag exists
     *
     * @param string $slug
     *
     * @return bool
     *
     **/
    public static function exists($slug)
    {
        try {
            return (bool) self::getOne($slug);
        } catch (\PDOException $e) {
            return false;
        }
    }

    // Returns an array of all tag names and corresponding slugs
    public static function getNames($type = 'post')
    {
        try {
            $model = self::getModel();
            $select = $model->getSql()->select();
            $select->columns(['slug', 'name'])
                   ->where(['type' => $type])
                   ->order('name');

            $tags = $model->selectWith($select)->toArray();
        } catch (\PDOException $e) {
            return false;
        }

        return $tags;
    }

    // Renders a tag page
    public static function render($slug, $page = 1)
    {
        //TODO BeforeRender Event!
        // Get the tag
        $tag = self::getOne(['slug' => $slug]);
        if (!$tag) {
            return false;
        }

        // Get the tag's posts
        $posts = Post::getMany([
            'tag' => $slug,
            'page' => $page,
            'items_per_page' => Setting::getOne('posts_per_page'),
        ], $pagination);

        // Make sure the requested page exists
        if ($page > $pagination['total_pages']) {
            return false;
        }

        // Add previous/next links to pagination
        $pagination['next_page_url'] = $pagination['next_page'] ?
            self::url($slug, $pagination['next_page']) : null;
        $pagination['previous_page_url'] = $pagination['previous_page'] ?
            self::url($slug, $pagination['previous_page']) : null;

        $beforeRender = new BeforeRender([
            'tag' => $tag,
            'special_vars' => [
                'meta' => [
                    'title' => !empty($tag['meta_title']) ? $tag['meta_title'] : $tag['name'],
                    'description' => !empty($tag['meta_description']) ? $tag['meta_description'] : $tag['description'],
                    // JSON linked data (schema.org)
                    'ld_json' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'Series',
                        'publisher' => Setting::getOne('title'),
                        'url' => self::url($tag['slug']),
                        'image' => empty($tag['cover']) ? null : [
                                    '@type' => 'ImageObject',
                                    'url' => Leafpub::url($tag['cover']),
                                ],
                        'name' => !empty($tag['meta_description']) ?
                            $tag['meta_title'] :
                            $tag['name'],
                        'description' => !empty($tag['meta_description']) ?
                            $tag['meta_description'] :
                            strip_tags(Leafpub::markdownToHtml($tag['description'])),
                    ],
                    // Open Graph
                    'open_graph' => [
                        'og:type' => 'website',
                        'og:site_name' => Setting::getOne('title'),
                        'og:title' => !empty($tag['meta_title']) ?
                            $tag['meta_title'] :
                            $tag['name'] . ' &middot; ' . Setting::getOne('title'),
                        'og:description' => !empty($tag['meta_description']) ?
                            $tag['meta_description'] : $tag['description'],
                        'og:url' => self::url($tag['slug']),
                        'og:image' => !empty($tag['cover']) ?
                            Leafpub::url($tag['cover']) : null,
                    ],
                    // Twitter Card
                    'twitter_card' => [
                        'twitter:card' => !empty($tag['cover']) ?
                            'summary_large_image' : 'summary',
                        'twitter:site' => !empty(Setting::getOne('twitter')) ?
                            '@' . Setting::getOne('twitter') : null,
                        'twitter:title' => !empty($tag['meta_title']) ?
                            $tag['meta_title'] :
                            $tag['name'] . ' &middot; ' . Setting::getOne('title'),
                        'twitter:description' => !empty($tag['meta_description']) ?
                            $tag['meta_description'] : $tag['description'],
                        'twitter:url' => self::url($tag['slug']),
                        'twitter:image' => !empty($tag['cover']) ?
                            Leafpub::url($tag['cover']) : null,
                    ],
                ],
            ],
        ]);

        Leafpub::dispatchEvent(BeforeRender::NAME, $beforeRender);
        $data = $beforeRender->getEventData();

        // Render it
        return Renderer::render([
            'template' => Theme::getPath('tag.hbs'),
            'data' => [
                'tag' => $data['tag'],
                'posts' => $posts,
                'pagination' => $pagination,
            ],
            'special_vars' => $data['special_vars'],
            'helpers' => ['url', 'utility', 'theme'],
        ]);
    }

    /**
     * Returns a tag URL
     *
     * @param string $slug
     * @param int    $page
     *
     * @return string
     *
     **/
    public static function url($slug = '', $page = 1)
    {
        return $page > 1 ?
            // example.com/tag/name/page/2
            Leafpub::url(
                Setting::getOne('frag_tag'),
                $slug,
                Setting::getOne('frag_page'),
                $page
            ) :
            // example.com/tag/name
            Leafpub::url(Setting::getOne('frag_tag'), $slug);
    }

    protected static function getModel()
    {
        if (self::$_instance == null) {
            self::$_instance = new Tables\Tag();
        }

        return self::$_instance;
    }

    /**
     * Normalize data types for certain fields
     *
     * @param array $tag
     *
     * @return array
     *
     **/
    private static function normalize($tag)
    {
        // Cast to integer
        $tag['id'] = (int) $tag['id'];

        // Convert dates from UTC to local
        $tag['created'] = Leafpub::utcToLocal($tag['created']);

        return $tag;
    }
}
