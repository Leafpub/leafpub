<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use Leafpub\Models\Setting,
    Leafpub\Models\Plugin,
    Symfony\Component\EventDispatcher\EventDispatcher;

/**
* Leafpub
*
* base class for the Leafpub API
* @package Leafpub
*
**/
class Leafpub {

    /**
    * Properties
    **/
    protected static $database, $language, $listeners, $dispatcher, $logger;

    /**
    * Initialize the app
    *
    * @return void
    * @throws \Exception
    *
    **/
    public static function run() {
        try {
            self::$logger = new \Monolog\Logger('Leafpub::Logger');
            $logLvl = \Monolog\Logger::INFO;
            if(LEAFPUB_DEV == 1){
                self::$logger->pushProcessor(new \Monolog\Processor\IntrospectionProcessor());
                $logLvl = \Monolog\Logger::DEBUG;
            }
            self::$logger->pushHandler(new \Monolog\Handler\RotatingFileHandler(Leafpub::path('log/leafpub.log'), 30, $logLvl));
            self::$logger->debug('Startup...');
            // Connect to the database
        
            self::$logger->debug('Connecting to database');
            Database::connect();
        }
        catch (\UnexpectedValueException $ue){
            exit(Error::system([
                'title' => 'Logger Error',
                'message' => $ue->getMessage()
            ]));
        } 
        catch(\Exception $e) {
            switch($e->getCode()) {
                case Database::NOT_CONFIGURED:
                    // Database isn't configured, launch the installer
                    self::$logger->error('Database isn\'t configured');
                    header('Location: ' . self::url('source/installer/'));
                    exit();
                default:
                    self::$logger->error('Database Error: ' . $e->getMessage());
                    $title = 'Database Error';
                    $message = 'Unable to connect to the database: ' . $e->getMessage();
            }

            exit(Error::system([
                'title' => $title,
                'message' => $message
            ]));
        }

        // Load settings
        try {
            self::$logger->debug('Load settings');
            Setting::load();
        } catch(\Exception $e) {
            exit(Error::system([
                'title' => 'Settings Error',
                'message' => $e->getMessage()
            ]));
        }

        // Load the language pack
        try {
            self::$logger->debug('Load language');
            Language::load(Setting::getOne('language'));
        } catch(\Exception $e) {
            exit(Error::system([
                'title' => 'Translation Pack Error',
                'message' => $e->getMessage()
            ]));
        }

        // Set encoding
        mb_internal_encoding('UTF-8');

        // Set timezone
        date_default_timezone_set(Setting::getOne('timezone'));

         // Create the Symfony EventDispatcher
        self::$logger->debug('Create event dispatcher');
        self::$dispatcher = new EventDispatcher();
        self::_registerCoreListener();
    }

    private static function _registerCoreListener(){
        // Add Application Listener
        $appListener = new Listeners\Application();
        self::on(Events\Application\Startup::NAME, array($appListener, 'onApplicationStartup'));
        
        // Add Post Listener
        $postListener = new Listeners\Post();
        self::on(Events\Post\Add::NAME, array($postListener, 'onPostAdd'));
        self::on(Events\Post\Added::NAME, array($postListener, 'onPostAdded'));
        self::on(Events\Post\BeforeRender::NAME, array($postListener, 'onBeforeRender'));

        // Handle thumbnail generation
        self::on(Events\Upload\GenerateThumbnail::NAME, __NAMESPACE__ . '\Models\Upload::handleThumbnail', -999);
    }

    public static function getLogger(){
        return self::$logger;
    }

    public static function registerPlugins(\Slim\App $app){
        // Only register plugins if the static array is null.
        if (Plugin::$plugins == null){
            self::$logger->debug('Register plugins');
            try {
                $plugins = Plugin::getActivatedPlugins();

                foreach($plugins as $plugin){
                    $ns = $plugin['dir'];
                    $class = 'Leafpub\\Plugins\\' . $ns . '\\Plugin';
                    $pls[$ns] = new $class($app);
                    self::$logger->debug('Register plugin \'' . $ns . '\'');
                }
                Plugin::$plugins = $pls;
            }
            catch (\Zend\Db\Adapter\Exception\InvalidQueryException $ze){

            } 
            catch (\Exception $e){
                exit(Error::system([
                    'title' => 'Register Plugin Error',
                    'message' => $e->getMessage()
                ]));
            }
        }
    }
    /**
    * Returns the file extension of $filename (lowercase, without a dot)
    *
    * @param String $filename
    * @return String
    *
    **/
    public static function fileExtension($filename) {
        return mb_strtolower(pathinfo($filename)['extension']);
    }

