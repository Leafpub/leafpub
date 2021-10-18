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

use Composer\Semver\Comparator;
use DirectoryIterator;
use Leafpub\Leafpub;
use ZipArchive;

class Plugin extends AbstractModel
{
    /**
     * Constants
     **/
    public const ALREADY_EXISTS = 1;
    public const INVALID_NAME = 2;
    public const INVALID_DIR = 3;
    public const NOT_FOUND = 4;
    public const VERSION_MISMATCH = 5;

    public static $plugins;
    /**
     * @var null|\Leafpub\Models\Tables\Plugin
     */
    protected static ?\Leafpub\Models\Tables\Plugin $_instance = null;

    /**
     * @var array<class-string<\Leafpub\Controller\AdminController>>|array<class-string<\Leafpub\Controller\APIController>>|array<class-string<\Leafpub\Leafpub>>|array<class-string<\Leafpub\Models\Plugin>>
     */
    protected static array $allowedCaller = [
        'Leafpub\\Controller\\AdminController',
        'Leafpub\\Controller\\APIController',
        'Leafpub\\Leafpub',
        'Leafpub\\Models\\Plugin',
    ];

    public static function getMany(array $options = [], &$pagination = null)
    {
        $options = array_merge([
            'items_per_page' => 10,
            'page' => 1,
            'query' => null,
            'sort' => 'DESC',
        ], (array) (array) (array) (array) (array) $options);

        $where = null;

        if ($options['query'] !== null) {
            $where = function ($wh) use ($options) {
                $wh->like('name', '%' . $options['query'] . '%');
            };
        }

        $total_items = self::count($where);

        // Generate pagination
        $pagination = Leafpub::paginate(
            $total_items,
            $options['items_per_page'],
            $options['page']
        );

        $offset = ($pagination['current_page'] - 1) * $pagination['items_per_page'];
        $count = $pagination['items_per_page'];

        $selectOne = self::getModel()->getSql()->select();
        $selectOne->where(['enabled' => '1']);
        $selectOne->limit($count)->offset($offset);

        $selectZero = self::getModel()->getSql()->select();
        $selectZero->where(['enabled' => '0']);
        $selectZero->limit($count)->offset($offset);

        if ($where) {
            $selectOne->where($where);
            $selectZero->where($where);
        }

        $selectZero->combine($selectOne, 'UNION');
        $databasePlugins = self::getModel()->selectWith($selectZero)->toArray();

        // Merge plugins from database with plugins from filesystem
        // only, when we're not searching...'
        if ($options['query'] == null) {
            $plugins = array_map(
                function ($arr) use ($databasePlugins) {
                                foreach ($databasePlugins as $plugin) {
                                    if ($plugin['dir'] == $arr['dir']) {
                                        $arr['install_date'] = $plugin['install_date'];
                                        if ($plugin['enabled'] == 1) {
                                            $arr['enabled'] = 1; // Set to 1 because this is the HDD Plugin, not the database plugin...
                                            $arr['enable_date'] = $plugin['enable_date'];
                                        }

                                        return $arr;
                                    }
                                }

                                return $arr;
                            },
                self::getAll()
            );
        } else {
            $plugins = $databasePlugins;
        }
        foreach ($plugins as $key => $plugin) {
            $plugins[$key] = self::normalize($plugin);
        }

        return $plugins;
    }

    public static function getOne($plugin)
    {
        try {
            $plugin = self::getModel()->select(['dir' => $plugin])->current();
            if (!$plugin) {
                return false;
            }
        } catch (\PDOException $e) {
            return false;
        }

        return self::normalize($plugin->getArrayCopy());
    }

    public static function create($plugin)
    {
        if (!self::isAllowedCaller()) {
            return false;
        }

        // Is the name valid?
        if ($plugin['name'] === '' || Leafpub::isProtectedSlug($plugin['name'])) {
            throw new \Exception('Invalid name: ' . $plugin['name'], self::INVALID_NAME);
        }

        if (
            $plugin['dir'] === '' ||
            Leafpub::isProtectedSlug($plugin['dir']) ||
            !is_dir(Leafpub::path('content/plugins/' . $plugin['dir']))
        ) {
            throw new \Exception('Invalid dir: ' . $plugin['dir'], self::INVALID_DIR);
        }

        if (
            (LEAFPUB_VERSION !== '{{version}}') &&
            !Comparator::greaterThanOrEqualTo(LEAFPUB_VERSION, $plugin['requires'])
        ) {
            throw new \Exception('Plugin needs Leafpub Version ' . $plugin['requires'] . ', but version ' . LEAFPUB_VERSION . ' detected', self::VERSION_MISMATCH);
        }

        $plugin = self::normalize($plugin);

        try {
            self::getModel()->insert($plugin);
            $plugin_id = (int) (int) self::getModel()->getLastInsertValue();
            if ($plugin_id <= 0) {
                return false;
            }
        } catch (\PDOException $e) {
            return false;
        }

        return true;
    }

