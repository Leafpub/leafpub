<?php
namespace Postleaf\Importer;

class Wordpress extends AbstractImporter {
    private $namespaces = array();
    
    public function parseFile(){
        
        if (extension_loaded('simplexml')){
            $this->parseWithSimpleXml();
        } else {
            throw new \Exception(Language::term('Extension simplexml needs to be installed!'));
        }
        
        foreach($this->_posts as $post){
            $filteredContent = $this->filterContent($post['content']);
            $post['content'] = $filteredContent;
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
			return new \Exception(Language::term('There was an error when reading this file'), libxml_get_errors() );
		}

		$parser = simplexml_import_dom( $dom );
		if (!$parser){
			return new \Exception(Language::term('There was an error when reading this file'), libxml_get_errors());
    	}
    
		unset( $dom );
        
        $base_url = $parser->xpath('/rss/channel/wp:base_site_url');
		$this->oldUrl = (string) trim($base_url[0]);
		
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
				'parent' => (string) $t->category_parent,
				'name' => $cat_name,
				'description' => (string) $t->category_description
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
				'description' => (string) $t->tag_description
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
		    'pub_date' => (string) $item->post_date
		);

		$dc = $item->children('http://purl.org/dc/elements/1.1/');
		$post['author'] = (string) $dc->creator; //Should we insert the author/user id here? $this->users[(string) $dc->creator]['id'];

		$content = $item->children('http://purl.org/rss/1.0/modules/content/');
		$post['content'] = (string) $content->encoded; // --> CONTENT FILTERING!
			
		$wp = $item->children($this->namespaces['wp']);
		$post['id'] = (int) $wp->post_id;
		$post['created'] = (string) $wp->post_date;
		$post['slug'] = (string) $wp->post_name;
		
		$post['status'] = (string) $wp->status == 'publish' ? 'published' : (string) $wp->status;
			
		$post['page'] = (((string) $wp->post_type == 'page') ? 1 : 0);
			
		$post['sticky'] = (int) $wp->is_sticky;

		if (isset($wp->attachment_url)){
			$post['image'] = (string) $wp->attachment_url; // NOT URL --> PATH TO PIC
		}
		
		
		foreach ( $item->category as $c ) {
			$att = $c->attributes();
			if ( isset($att['nicename'] ))
				if ($att['domain'] == 'post_tag'){
					$this->_post_tags[(int) $wp->post_id][] = $this->tags[(string) $c]['id'];
					$post['tags'][] = (string) $c;
				} elseif ($att['domain'] == 'category'){
					//Save the Cat ID.	
				}
		}
        /*
		foreach ( $wp->postmeta as $meta ) {
			$post['postmeta'][] = array(
				'key' => (string) $meta->meta_key,
				'value' => (string) $meta->meta_value
			);
		}
        */
		$this->_posts[(string) $wp->post_name] = $post;
    }
    
    private function handleMedia($item){
        $media = array();

		$dc = $item->children('http://purl.org/dc/elements/1.1/');
		$media['author'] = $this->users[(string) $dc->creator]['id'];

		$wp = $item->children($this->namespaces['wp']);
		$media['id'] = (int) $wp->post_id;
		
		$url = (string) $wp->attachment_url;
		
		$media['filename'] = \Postleaf\Postleaf::fileName($url);
		$media['extension'] = \Postleaf\Postleaf::fileExtension($url);
		
		$media['url'] = $url;

        $this->_media[(string) $wp->post_name] = $media;
    }
    
    protected function filterContent($content){
        $filteredContent = parent::filterContent($content);

    }
}
?>