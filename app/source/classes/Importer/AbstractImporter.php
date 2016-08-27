<?php
namespace Postleaf\Importer;

use Postleaf\Post,
    Postleaf\Postleaf,
    Postleaf\Tag,
    Postleaf\Upload,
    Postleaf\User;
    
abstract class AbstractImporter {
    protected $_user, $_tags, $_content, $_media, $_post_tags; // arrays
    protected $_loadMediaFiles = false; // boolean
    protected $_fileToParse; // String
    protected $_oldBlogUrl; // String - this adress will be deleted in filterPosts() in img src tags
    
    public function __construct($file){
        $this->_fileToParse = $file;
    }
    
    // This function has to be defined in the ImporterClass
    abstract public function parseFile();
    
    protected function loadMediaFile($filename, $url){
        //Code to download the file
        
        // Where should we save the media files?
        // Every file in todays folder (year/month/day)?
        $path = 'PATH_TO_SET';
        $mediaFile = fopen ($path. '/' . $filename, 'w+');
        $handle = curl_init(str_replace(" ","%20",$url));
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        
        curl_setopt($handle, CURLOPT_FILE, $mediaFile); 
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        
        curl_exec($handle); 
        curl_close($handle);
        fclose($mediaFile);
    }
    
    // In this abstract class this function filters all img tags and sets the correct img src path
    // In the ImporterClasses this function could be extended to strip Wordpress shortcode tags
    protected function filterContent($content){
        $filteredContent = strtr($content, $this->_oldBlogUrl, '');
        return $filteredContent;   
    }
    
    // parseFile fills our protected arrays with data.
    // We are now saving the array data to DB; 
    public function importData(){
        $this->_user;
        $this->_tags;
        $this->_posts;
        $this->_post_tags;
        //$this->_media;
    }
}
?>