    public static function edit($plugin)
    {
        if (!self::isAllowedCaller()) {
            return false;
        }

        // Is the name valid?
        if ($plugin['name'] === '' || Leafpub::isProtectedSlug($plugin['name'])) {
            throw new \Exception('Invalid name: ' . $plugin['name'], self::INVALID_NAME);
        }

        if (
            $plugin['dir'] === '' ||
            Leafpub::isProtectedSlug($plugin['dir']) ||
            !is_dir(Leafpub::path('content/plugins/' . $plugin['dir']))
        ) {
            throw new \Exception('Invalid dir: ' . $plugin['dir'], self::INVALID_DIR);
        }

        if (LEAFPUB_VERSION != '{{version}}' && !Comparator::greaterThanOrEqualTo(LEAFPUB_VERSION, $plugin['requires'])) {
            throw new \Exception('Plugin needs Leafpub Version ' . $plugin['requires'] . ', but version ' . LEAFPUB_VERSION . ' detected', self::VERSION_MISMATCH);
        }
        $dbPlugin = self::getOne($plugin['dir']);
        $plugin = array_merge($dbPlugin, $plugin);
        $id = $plugin['id'];
        unset($plugin['id']);

        try {
            self::getModel()->update($plugin, ['id' => $id]);
        } catch (\PDOException $e) {
            return false;
        }

        return true;
    }

