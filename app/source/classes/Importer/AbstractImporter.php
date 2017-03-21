<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Importer;

use Leafpub\Models\Post,
    Leafpub\Leafpub,
    Leafpub\Models\Tag,
    Leafpub\Models\Upload,
    Leafpub\Models\User,
    Leafpub\Session;

/**
* AbstractImporter
*
* methods for importing data from other blog platforms
* @package Leafpub\Importer
*
**/  
abstract class AbstractImporter {
    /**
    * Properties
    **/
    protected $_user, $_tags, $_categories, $_content, $_media, $_post_tags; // arrays
    protected $_loadMediaFilesRemote = false; // boolean
    protected $_loadMediaFilesLocal = false;
    protected $_loadUser = false;
    protected $_truncateTables = false;
    protected $_useCategoryAsTag = false;
    protected $_tmpPath = '';
    protected $_fileToParse; // String
    protected $_oldBlogUrl; // String - this adress will be deleted in filterPosts() in img src tags
    private $_failed = [];
    private $_succeed = [];

    public function __construct($file){
        $this->_fileToParse = $file;
    }
    
    public function setOptions($options){
        $this->_loadMediaFilesRemote =  ($options['media'] == 'remote' ? true : false);
        $this->_loadMediaFilesLocal = ($options['media'] == 'local' ? true : false);
        $this->_loadUser = ($options['user'] == 'false' ? false : true);
        $this->_truncateTables = ($options['flush'] == 'false' ? false : true);
        $this->_useCategoryAsTag = ($options['category'] == 'false' ? false :true);
    }
    /**
    * This function has to be defined in the ImporterClass
    *
    * @return array
    *
    **/
    abstract public function parseFile();
    
    /**
    * Loads an external media file via CURL
    *
    * @param String $filename
    * @param String $url
    * @return void
    *
    **/
    protected function loadMediaFile($filename, $url){
        //Code to download the file
        
        // Where should we save the media files?
        // Every file in todays folder (year/month/day)?
        $mediaFile = fopen ($this->_tmpPath . '/' . $filename, 'w+');
        $handle = curl_init(str_replace(" ","%20",$url));
        curl_setopt($handle, CURLOPT_TIMEOUT, 50);
        
        curl_setopt($handle, CURLOPT_FILE, $mediaFile); 
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        
        curl_exec($handle); 
        curl_close($handle);
        fclose($mediaFile);
    }
    
    // In this abstract class this function filters all img tags and sets the correct img src path
    // In the ImporterClasses this function could be extended to strip Wordpress shortcode tags
    protected function filterContent($content){
        return str_replace($this->_oldBlogUrl, '/', $content);
    }
    
    // parseFile fills our protected arrays with data.
    // We are now saving the array data to DB; 
    public function importData(){
        $adapter = \Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter();
        $adapter->getDriver()->getConnection()->beginTransaction();
        try {
            if ($this->_truncateTables){
                Post::truncate();
                Tag::truncate();
                Upload::truncate();
            }
            if ($this->_loadUser){
                $this->_importUser();
            }
            $this->_importTags();
            $this->_importPosts();
            if ($this->_loadMediaFilesRemote || $this->_loadMediaFilesLocal){
                $this->_importMedia();
            }
            $adapter->getDriver()->getConnection()->commit();
        } catch (\Exception $e){
            $adapter->getDriver()->getConnection()->rollback();
            return false;
        }
        return array('succeed' => $this->_succeed, 'failed' => $this->_failed);
    }

    private function _importUser(){
        foreach($this->_user as $slug => $ds){
            try {
                User::create($ds);
                $this->_succeed[] = 'user: ' . $slug;
            } catch (\Exception $e){
                $this->_failed[] = [
                    'user: ' . $slug,
                    $e->getMessage()
                ];
            }
        }
        return true;
    }

    private function _importTags(){
        $data = array();
        $key = '';
        if ($this->_useCategoryAsTag){
            $data = $this->_categories;
            $key = 'cat';
        } else {
            $data = $this->_tags;
            $key = 'tag';
        }
        foreach($data as $slug => $ds){
            try {
                Tag::create($ds);
                $this->_succeed[] = $key . ': ' . $slug;
            } catch (\Exception $e){
                $this->_failed[] = [
                    $key . ': ' . $slug,
                    $e->getMessage()
                ];
            }
        }
        return true;
    }

    private function _importPosts(){
        foreach($this->_posts as $slug => $ds){
            try {
                Post::create($ds);
                $this->_succeed[] = 'post: ' . $slug;
            } catch (\Exception $e){
                $this->_failed[] = [
                    'post: ' . $slug,
                    $e->getMessage()
                ];
            }
        }
        return true;
    }
    /*
    private function _importPostTags(){

    }
    */
    private function _importMedia(){
        $info = array();
        if ($this->_loadMediaFilesRemote){
            // if we load media from an external server...
            $this->_tmpPath = Leafpub::path('content/uploads/import/' . uniqid());
            if (!Leafpub::makeDir($this->_tmpPath)){
                throw new \Exception('Couldn\'t create ' . $this->_tmpPath );
            }
        } elseif ($this->_loadMediaFilesLocal){
            // if we load from a temp dir of our server...
            $this->_tmpPath = Leafpub::path('content/uploads/import');
        } else {
            throw new \Exception('Haha, nice try....');
        }
        

        foreach ($this->_media as $file){
            $path = '';
            $filename = $file['filename'] . '.' . $file['extension'];
            // CURL load
            if ($this->_loadMediaFilesRemote){
                $this->loadMediaFile($filename, $file['url']);
                $path = $this->_tmpPath . '/' . $filename;
            }
            // Just copy the file
            if ($this->_loadMediaFilesLocal){
                $path = $this->_tmpPath . '/' . $file['attachedFile'];
            }
            if (is_file($path)){
                try {
                    Upload::create([$filename, file_get_contents($path), $info]);
                    $this->_succeed[] = 'media: ' . $filename;
                }
                catch (\Exception $e){
                    $this->_failed[] = [
                        'media: ' . $filename,
                        $e->getMessage()
                    ];
                }
            }
        }
        if ($this->_loadMediaFilesRemote){
            Leafpub::removeDir($this->_tmpPath);
        }
        return true;
    }
}
?>