    /**
    * Returns the filename without an extension
    *
    * @param String $filename
    * @return String
    *
    **/
    public static function fileName($filename) {
        return pathinfo($filename)['filename'];
    }

    /**
    * Given a size in bytes, returns the most appropriate size as a string. Ex: 4.20k
    *
    * @param long size
    * @param int $precision
    * @return String
    *
    **/
    public static function formatBytes($size, $precision = 2) {
        $base = log($size, 1000);
        $suffixes = ['', 'kb', 'mb', 'gb', 'tb'];
        return round(pow(1000, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }

    /**
    * Returns no more than $num characters from $string, breaking at word
    *
    * @param String $string
    * @param int $num
    * @return String
    *
    **/
    public static function getChars($string, $num) {
        // Remove line breaks
        $string = preg_replace('/\r|\n/', ' ', $string);

        // Trim to max length and break at word
        $string = mb_substr($string, 0, $num);
        $string = mb_substr($string, 0, mb_strrpos($string, ' '));

        return $string;
    }

    /**
    * Returns no more than $num words from $string
    *
    * @param String $string
    * @param int $num
    * @return String
    *
    **/
    public static function getWords($string, $num) {
        // Remove line breaks
        $string = preg_replace('/\r|\n/', ' ', $string);

        // Explode and slice off extra words
        $string = explode(' ', $string);
        $string = array_slice($string, 0, $num);

        return implode(' ', $string);
    }

    /**
    * Returns true if the specified URL matches the current URL
    *
    * @param String $test_url
    * @return bool
    *
    **/
    public static function isCurrentUrl($test_url) {
        // Parse the current URL
        $current_url = parse_url(self::url($_SERVER['REQUEST_URI']));

        // Parse test URL
        $test_url = parse_url($test_url);

        // Parsing error, no match
        if(!$current_url || !$test_url) return false;

        // Do the hosts match OR is the nav link relative?
        if($current_url['host'] === $test_url['host'] || empty($test_url['host'])) {
            // Compare paths without trailing slashes
            return rtrim($current_url['path'], '/') === rtrim($test_url['path'], '/');
        }

        return false;
    }

    /**
    * Returns true if the specified URI is the homepage. Defaults to the current URI.
    *
    * @param null $test_url
    * @return bool
    *
    **/
    public static function isHomepage($test_url = null) {
        // Default to the current URI
        if(!$test_url) $test_url = $_SERVER['REQUEST_URI'];

        return explode('?', $test_url)[0] === '/';
    }

    /**
    * Returns true if Leafpub has been installed
    *
    * @return bool
    *
    **/
    public static function isInstalled() {
        // Simple check for database.php
        return file_exists(Leafpub::path('database.php'));
    }

    /**
    * Determines whether a slug is protected (i.e. used in settings.slugs)
    *
    * @param String $slug
    * @return bool
    *
    **/
    public static function isProtectedSlug($slug) {
        return in_array($slug, [
            'api',      // reserved for the API
            'leafpub', // reserved for future use
            Setting::getOne('frag_admin'),
            Setting::getOne('frag_author'),
            Setting::getOne('frag_blog'),
            Setting::getOne('frag_feed'),
            Setting::getOne('frag_page'),
            Setting::getOne('frag_search'),
            Setting::getOne('frag_tag')
        ]);
    }

    /**
    * Returns true if the website is being served over HTTPS
    *
    * @return bool
    *
    **/
    public static function isSsl() {
        // Some servers (e.g. Cloud9) don't populate $_SERVER[HTTPS], so we have to check the value
        // of $_SERVER[REQUEST_SCHEME] instead.
        if($_SERVER['REQUEST_SCHEME'] === 'https') return true;

         // If https is empty or it is  off, perform extra checks
       if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off'){
               // Detect if HTTP_X_FORWARDED_PROTO is set to https; and if so set HTTPS to on.
               if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'){
                   $_SERVER['HTTPS']='on';
               }
       }
        // Other servers will populate $_SERVER[HTTPS] when SSL is on. IIS is unique because the
        // value will be 'off' when SSL is not enabled.
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }

