<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */
namespace Leafpub\Listeners;

use Leafpub\Events\Post\Add,
    Leafpub\Events\Post\Added,
    Leafpub\Events\Post\BeforeRender,
    Leafpub\Events\Post\PostViewed,
    Leafpub\Leafpub,
    Leafpub\Models\Setting,
    Leafpub\Models\User,
    Leafpub\Models\Post as P,
    Leafpub\Models\PostMeta;

class Post {
    
    public function onPostAdd(Add $add){
        $post = $add->getEventData();
    }
    
    public function onPostAdded(Added $added){
        $post = $added->getEventData();
    }

    public function onBeforeRender(BeforeRender $event){
        $data = $event->getEventData();
        /*
        $author = User::get($data['post']['author']);
        $data['special_vars']['meta']['ld_json'] = $this->_generateLDJson($data['post'], $author);
        $data['special_vars']['meta']['open_graph'] = $this->_generateOGData($data['post']);
        $data['special_vars']['meta']['twitter_card'] = $this->_generateTwitterData($data['post'], $author);
        
        $event->setEventData($data);
        */
    }

    public function onPostViewed(PostViewed $event){
        $data = $event->getEventData();
        $slug = $data['post'];
        P::increaseViewCount($slug);
    }

    private function _generateLDJson($post, $author){
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'publisher' => [
                '@type' => 'Organization',
                'name' => Setting::get('title'),
                'logo' => !empty(Setting::get('logo')) ?
                    Leafpub::url(Setting::get('logo')) : null
                ],
            'author' => [
                '@type' => 'Person',
                'name' => $author['name'],
                'description' => strip_tags(P::markdownToHtml($author['bio'])),
                'image' => !empty($author['avatar']) ?
                    Leafpub::url($author['avatar']) : null,
                'sameAs' => !empty($author['website']) ?
                    [$author['website']] : null,
            ],
            'url' => P::url($post['slug']),
            'headline' => !empty($post['meta_title']) ?
                $post['meta_title'] :
                $post['title'],
            'description' => !empty($post['meta_description']) ?
                $post['meta_description'] :
                P::getWords(strip_tags($post['content']), 50),
            'image' => empty($post['image']) ? null : Leafpub::url($post['image']),
            'datePublished' => P::strftime('%FT%TZ', strtotime($post['pub_date'])),
            'dateModified' => P::strftime('%FT%TZ', strtotime($post['pub_date']))
        ];
    }

    private function _generateOGData($post){
        // Open Graph
        return [
            'og:type' => 'article',
            'og:site_name' => Setting::get('title'),
            'og:title' => !empty($post['meta_title']) ?
                $post['meta_title'] :
                $post['title'],
            'og:description' => !empty($post['meta_description']) ?
                $post['meta_description'] :
                P::getWords(strip_tags($post['content']), 50),
            'og:url' => P::url($post['slug']),
            'og:image' => empty($post['image']) ? '' : Leafpub::url($post['image']),
            'article:published_time' => $post['page'] ?
                null : P::strftime('%FT%TZ', strtotime($post['pub_date'])),
            'article:modified_time' => $post['page'] ?
                null : P::strftime('%FT%TZ', strtotime($post['pub_date'])),
            'article:tag' => $post['page'] ?
                null : implode(', ', (array) $post['tags'])
        ];
    }

    private function _generateTwitterData($post, $author){
        return [
            'twitter:card' => !empty($post['image']) ?
                'summary_large_image' :
                'summary',
            'twitter:site' => !empty(Setting::get('twitter')) ?
                '@' . Setting::get('twitter') : null,
            'twitter:title' => !empty($post['meta_title']) ?
                $post['meta_title'] :
                $post['title'],
            'twitter:description' => !empty($post['meta_description']) ?
                $post['meta_description'] :
                P::getWords(strip_tags($post['content']), 50),
            'twitter:creator' => !empty($author) ?
                '@' . $author['twitter'] : null,
            'twitter:url' => P::url($post['slug']),
            'twitter:image' => !empty($post['image']) ?
                Leafpub::url($post['image']) :
                null,
            'twitter:label1' => !$post['page'] ?
                'Written by' : null,
            'twitter:data1' => !$post['page'] ?
                $author['name'] : null,
            'twitter:label2' => !$post['page'] ?
                'Tagged with' : null,
            'twitter:data2' => !$post['page'] ?
                implode(', ', (array) $post['tags']) : null
        ];
    }

    private function _generateAMPData(){

    }
}
?>