    public static function delete($plugin)
    {
        if (!self::isAllowedCaller()) {
            return false;
        }

        try {
            $rowCount = self::getModel()->delete(['dir' => $plugin]);

            return $rowCount > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public static function count($where = null)
    {
        try {
            $model = self::getModel();
            $select = $model->getSql()->select();
            $select->columns(['num' => new \Zend\Db\Sql\Expression('COUNT(*)')]);

            if ($where) {
                $select->where($where);
            }

            return (int) $model->selectWith($select)->current()['num'];
        } catch (\Exception $e) {
        }
    }

    /**
     * Returns a list of all available plugins
     *
     *
     **/
    public static function getAll(): array
    {
        $plugins = [];

        $iterator = new DirectoryIterator(Leafpub::path('content/plugins'));
        foreach ($iterator as $dir) {
            if (!$dir->isDot() && $dir->isDir()) {
                // Attempt to read and decode theme.json
                $plugin_json = $dir->getPathname() . '/plugin.json';
                $json = json_decode(file_get_contents($plugin_json), true);
                if (!$json) {
                    continue;
                }
                // Add it
                $plugins[] = array_merge($json, ['dir' => $dir->getFilename()]);
            }
        }

        return $plugins;
    }

    /**
     * Returns a list of all activated Plugins
     *
     * @return mixed
     */
    public static function getActivatedPlugins()
    {
        try {
            return self::getModel()->select(['enabled' => '1'])->toArray();
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Upload a zip, unzip, create a folder, copy files
     *
     * @return bool
     */
    public static function install($zipFile)
    {
        if (!self::isAllowedCaller()) {
            return false;
        }

        $bPluginJson = false;
        $bPluginPhp = false;
        $bUpdate = false;

        $zip = new ZipArchive();
        $res = $zip->open($zipFile, ZipArchive::CHECKCONS);

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

        // Check for the mandatory files
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $file = $zip->getNameIndex($i);
            if (strstr($file, 'plugin.json')) {
                $bPluginJson = true;
            }
            if (strstr($file, 'Plugin.php')) {
                $bPluginPhp = true;
            }
        }

        // All mandatory files are present
        if (!$bPluginJson || !$bPluginPhp) {
            throw new \Exception('Mandatory file missing');
        }

        // Get the plugin folder
        $ns = $zip->getNameIndex(0);

        // Plugin exists already
        if (is_dir(Leafpub::path('content/plugins/' . $ns))) {
            // We're doing an update'
            Leafpub::removeDir(Leafpub::path('content/plugins/' . $ns));
            $bUpdate = true;
        }

        if (!$zip->extractTo(Leafpub::path('content/plugins'))) {
            throw new \Exception('Unable to extract zip');
        }

        $plugin = json_decode(
            file_get_contents(
                Leafpub::path("content/plugins/$ns/plugin.json")
            ),
            true
        );
        $plugin['dir'] = $ns;

        $plugin['img'] = $plugin['image'];

        unset($plugin['image']);
        unset($plugin['routes']);
        unset($plugin['isWidget']);

        return $bUpdate ? self::edit($plugin) : self::create($plugin);
    }

    /**
     * Delete the plugin folder
     *
     * @param string $plugin the plugin to deinstall
     */
    public static function deinstall($plugin): bool
    {
        if (!self::isAllowedCaller()) {
            return false;
        }

        if (!is_dir(Leafpub::path("content/plugins/$plugin"))) {
            return false;
        }

        $plugin = self::getOne($plugin);
        if (!$plugin) {
            return false;
        }

        if ($plugin['enabled'] == 1) {
            self::deactivate($plugin['dir']);
        }

        self::delete($plugin['dir']);

        Leafpub::removeDir(self::path('content/plugins/' . $plugin['dir']));

        return true;
    }

    /**
     * Activates an installed plugin
     *
     * @param string $dir the plugin to activate
     */
    public static function activate($dir): bool
    {
        if (!self::isAllowedCaller()) {
            return false;
        }

        // Always sanitize user input ;-)
        if (!is_dir(Leafpub::path("content/plugins/$dir"))) {
            return false;
        }

        $plugin = self::getOne($dir);

        if (!$plugin) {
            $plugin = json_decode(
                file_get_contents(
                    Leafpub::path("content/plugins/$dir/plugin.json")
                ),
                true
            );
            $plugin['dir'] = $dir;

            $plugin['img'] = $plugin['image'];

            unset($plugin['image']);
            unset($plugin['routes']);
            unset($plugin['isWidget']);

            try {
                self::create($plugin);
            } catch (\Exception $e) {
                return false;
            }
        }

        // Create Plugin and call _afterActivation
        $plug = "Leafpub\\Plugins\\$dir\\Plugin";
        $plug::afterActivation();

        // Update database
        try {
            $rowCount = self::getModel()->update(
                [
                                'enabled' => '1',
                                'enable_date' => new \Zend\Db\Sql\Expression('NOW()'),
                            ],
                ['dir' => $dir]
            );

            return $rowCount > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Deactivates an installed plugin
     *
     * @param string $dir the plugin to deactivate
     */
    public static function deactivate($dir): bool
    {
        if (!self::isAllowedCaller()) {
            return false;
        }

        if (!is_dir(Leafpub::path("content/plugins/$dir"))) {
            return false;
        }

        $plugin = self::getOne($dir);
        if (!$plugin) {
            return false;
        }

        // Get the plugin instance from the static array and call _afterDeactivation
        $plug = "Leafpub\\Plugins\\$dir\\Plugin";
        $plug::afterDeactivation();
        unset(Plugin::$plugins[$dir]);

        // Update database
        try {
            $rowCount = self::getModel()->update(
                [
                                'enabled' => '0',
                                'enable_date' => new \Zend\Db\Sql\Expression('NOW()'),
                            ],
                ['dir' => $dir]
            );

            return $rowCount > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Checks, if a plugin exists
     *
     * @param string $dir
     */
    public static function exists($dir): bool
    {
        try {
            return (bool) self::getOne($dir);
        } catch (\PDOException $e) {
            return false;
        }
    }

    protected static function getModel(): \Leafpub\Models\Tables\Plugin
    {
        if (self::$_instance == null) {
            self::$_instance = new Tables\Plugin();
        }

        return self::$_instance;
    }

    /**
     * Normalize types for certain fields
     *
     * @param array $plugin
     */
    private static function normalize($plugin): array
    {
        // Cast to integer
        $plugin['id'] = (int) $plugin['id'];
        $plugin['isAdminPlugin'] = (int) $plugin['isAdminPlugin'];
        $plugin['isMiddleware'] = (int) $plugin['isMiddleware'];
        $plugin['enabled'] = (int) $plugin['enabled'];

        // Convert dates from UTC to local
        $plugin['install_date'] = Leafpub::utcToLocal($plugin['install_date']);
        $plugin['enable_date'] = Leafpub::utcToLocal($plugin['enable_date']);

        return $plugin;
    }
}
