<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2017 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

class Update extends Leafpub {
    const GITHUB_API_URL = 'https://api.github.com/repos/';
    //const REPO_URL = 'repos/:owner/:repo/';
    const RELEASE_UPDATE_URL =  '/releases/latest'; //self::GITHUB_API_URL . self::REPO_URL .
    const CONTENT_URL = '/contents/'; //self::GITHUB_API_URL . self::REPO_URL . 

    public static function updateRegisterFiles(){
        $url = self::generateApiUrl('https://github.com/Leafpub/register'. self::CONTENT_URL);//str_replace([':owner', ':repo'], ['Leafpub', 'register'], self::CONTENT_URL);
        $ret = json_decode(self::getRemoteData($url));
        for ($i = 2; $i < count($ret); $i++){
            try {
                $file = self::path('source/config/' . $ret[$i]->name);
                $data = self::getRemoteData($ret[$i]->download_url);
                file_put_contents($file, $data);
            } catch(\Exception $e){
                return false;
            }
        }
    }

    public static function checkForUpdates(){
        return [
            'Leafpub' => self::checkForLeafpubUpdate(),
            'plugins' => self::checkPluginsForUpdate(),
            'languages' => self::checkLanguagesForUpdate(),
            'themes' => self::checkThemesForUpdate()
        ];
    }
    
    public static function checkForLeafpubUpdate(){
        $url = self::generateApiUrl('https://github.com/Leafpub/leafpub'. self::RELEASE_UPDATE_URL);
        $cls = json_decode(self::getRemoteData($url));
        if ($cls->tag_name > LEAFPUB_VERSION){
            return self::downloadZip($cls->assets[0]->url, self::path('content/uploads/' . $cls->assets[0]->name));
        } else {
            return false;
        }
    }

    protected function checkPluginsForUpdate(){
        $plugins = \Leafpub\Models\Plugin::getActivatedPlugins();
        $data = self::processJsonFile(self::path('source/config/plugins.json'));
        $x = [];
        foreach($plugins as $plugin){
            if (isset($data[$plugin['name']])){
                $x[$plugin['name']] = ['url' => $data[$plugin['name']], 'version' => $plugin['version']];
            }
        }
        exit(
            var_dump(
                $x
            )
        );
    }

    protected static function checkLanguagesForUpdate(){

    }

    protected static function checkThemesForUpdate(){

    }
    
    protected static function getRemoteData($url){
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, ['User-Agent: Leafpub', 'Accept: application/vnd.github.v3.raw+json']);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($handle);
        curl_close($handle);
        return $ret;
    }

    protected static function downloadZip($url, $targetFile){
        try {
            $targetFile = fopen($targetFile, 'w');
            $handle = curl_init($url);
            curl_setopt($handle, CURLOPT_HTTPHEADER, ['User-Agent: Leafpub', 'Accept: application/octet-stream']);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($handle, CURLOPT_FILE, $targetFile );
            curl_setopt($handle, CURLOPT_NOPROGRESS, 0);
            curl_setopt($handle, CURLOPT_PROGRESSFUNCTION, array(self, 'curl_progress_callback'));

            $ret = curl_exec($handle);
            curl_close($handle);
            fclose($targetFile);
            return $ret;
        } catch(\Exception $e){
            echo $e->getMessage();
        }
    }

    protected static function generateApiUrl($url){
        return str_replace('https://github.com/', self::GITHUB_API_URL, $url);
    }

    protected static function processJsonFile($file){
        $data = json_decode(file_get_contents($file), true);
        foreach($data as $d){
            $ret[$d['name']] = $d['url'];
        }
        return $ret;
    }

    protected static function compareVersions(){

    }
    
    public static function curl_progress_callback($dltotal, $dlnow, $ultotal, $ulnow){
        //echo $dltotal; //Reports correct value
        echo ($dltotal-$dlnow) . '<br>';
    }
}