<?php
//
// Postleaf\Postleaf: base class for the Postleaf API
//
namespace Postleaf;

class Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Properties
    ////////////////////////////////////////////////////////////////////////////////////////////////

    protected static $database, $language, $settings;

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Initialization method
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Initialize the app
    public static function run() {
        // Connect to the database
        try {
            Database::connect();
        } catch(\Exception $e) {
            switch($e->getCode()) {
                case Database::NOT_CONFIGURED:
                    // Database isn't configured, launch the installer
                    header('Location: ' . self::url('source/installer'));
                    exit();
                    break;
                default:
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
            Setting::load();
        } catch(\Exception $e) {
            exit(Error::system([
                'title' => 'Settings Error',
                'message' => $e->getMessage()
            ]));
        }

        // Load the language pack
        try {
            Language::load(Setting::get('language'));
        } catch(\Exception $e) {
            exit(Error::system([
                'title' => 'Translation Pack Error',
                'message' => $e->getMessage()
            ]));
        }

        // Set encoding
        mb_internal_encoding('UTF-8');

        // Set timezone
        date_default_timezone_set(Setting::get('timezone'));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Returns the file extension of $filename (lowercase, without a dot)
    public static function fileExtension($filename) {
        return mb_strtolower(pathinfo($filename)['extension']);
    }

    // Returns the filename without an extension
    public static function fileName($filename) {
        return pathinfo($filename)['filename'];
    }

    // Given a size in bytes, returns the most appropriate size as a string. Ex: 4.20k
    public static function formatBytes($size, $precision = 2) {
        $base = log($size, 1000);
        $suffixes = ['', 'kb', 'mb', 'gb', 'tb'];
        return round(pow(1000, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }

    // Returns no more than $num characters from $string, breaking at word
    public static function getChars($string, $num) {
        // Remove line breaks
        $string = preg_replace('/\r|\n/', ' ', $string);

        // Trim to max length and break at word
        $string = mb_substr($string, 0, $num);
        $string = mb_substr($string, 0, mb_strrpos($string, ' '));

        return $string;
    }

    // Returns no more than $num words from $string
    public static function getWords($string, $num) {
        // Remove line breaks
        $string = preg_replace('/\r|\n/', ' ', $string);

        // Explode and slice off extra words
        $string = explode(' ', $string);
        $string = array_slice($string, 0, $num);

        return implode(' ', $string);
    }

    // Returns true if the specified URL matches the current URL
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

    // Returns true if the specified URI is the homepage. Defaults to the current URI.
    public static function isHomepage($test_url = null) {
        // Default to the current URI
        if(!$test_url) $test_url = $_SERVER['REQUEST_URI'];

        return explode('?', $test_url)[0] === '/';
    }

    // Returns true if Postleaf has been installed
    public static function isInstalled() {
        // Simple check for database.php
        return file_exists(Postleaf::path('database.php'));
    }

    // Determines whether a slug is protected (i.e. used in settings.slugs)
    public static function isProtectedSlug($slug) {
        return in_array($slug, [
            'api',      // reserved for the API
            'postleaf', // reserved for future use
            Setting::get('frag_admin'),
            Setting::get('frag_author'),
            Setting::get('frag_blog'),
            Setting::get('frag_feed'),
            Setting::get('frag_page'),
            Setting::get('frag_search'),
            Setting::get('frag_tag')
        ]);
    }

    // Returns true if $email is a valid email address
    public static function isValidEmail($email) {
        return !!filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // Convert a local date string to UTC
    public static function localToUtc($local_date) {
        $dt = new \DateTime($local_date, new \DateTimeZone(Setting::get('timezone')));
        $dt->setTimeZone(new \DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
    }

    // Makes the specified directory if it doesn't exist. Returns true if the folder already exists
    // or if it was created.
    public static function makeDir($path, $mode = 0755) {
        if(!file_exists($path) || !is_dir($path)) {
            if(!@mkdir($path, $mode, true)) {
                return false;
            }
        }

        return true;
    }

    // Converts markdown to HTML
    public static function markdownToHtml($markdown) {
        $pd = new \Parsedown();

        return $pd->text($markdown);
    }

    // Same as number_format(), but localizes decimal and thousands separators
    public static function numberFormat($number, $dec_places = 0) {
        return number_format(
            $number,
            $dec_places,
            Language::term('decimal_separator'),
            Language::term('thousands_separator')
        );
    }

    // Generates an array of pagination data
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

    // Parses a date/time string. If an @ symbol is present, the date/time will be split and parsed
    // separately which makes the format more flexible. If the date/time can't be parsed, the
    // current time will be used instead. Outputs a date/time string in the specified format.
    public static function parseDate($date, $format = '%Y-%m-%d %H:%M:%S') {
        // Check for @
        if(mb_strstr($date, '@')) {
            // Parse date/time separately
            $date = explode('@', $date);

            // Parse date
            $date[0] = strtotime($date[0]);
            if(!$date[0]) $date[0] = time();

            // Parse time
            $date[1] = strtotime($date[1]);
            if(!$date[1]) $date[1] = time();

            // Get YYYY-MM-DD HH:MM:SS format
            $date = date('Y-m-d ', $date[0]) . date('H:i:s', $date[1]);

            // Convert to desired format
            return self::strftime($format, strtotime($date));
        } else {
            // Parse date/time as one string
            $parsed_date = strtotime($date);
            if(!$parsed_date) $parsed_date = time();
            // Convert to desired format
            return self::strftime($format, $parsed_date);
        }
    }

    // Returns Postleaf's base path, optionally concatenating additional folders
    public static function path() {
        // Determine the base path that Postleaf runs from. This will be the same as the document
        // root unless Postleaf is running from a subfolder.
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

    // Converts a path to a URL
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

    //
    // Generates cryptographically secure pseudo-random bytes
    //
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

    // Redirects the user to the specified URL and exits. Must be called before any output is
    // sent to the browser. If $permanent is true, a 301 HTTP code will be sent as well.
    public static function redirect($url, $permanent = false) {
        if($permanent) header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $url);
        exit();
    }

    // Recursively removes a directory and all its contents
    public static function removeDir($dir) {
        // Remove everything inside
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
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

    // Generates a web-safe filename by removing potentially problematic characters
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

    // Sends an email (currently a wrapper for mail(), but can be extended later)
    public static function sendEmail($options) {
        return mail(
            $options['to'],
            $options['subject'],
            $options['message'],
            'From: ' . $options['from']
        );
    }

    // Translates any string into a Postleaf slug. This function may return an empty string if no
    // valid characters are passed in.
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

    // This is a wrapper for PHP's strftime() function. We do this instead of setting the locale
    // because:
    //
    //  1. Not all locales are available on all systems, and it's easier to install a Postleaf
    //     language pack than additional PHP locales, and some users can't.
    //
    //  2. There are various parameters that aren't supported on all operating systems (e.g. %e and
    //     %P). This method normalizes those parameters so they work consistently on all systems.
    //
    public static function strftime($format, $timestamp = null) {
        // Default to current timestamp
        if(!$timestamp) $timestamp = time();

        // Get localized names
        $day_short = Language::term(mb_strtolower(date('D', $timestamp)) . '_short');
        $day_long = Language::term(mb_strtolower(date('l', $timestamp)));
        $month_short = Language::term(mb_strtolower(date('M', $timestamp)) . '_short');
        $month_long = Language::term(mb_strtolower(date('F', $timestamp)));
        $am_pm = Language::term(date('a', $timestamp));

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
    }

    // Returns the subfolder that Postleaf is running from
    public static function subfolder() {
        return mb_substr(self::path(), mb_strlen(realpath($_SERVER['DOCUMENT_ROOT'])));
    }

    // Returns Postleaf's base URL, optionally concatenating additional folders
    public static function url() {
        // Get protocol and hostname
        $protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';
        $hostname = $_SERVER['HTTP_HOST'];

        // Determine if Postleaf is running from a subfolder
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

    // Convert a UTC date string to local time
    public static function utcToLocal($utc_date) {
        $dt = new \DateTime($utc_date, new \DateTimeZone('UTC'));
        $dt->setTimeZone(new \DateTimeZone(Setting::get('timezone')));
        return $dt->format('Y-m-d H:i:s');
    }

}