<?php
namespace Postleaf\Importer;

class Wordpress extends AbstractImporter {
    public function parseFile(){
        $parser = new XmlParser($this->_fileToParse);
        $retArray = $parser->parse();
        
        $this->_user = $retArray['user'];
        $this->_tags = $retArray['tags'];
        $this->_posts = $retArray['posts'];
        $this->_post_tags = $retArray['post_tags'];
        $this->_media = $retArray['media'];
        $this->_oldBlogUrl = $retArray['oldUrl'];
        
        foreach($this->_posts as $post){
            $this->filterContent($post['content']);
        }
        return $retArray;
    }
    
    protected function filterContent($content){
        $filteredContent = parent::filterContent($content);
        var_dump($filteredContent);
    }
}
?>