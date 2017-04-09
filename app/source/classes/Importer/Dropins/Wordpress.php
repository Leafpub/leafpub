<?php
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
* Wordpress
*
* Parses a WordPress export XML 
* @package Lefpub\Importer\Dropins
*
**/
class Wordpress extends AbstractImporter {
	/**
	* Properties
	**/
    private $namespaces = array();
    private $_isDotCom = false;

    public function parseFile(){
        
        if (extension_loaded('simplexml')){
            $this->parseWithSimpleXml();
        } else {
            throw new \Exception(\Leafpub\Language::term('Extension simplexml needs to be installed!'));
        }
        
		foreach($this->_posts as &$post){
			if (isset($post['image'])){
				$id = $post['image']; 
				$post['image'] = 'content/uploads/' . date('Y') . '/' . date('m') . '/' . $this->_media[(int) $id]['filename'] . '.' . $this->_media[(int) $id]['extension'];
			}
		}

        return array(
            'user' => $this->_user,
            'posts' => $this->_posts,
            'tags' => $this->_tags, // merge with categories...
            'media' => ($this->_loadMediaFiles ? $this->_media : array()),
            'post_tags' => $this->_post_tags
        );
    }
    
    private function parseWithSimpleXml(){
        $internal_errors = libxml_use_internal_errors(true);

		$dom = new \DOMDocument();
		$old_value = null;
		if ( function_exists( 'libxml_disable_entity_loader' ) ) {
			$old_value = libxml_disable_entity_loader( true );
		}
		$success = $dom->loadXML(file_get_contents( $this->_fileToParse ));
		if (!is_null( $old_value )) {
			libxml_disable_entity_loader($old_value);
		}

		if ( ! $success || isset( $dom->doctype ) ) {
			throw new \Exception(\Leafpub\Language::term('There was an error when reading this file') . ': ' . $this->parseXmlError(libxml_get_errors()) );
		}

		$parser = simplexml_import_dom( $dom );
		if (!$parser){
			throw new \Exception(\Leafpub\Language::term('There was an error when reading this file') . ': ' . $this->parseXmlError(libxml_get_errors()));
    	}
    
		unset( $dom );
        
        $base_url = $parser->xpath('/rss/channel/wp:base_blog_url');
		$this->_oldBlogUrl = (string) trim($base_url[0]);

        if (strpos($parser->xpath('/rss/channel/wp:base_site_url')[0], 'wordpress.com') !== false){
            $this->_isDotCom = true;
        }
    
        $this->namespaces = $parser->getDocNamespaces();
        
        // 1. authors/users
        foreach ( $parser->xpath('/rss/channel/wp:author') as $author ) {
			$a = $author->children($this->namespaces['wp']);
	
			$slug = (string) $a->author_login;
			$this->_user[$slug] = array(
				'id' => (int) $a->author_id,
				'slug' => $slug,
				'email' => (string) $a->author_email,
				'name' => (string) $a->author_first_name . ' ' . (string) $a->author_last_name,
				'password' => (string) $a->author_first_name . (string) $a->author_last_name // password needs to be set for User::add
			);
		}
        // 2. categories/tags
        foreach ( $parser->xpath('/rss/channel/wp:category') as $cats ) {
			$t = $cats->children($this->namespaces['wp']);
			$cat_name = (string) $t->cat_name;
			$category = array(
				'id' => (int) $t->term_id,
				'slug' => (string) $t->category_nicename,
				//'parent' => (string) $t->category_parent,
				'name' => $cat_name,
				'description' => (string) $t->category_description,
                'type' => 'post',
				'created' => new \Zend\Db\Sql\Expression('NOW()')
			);

			$this->_categories[$cat_name] = $category;
		}
		
		foreach ( $parser->xpath('/rss/channel/wp:tag') as $tags ) {
			$t = $tags->children($this->namespaces['wp']);
			$tag_name = (string) $t->tag_name;
			$tag = array(
				'id' => (int) $t->term_id,
				'slug' => (string) $t->tag_slug,
				'name' => $tag_name,
				'description' => (string) $t->tag_description,
                'type' => 'post',
				'created' => new \Zend\Db\Sql\Expression('NOW()')
			);

			$this->_tags[$tag_name] = $tag;
		}
		
        // 3. posts/media/pages
        foreach ( $parser->channel->item as $item ) {
            // In Wordpress everything is a post (image, menu, menuitem, post...)
            // We need to know, which post_type is the actual item.
            $wp = $item->children($this->namespaces['wp']);
            $type = (string) $wp->post_type;
            
            switch ($type){
                case 'post';
                case 'page':
                    $this->handlePost($item);
                    break;
                case 'attachment':
                    $this->handleMedia($item);
                    break;
                case 'nav_menu_item':
                	// Should we import the navigation?
                    break;
                default:
                    // Every custom post type 
                    $this->handlePost($item);
                    break;
            }
		}
    }
    
