<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Importer\Dropins;

use Leafpub\Importer\AbstractImporter;

/**
 * Ghost
 *
 * Parses a Ghost export XML
 *
 **/
class Ghost extends AbstractImporter
{
    /**
     * @var array<mixed, array<int|string, mixed>>|null
     */
    public array $_posts;
    /**
     * @var array<string, string>
     */
    private array $userKeys = [
        'id' => 'id',
        'slug' => 'slug',
        'name' => 'name',
        'password' => 'password',
        'email' => 'email',
        'created_at' => 'created',
    ];

    /**
     * @var array<string, string>
     */
    private array $tagKeys = [
        'id' => 'id',
        'slug' => 'slug',
        'name' => 'name',
        'description' => 'description',
        'image' => 'cover',
        'meta_title' => 'meta_title',
        'meta_description' => 'meta_description',
        'created_at' => 'created',
    ];

    /**
     * @var array<string, string>
     */
    private array $postKeys = [
        'id' => 'id',
        'slug' => 'slug',
        'html' => 'content',
        'image' => 'image',
        'author_id' => 'author',
        'meta_title' => 'meta_title',
        'meta_description' => 'meta_description',
        'page' => 'page',
        'sticky' => 'sticky',
        'featured' => 'featured',
        'created_at' => 'created',
        'published_at' => 'pub_Date',
    ];

    /**
     * @var array<string, string>
     */
    private array $mediaKeys = [
        'id' => 'id',
    ];

    /**
     * @var array<string, string>
     */
    private array $ptKeys = [
        'post_id' => 'post',
        'tag_id' => 'tag',
    ];

    public function parseFile(): void
    {
        $data = json_decode(file_get_contents($this->_fileToParse));
        $posts = $data->db[0]->data->posts;
        $media = $data->db[0]->data->media;
        $users = $data->db[0]->data->users;
        $tags = $data->db[0]->data->tags;
        $pt = $data->db[0]->data->post_tags;

        foreach ($users as $u) {
            $u = get_object_vars($u);
            $user = [];
            foreach ($this->userKeys as $source => $target) {
                $user[$target] = $u[$source];
            }
            $this->_user[$u['slug']] = $user;
        }

        foreach ($posts as $p) {
            $p = get_object_vars($p);
            $post = [];
            foreach ($this->postKeys as $source => $target) {
                $post[$target] = $p[$source];
            }
            //$post['author'] = $this->_user[$p['author_id']]['slug'];
            $this->filterContent($post['content']);
            $this->_posts[$p['slug']] = $post;
        }

        foreach ($media as $m) {
            $m = get_object_vars($m);
            $medium = [];
            foreach ($this->mediaKeys as $source => $target) {
                $medium[$target] = $m[$source];
            }
            $this->_media[] = $medium;
        }

        foreach ($tags as $t) {
            $t = get_object_vars($t);
            $tag = [];
            foreach ($this->tagKeys as $source => $target) {
                $tag[$target] = $t[$source];
            }
            $this->_tags[$t['slug']] = $tag;
        }

        foreach ($pt as $p_t) {
            $p_t = get_object_vars($p_t);
            $t_p = [];
            foreach ($this->ptKeys as $source => $target) {
                $t_p[$target] = $p_t[$source];
            }
            $this->_post_tags[] = $tag;
        }
    }
}