    /**
    * Returns true if $email is a valid email address
    *
    * @param String $email
    * @return bool
    *
    **/
    public static function isValidEmail($email) {
        return !!filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
    * Convert a local date string to UTC
    *
    * @param String $local_date
    * @return String
    *
    **/
    public static function localToUtc($local_date) {
        $dt = new \DateTime($local_date, new \DateTimeZone(Setting::getOne('timezone')));
        $dt->setTimeZone(new \DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
    }

    /**
    * Makes the specified directory if it doesn't exist. Returns true if the folder already exists
    * or if it was created.
    *
    * @param String $path
    * @param int $mode
    * @return bool
    *
    **/
    public static function makeDir($path, $mode = 0755) {
        if(!file_exists($path) || !is_dir($path)) {
            if(!@mkdir($path, $mode, true)) {
                return false;
            }
        }

        return true;
    }

    /**
    * Converts markdown to HTML
    *
    * @param String $markdown
    * @return String
    *
    **/
    public static function markdownToHtml($markdown) {
        $pd = new \Parsedown();

        return $pd->text($markdown);
    }

    /**
    * Same as number_format(), but localizes decimal and thousands separators
    *
    * @param int $number
    * @param int $dec_places
    * @return String
    *
    **/
    public static function numberFormat($number, $dec_places = 0) {
        return number_format(
            $number,
            $dec_places,
            Language::term('decimal_separator'),
            Language::term('thousands_separator')
        );
    }

    /**
    * Dispatches an event
    *
    * @param String $eventName
    * @param \Symfony\Component\EventDispatcher\Event $event
    * @return void
    *
    **/
    public static function dispatchEvent($eventName, $event) {
        // We need this check because on install, the dispatcher won't be initiated!
        if (self::$dispatcher instanceof EventDispatcher){
            self::$dispatcher->dispatch($eventName, $event);
        }
    }

    /**
    * Removes one or more event listeners
    *
    * @param String $eventName
    * @param callable $listener
    * @return void
    *
    */
    public static function off($eventName, $listener) {
        self::$dispatcher->removeListener($eventName, $listener);
    }

    /**
    * Adds an event listener
    *
    * @param String $event
    * @param callable $callback
    * @param int $priority
    * @return void
    *
    */
    public static function on($event, $callback, $priority = 0) {
        self::$dispatcher->addListener($event, $callback, $priority);
    }

    /**
    * Checks if a listener exists
    *
    * @param String $event
    * @return bool
    *
    */
    public static function hasListener($event){
        return (self::$dispatcher->hasListeners($event) > 0);
    }

    /** TODO: Do we also need Subscriber functionality?
    public static function addSubscriber(EventSubscriberInterface $event){
        self::$dispatcher->addSubscriber($event);
    }
    **/
    /**
    * Generates an array of pagination data
    *
    * @param int $total_items
    * @param int $items_per_page
    * @param int $current_page
    * @return array
    *
    **/
    public static function paginate($total_items, $items_per_page = 10, $current_page = 1) {
        // Items per page must be at least one
        $items_per_page = (int) max(1, $items_per_page);

        // Calculate total pages
        $total_pages = (int) ceil($total_items / $items_per_page);

        // Current page must be at least one
        $current_page = (int) max(1, $current_page);

        // Determine previous/next pages
        $previous_page = (int) $current_page > 1 ? $current_page - 1 : null;
        $next_page = (int) $current_page < $total_pages ? $current_page + 1 : null;

        return [
            'current_page' => $current_page,
            'items_per_page' => $items_per_page,
            'next_page' => $next_page,
            'previous_page' => $previous_page,
            'total_items' => $total_items,
            'total_pages' => $total_pages
        ];
    }

    /**
    * Parses a string and outputs the corresponding date in YYYY-MM-DD HH:MM:SS format. If a valid
    * date/time can't be parsed, the current date/time will be used instead.
    *
    * @param String $date
    * @return String
    *
    **/
    public static function parseDate($date) {
        return date('Y-m-d H:i:s', strtotime($date) ?: time());
    }

    /**
    * Returns Leafpub's base path, optionally concatenating additional folders
    *
    * @return String
    *
    **/
    public static function path() {
        // Determine the base path that Leafpub runs from. This will be the same as the document
        // root unless Leafpub is running from a subfolder.
        $base_path = realpath(dirname(dirname(__DIR__)));

        // Grab arguments and prepend base path
        $args = func_get_args();
        array_unshift($args, $base_path);

        // Remove empties
        $args = array_filter($args, 'mb_strlen');

        // Glue them together
        $path = implode('/', $args);

        // Convert backslashes to forward slashes
        $path = str_replace('\\', '/', $path);

        // Remove duplicate slashes
        $path = preg_replace('/\/+/', '/', $path);

        return $path;
    }

    /**
    * Converts a path to a URL
    *
    * @param String $path
    * @return String
    *
    **/
    public static function pathToUrl($path) {
        // Get real path for comparison
        $base_path = self::path();
        $path = realpath($path);

        // Remove basepath from path
        if(mb_substr($path, 0, mb_strlen($base_path)) === $base_path) {
            $path = mb_substr($path, mb_strlen($base_path));
        }

        // Return a URL
        return self::url($path);
    }

    /**
    * Generates cryptographically secure pseudo-random bytes
    *
    * @param int $length
    * @return String
    *
    **/
    public static function randomBytes($length = 128) {
        if(function_exists('random_bytes')) {
            // PHP 7+
            $bytes = bin2hex(random_bytes($length));
        } else {
            // PHP < 7
            $bytes = bin2hex(openssl_random_pseudo_bytes($length));
        }

        return $bytes;
    }

    /**
    * Redirects the user to the specified URL and exits. Must be called before any output is
    * sent to the browser. If $permanent is true, a 301 HTTP code will be sent as well.
    *
    * @param String $url
    * @param bool $permanent
    * @return void
    *
    **/
    public static function redirect($url, $permanent = false) {
        if($permanent) header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $url);
        exit();
    }

    public static function scanDir($dir){
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        return $files;
    }

    /**
    * Recursively removes a directory and all its contents
    *
    * @param String $dir
    * @return bool
    *
    **/
    public static function removeDir($dir) {
        // Remove everything inside
        $files = self::scanDir($dir);
        foreach($files as $file) {
            if($file->isDir()) {
                if(!rmdir($file->getRealPath())) return false;
            } else {
                if(!unlink($file->getRealPath())) return false;
            }
        }

        // Remove the directory
        return rmdir($dir);
    }

    /**
    * Generates a web-safe filename by removing potentially problematic characters
    *
    * @param String $filename
    * @return String
    *
    **/
    public static function safeFilename($filename) {
        $invalid_chars = [
            '[', ']', '{', '}', '|', '<', '>', '/', '\\', '?', ':', ';', '\'', '"', ' ',
            '~', '`', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '+', '='
        ];

        // Replace invalid characters with dashes
        $filename = str_replace($invalid_chars, '-', $filename);

        // Remove duplicate dashes
        return preg_replace('/-+/', '-', $filename);
    }

    /**
    * Translates any string into a Leafpub slug. This function may return an empty string if no
    * valid characters are passed in.
    *
    * @param String $string
    * @return String
    *
    **/
    public static function slug($string) {
        // Convert spaces and underscores to dashes
		$string = preg_replace('/(\s|_)/', '-', $string);
		// Remove unsafe characters
		$string = preg_replace('/[^A-Z0-9-]/i', '', $string);
		// Remove duplicate dashes
		$string = preg_replace('/-+/', '-', $string);
		// Remove starting dashes
		$string = preg_replace('/^-+/', '', $string);
		// Remove trailing dashes
		$string = preg_replace('/-+$/', '', $string);

        // Make lowercase
		return mb_strtolower($string);
    }

    /**
    * This is a wrapper for PHP's strftime() function. We do this instead of setting the locale
    * because:
    *
    *  1. Not all locales are available on all systems, and it's easier to install a Leafpub
    *     language pack than additional PHP locales, and some users can't.
    *
    *  2. There are various parameters that aren't supported on all operating systems (e.g. %e and
    *     %P). This method normalizes those parameters so they work consistently on all systems.
    *
    * @param String $format
    * @param null $timestamp
    * @return String
    *
    **/
    public static function strftime($format, $timestamp = null) {
        // Default to current timestamp
        if(!$timestamp) $timestamp = time();

        // Get localized names
        $day_short = Language::term(mb_strtolower(date('D', $timestamp)) . '_short');
        $day_long = Language::term(mb_strtolower(date('l', $timestamp)));
        $month_short = Language::term(mb_strtolower(date('M', $timestamp)) . '_short');
        $month_long = Language::term(mb_strtolower(date('F', $timestamp)));
        $am_pm = Language::term(date('a', $timestamp));

        if ($format != 'time_ago'){
            // Convert them
            $format = str_replace('%a', $day_short, $format);
            $format = str_replace('%A', $day_long, $format);
            $format = str_replace('%b', $month_short, $format);
            $format = str_replace('%B', $month_long, $format);
            $format = str_replace('%p', mb_strtoupper($am_pm), $format);
            $format = str_replace('%P', mb_strtolower($am_pm), $format);

            // %e isn't supported on Windows
            $format = str_replace('%e', date('j', $timestamp), $format);

            // Run the rest through strftime()
            return strftime($format, $timestamp);
        } else {
            return self::getTimeAgo($timestamp);
        }
    }

    /**
    * Returns the subfolder that Leafpub is running from
    *
    * @return String
    *
    **/
    public static function subfolder() {
        return mb_substr(self::path(), mb_strlen(realpath($_SERVER['DOCUMENT_ROOT'])));
    }

    /**
    * Returns Leafpub's base URL, optionally concatenating additional folders
    *
    * @return String
    *
    **/
    public static function url() {
        // Determine protocol
        $protocol = self::isSsl() ? 'https' : 'http';

        // Get the hostname
        $hostname = $_SERVER['HTTP_HOST'];

        // Determine if Leafpub is running from a subfolder
        $subfolder = self::subfolder();

        // Get args and prepend subfolder
        $args = func_get_args();
        array_unshift($args, $subfolder);

        // Remove empties
        $args = array_filter($args, 'mb_strlen');

        // Glue them together
        $path = implode('/', $args);

        // Convert backslashes to forward slashes
        $path = str_replace('\\', '/', $path);

        // Remove duplicate slashes
        $path = preg_replace('/\/+/', '/', $path);

        // Remove preceding slash
        $path = ltrim($path, '/');

        // Generate the URL
        return "$protocol://$hostname/$path";
    }

    /**
    * Convert a UTC date string to local time
    *
    * @param String $utc_date
    * @return String
    *
    **/
    public static function utcToLocal($utc_date) {
        $dt = new \DateTime($utc_date, new \DateTimeZone('UTC'));
        $dt->setTimeZone(new \DateTimeZone(Setting::getOne('timezone')));
        return $dt->format('Y-m-d H:i:s');
    }

    public static function getTimeAgo($timestamp) {
        $estimate_time = time() - $timestamp;

        if( $estimate_time < 1 ){
            return 'less than 1 second ago';
        }

        $condition = [ 
                    12 * 30 * 24 * 60 * 60  =>  'year',
                    30 * 24 * 60 * 60       =>  'month',
                    24 * 60 * 60            =>  'day',
                    60 * 60                 =>  'hour',
                    60                      =>  'minute',
                    1                       =>  'second'
        ];

        foreach( $condition as $secs => $caption ){
            $duration = $estimate_time / $secs;

            if( $duration >= 1 ){
                $r = round( $duration );
                $caption = (( $r > 1) ? Language::term($caption . '_pl') : Language::term($caption) );
                return Language::term(
                        '{n}_{time}_{ago}',
                        [
                            'n' => $r,
                            'time' => $caption,
                            'ago' => Language::term('ago')
                        ]
                    );
            }
        }
    }

    /**
    * Returns an array of all known database table names
    *
    * @return array
    *
    **/
    public static function getTableNames() {
        return ['History', 'PostUploads', 'PostTags', 'Post', 'Setting', 'Tag', 'UploadTags', 'Upload', 'User', 'Plugin'];
    }
}