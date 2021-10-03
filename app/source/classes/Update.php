<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use Composer\Semver\Comparator;
use Leafpub\Models\Setting;
use ZipArchive;

class Update extends Leafpub
{
    public const GITHUB_API_URL = 'https://api.github.com/repos/';
    //const REPO_URL = 'repos/:owner/:repo/';
    public const RELEASE_UPDATE_URL = '/releases'; //self::GITHUB_API_URL . self::REPO_URL .
    public const CONTENT_URL = '/contents/'; //self::GITHUB_API_URL . self::REPO_URL .

    public const UPDATE_TYPE_CORE = 'core';
    public const UPDATE_TYPE_PLUGIN = 'plugin';
    public const UPDATE_TYPE_THEME = 'theme';
    public const UPDATE_TYPE_LANGUAGE = 'language';

    /**
     * @var array<string, string>
     */
    private static array $_paths = [
        self::UPDATE_TYPE_CORE => '',
        self::UPDATE_TYPE_PLUGIN => 'content/plugins',
        self::UPDATE_TYPE_THEME => 'content/themes',
        self::UPDATE_TYPE_LANGUAGE => 'source/languages',
    ];

    public static function doUpdate(array $params)
    {
        $name = $params['name'];
        if (isset($params['sign'])) {
            $sign = $params['sign'];
            $data = self::decodeData($sign);
            if (property_exists($data, 'link') && property_exists($data, 'newVersion') && in_array($data->type, ['core', 'plugin', 'language', 'theme'])) {
                $url = self::generateApiUrl($data->link . self::RELEASE_UPDATE_URL . '/tags/' . $data->newVersion);
                $cls = json_decode(self::getRemoteData($url));
                $dlPath = self::path('content/uploads/' . $cls->assets[0]->name);
                self::$logger->debug('dlPath: ' . $dlPath);
                $dl = self::downloadZip($cls->assets[0]->url, $dlPath);
                if ($dl) {
                    return self::extractData($name, $dlPath, $data);
                }
            } else {
                Session::logout();

                return false;
            }
        }
    }

    public static function extractData($name, $dlPath, $data): bool
    {
        $zip = new ZipArchive();
        $res = $zip->open($dlPath, ZipArchive::CHECKCONS);
        self::$logger->debug('extractData(' . $name . ', ' . $dlPath . ')');
        // Check if zip is ok
        if ($res !== true) {
            switch ($res) {
                case ZipArchive::ER_NOZIP:
                    throw new \Exception('not a zip archive');
                case ZipArchive::ER_INCONS:
                    throw new \Exception('consistency check failed');
                case ZipArchive::ER_CRC:
                    throw new \Exception('checksum failed');
                default:
                    throw new \Exception('error ' . $res);
            }
        }

        $unzipPath = self::path('content/uploads/' . uniqid('', true));
        $copyPath = self::path(self::$_paths[$data->type]);

        $unzip = $zip->extractTo($unzipPath);
        if (!$unzip) {
            throw new \Exception('Unable to extract zip');
        }
        $zip->close();

        if ($data->type !== self::UPDATE_TYPE_CORE) {
            if ($data->type === self::UPDATE_TYPE_LANGUAGE) {
                $copyPath = self::path(self::$_paths[$data->type]);
            } elseif ($data->type === self::UPDATE_TYPE_THEME) {
                $copyPath = self::path(self::$_paths[$data->type] . '/' . lcfirst($name));
            } else {
                $copyPath = self::path(self::$_paths[$data->type] . '/' . $name);
            }
        } else {
            $copyPath = self::path(self::$_paths[$data->type]);
        }

        self::rcopy($unzipPath . '/' . self::fileName($dlPath), $copyPath);
        unlink($dlPath);
        self::removeDir($unzipPath);

        return true;
    }