    private function handlePost($item){
        $post = array(
		    'title' => (string) $item->title,
		    'pub_date' => date('Y-m-d H:i:s', strtotime((string) $item->pubDate))
		);
		
		$dc = $item->children('http://purl.org/dc/elements/1.1/');
        if (!$this->_loadUser){
            $post['author'] = \Leafpub\Session::user('slug');
        } else {
		    $post['author'] = (string) $dc->creator; //Should we insert the author/user id here? $this->users[(string) $dc->creator]['id'];
        }

		$content = $item->children('http://purl.org/rss/1.0/modules/content/');
		$post['content'] = $this->filterContent((string) $content->encoded); // --> CONTENT FILTERING!
			
		$wp = $item->children($this->namespaces['wp']);
		$post['id'] = (int) $wp->post_id;
		$post['created'] = (string) $wp->post_date; // In Post::add created will overwritten
		$post['slug'] = (string) $wp->post_name;
		
		$post['status'] = (string) $wp->status == 'publish' ? 'published' : (string) $wp->status;
			
		$post['page'] = (((string) $wp->post_type == 'page') ? 1 : 0);
			
		$post['sticky'] = (int) $wp->is_sticky;
		
		/*
		if (isset($wp->attachment_url)){
			echo (string) $wp->attachment_url;
			$post['image'] = (string) $wp->attachment_url; // NOT URL --> PATH TO PIC
		}
		*/
		
		foreach ( $item->category as $c ) {
			$att = $c->attributes();
			if ( isset($att['nicename'] ))
				if ($att['domain'] == 'post_tag'){
					//$post['tags'][] = (string) $c;
				} elseif ($att['domain'] == 'category'){
					// Only WP category is used as a Leafpub tag	
					$post['tags'][] = (string) $c;
				}
		}
        
		foreach ( $wp->postmeta as $meta ) {
			/*
			$post['postmeta'][] = array(
				'key' => (string) $meta->meta_key,
				'value' => (string) $meta->meta_value
			);*/
			if ($meta->meta_key == '_thumbnail_id'){
				$post['image'] = $meta->meta_value;
			}
		}
    
		$this->_posts[(string) $wp->post_name] = $post;
    }
    
    private function handleMedia($item){
        $media = array();

		$dc = $item->children('http://purl.org/dc/elements/1.1/');
		$media['author'] = $this->users[(string) $dc->creator]['id'];

		$wp = $item->children($this->namespaces['wp']);
		$id = (int) $wp->post_id;
		$media['id'] = $id;
		
		$url = (string) $wp->attachment_url;
		
		$media['title'] = $item->title; // title won't be used yet.... ;-)
		$media['filename'] = \Leafpub\Leafpub::fileName($url);
		$media['extension'] = \Leafpub\Leafpub::fileExtension($url);
		
		$media['url'] = $url;
		// Where is this file saved in WP?
		foreach ( $wp->postmeta as $meta ) {
			if ($meta->meta_key == '_wp_attached_file'){
				$media['attached_file'] = $meta->meta_value;
			}
		}
		
        //$this->_media[(string) $wp->post_name] = $media;
		$this->_media[$id] = $media;
    }
    
    protected function filterContent($content){
		$year = date('Y');
        $month = date('m');
        $searchUrl = '';
		
        if ($this->_isDotCom){
            $searchUrl = 'https?:\/\/[^\/]+[files.wordpress.com]';
        } else {
            $searchUrl = addcslashes($this->_oldBlogUrl, ':/') . '\/wp-content\/uploads';
        }
		// Filter the old url, wp-content and year/month to our folder structure
		if (preg_match('/[0-9]{4}\/[0-9]{2}/', $content)){
			// Replace year/month to actual year / actual month
			$content = preg_replace(
				'/(?:' . $searchUrl . ')*\/([0-9]{4}\/[0-9]{2})/', 
				'/content/uploads/' . $year . '/' . $month, 
				$content
			);
		} else {
			$content = preg_replace(
				'/(?:' . $searchUrl .')/', 
				'/content/uploads/' . $year . '/' . $month, 
				$content
			);
		}
		// Delete wxh from filename
		$content = preg_replace('/-[0-9]{3,4}x[0-9]{3,4}/', '', $content);
		// Delete Wordpress Shortcodes
		$content = preg_replace('/' . "\[\w(.*?)?\]" . '/', '', $content);
		$content = preg_replace('/' . "\[\/\w(.*?)?\]" . '/', '', $content);
		//$content = preg_replace('/' . "\[\w(.+?)?\](?:(.+?)\[\/\w(.+?)?\])?" . '/', '', $content);
		return $content;
    } 

	private function parseXmlError(array $error){
		foreach ($error as $e){
			switch ($e->level) {
				case LIBXML_ERR_WARNING:
					$return .= "Warning $e->code: ";
					break;
				case LIBXML_ERR_ERROR:
					$return .= "Error $e->code: ";
					break;
				case LIBXML_ERR_FATAL:
					$return .= "Fatal Error $e->code: ";
					break;
			}

			$return .= trim($e->message) .
					"\n  Line: $e->line" .
					"\n  Column: $e->column";

			if ($e->file) {
				$return .= "\n  File: $e->file";
			}
			$return .= "\n";
		}
	}
}