    public static function updateRegisterFiles()
    {
        $url = self::generateApiUrl('https://github.com/Leafpub/register' . self::CONTENT_URL); //str_replace([':owner', ':repo'], ['Leafpub', 'register'], self::CONTENT_URL);
        $ret = json_decode(self::getRemoteData($url));
        $retCount = count($ret);
        for ($i = 2; $i < $retCount; ++$i) {
            try {
                $file = self::path('source/config/' . $ret[$i]->name);
                $data = self::getRemoteData($ret[$i]->download_url);
                file_put_contents($file, $data);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    public static function checkForUpdates(): array
    {
        $ret = [];
        $check = self::checkForLeafpubUpdate();
        if ($check) {
            $ret['Leafpub'] = $check;
        }
        $check = self::checkPluginsForUpdate();
        if ($check !== []) {
            $ret['plugins'] = $check;
        }
        $check = self::checkLanguagesForUpdate();
        if ($check !== []) {
            $ret['languages'] = $check;
        }
        $check = self::checkThemesForUpdate();
        if ($check !== []) {
            $ret['themes'] = $check;
        }

        return $ret;
    }

    public static function checkForLeafpubUpdate()
    {
        if (LEAFPUB_VERSION === '{{version}}' || LEAFPUB_VERSION === 'dev') {
            return false;
        }
        $url = self::generateApiUrl('https://github.com/Leafpub/leafpub' . self::RELEASE_UPDATE_URL);
        $cls = self::parseReleaseData(self::getRemoteData($url));
        if ($cls && Comparator::greaterThan($cls->tag_name, LEAFPUB_VERSION)) {
            return [
                'newVersion' => $cls->tag_name,
                'data' => self::encodeData(['link' => 'https://github.com/Leafpub/leafpub', 'newVersion' => $cls->tag_name, 'type' => self::UPDATE_TYPE_CORE]),
            ];
        }

        return false;
    }

    public static function getRemoteData($url)
    {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, ['User-Agent: Leafpub', 'Accept: application/vnd.github.v3.raw+json']);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($handle);
        curl_close($handle);

        return $ret;
    }

    public static function downloadZip($url, $targetFile): bool
    {
        try {
            $targetFile = fopen($targetFile, 'w');
            $handle = curl_init($url);
            curl_setopt($handle, CURLOPT_HTTPHEADER, ['User-Agent: Leafpub', 'Accept: application/octet-stream']);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($handle, CURLOPT_FILE, $targetFile);
            curl_setopt($handle, CURLOPT_NOPROGRESS, 0);
            curl_setopt($handle, CURLOPT_PROGRESSFUNCTION, [self, 'curl_progress_callback']);

            $ret = curl_exec($handle);
            curl_close($handle);
            fclose($targetFile);

            return true;
        } catch (\Exception $e) {
            echo $e->getMessage();

            return false;
        }
    }

    public static function generateApiUrl($url)
    {
        return str_replace('https://github.com/', self::GITHUB_API_URL, $url);
    }

    public static function encodeData(array $data): string
    {
        return \Firebase\JWT\JWT::encode($data, Setting::getOne('auth_key'));
    }

    public static function decodeData(string $token)
    {
        if (is_string($token) && ($token !== null)) {
            return \Firebase\JWT\JWT::decode($token, Setting::getOne('auth_key'), ['HS256']);
        }

        return false;
    }

    public static function curl_progress_callback($dltotal, $dlnow, $ultotal, $ulnow): void
    {
        //echo $dltotal; //Reports correct value
        //echo ($dltotal-$dlnow) . '<br>';
    }

    protected function checkPluginsForUpdate(): array
    {
        $plugins = \Leafpub\Models\Plugin::getActivatedPlugins();
        $data = self::processJsonFile(self::path('source/config/plugins.json'));
        $updatablePlugins = [];
        foreach ($plugins as $plugin) {
            if (isset($data[$plugin['name']])) {
                $url = self::generateApiUrl($data[$plugin['name']] . self::RELEASE_UPDATE_URL);
                $cls = self::parseReleaseData(self::getRemoteData($url));
                if ($cls && Comparator::greaterThan($cls->tag_name, $plugin['version'])) {
                    $updatablePlugins[$plugin['name']] = [
                        'name' => $plugin['name'],
                        'data' => self::encodeData(['link' => $data[$plugin['name']], 'oldVersion' => $plugin['version'], 'newVersion' => $cls->tag_name, 'type' => self::UPDATE_TYPE_PLUGIN]),
                        'newVersion' => $cls->tag_name,
                    ];
                }
            }
        }

        return $updatablePlugins;
    }

    protected static function checkLanguagesForUpdate(): array
    {
        $languages = Language::getAll();
        $data = self::processJsonFile(self::path('source/config/languages.json'));
        $updatableLanguages = [];
        foreach ($languages as $language) {
            if (isset($data[$language['name']])) {
                $url = self::generateApiUrl($data[$language['code']] . self::RELEASE_UPDATE_URL);
                $cls = self::parseReleaseData(self::getRemoteData($url));
                if ($cls && Comparator::greaterThan($cls->tag_name, $language['version'])) {
                    $updatableLanguages[$language['code']] = [
                        'name' => $language['code'],
                        'data' => self::encodeData(['link' => $data[$language['code']], 'oldVersion' => $language['version'], 'newVersion' => $cls->tag_name, 'type' => self::UPDATE_TYPE_LANGUAGE]),
                        'newVersion' => $cls->tag_name,
                    ];
                }
            }
        }

        return $updatableLanguages;
    }

    protected static function checkThemesForUpdate(): array
    {
        $themes = Theme::getAll();
        $data = self::processJsonFile(self::path('source/config/themes.json'));
        $updatableThemes = [];
        foreach ($themes as $theme) {
            if (isset($data[$theme['name']])) {
                $url = self::generateApiUrl($data[$theme['name']] . self::RELEASE_UPDATE_URL);
                $cls = self::parseReleaseData(self::getRemoteData($url));
                if ($cls && Comparator::greaterThan($cls->tag_name, $theme['version'])) {
                    $updatableThemes[$theme['name']] = [
                        'name' => $theme['name'],
                        'data' => self::encodeData(['link' => $data[$theme['name']], 'oldVersion' => $theme['version'], 'newVersion' => $cls->tag_name, 'type' => self::UPDATE_TYPE_THEME]),
                        'newVersion' => $cls->tag_name,
                    ];
                }
            }
        }

        return $updatableThemes;
    }

    protected static function processJsonFile($file)
    {
        $data = json_decode(file_get_contents($file), true);
        foreach ($data as $d) {
            $ret[$d['name']] = $d['url'];
        }

        return $ret;
    }

    /**
     * Parses the JSON data in $releaseJson and returns the first stable release
     *
     * @param string $releaseJson
     *
     * @return \stdClass
     */
    protected static function parseReleaseData($releaseJson)
    {
        $data = json_decode($releaseJson);
        foreach ($data as $release) {
            if ($release->prerelease === false) {
                return $release;
            }
        }

        return false;
    }

    // copies files and non-empty directories
    private static function rcopy($src, $dst): void
    {
        self::$logger->debug('rcopy(' . $src . ', ' . $dst . ')');
        if (is_dir($src)) {
            $files = scandir($src);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    self::rcopy("$src/$file", "$dst/$file");
                }
            }
        } elseif (file_exists($src)) {
            if (!copy($src, $dst)) {
                self::$logger->error('Didn\'t copy ' . $src . ' to ' . $dst);
            }
        }
    }
}
