<?php
//
// Postleaf\Uploads: methods for working with uploads
//
namespace Postleaf;

class Upload extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Constants
    ////////////////////////////////////////////////////////////////////////////////////////////////

    const
        INVALID_IMAGE_FORMAT = 1,
        UNABLE_TO_CREATE_DIRECTORY = 2,
        UNABLE_TO_WRITE_FILE = 3,
        UNSUPPORTED_FILE_TYPE = 4;

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Private methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Normalize data types for certain fields
    private static function normalize($upload) {
        // Cast to integer
        $upload['id'] = (int) $upload['id'];
        $upload['size'] = (int) $upload['size'];
        $upload['width'] = (int) $upload['width'];
        $upload['height'] = (int) $upload['height'];

        // Convert dates from UTC to local
        $upload['created'] = self::utcToLocal($upload['created']);

        return $upload;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Adds an upload and returns its ID on success
    public static function add($filename, $file_data, &$info = null) {
        // Get allowed upload types
        $allowed_upload_types = explode(',', Setting::get('allowed_upload_types'));

        // Get year and month for target folder
        $year = date('Y');
        $month = date('m');

        // Make filename web-safe
        $filename = self::safeFilename($filename);

        // Get filename without extension
        $filename_without_extension = self::fileName($filename);

        // Get extension
        $extension = self::fileExtension($filename);

        // Check allowed upload types
        if(!in_array($extension, $allowed_upload_types)) {
            throw new \Exception(
                'Unsupported file type: ' . $filename,
                self::UNSUPPORTED_FILE_TYPE
            );
        }

        // Create uploads folder if it doesn't exist
        $target_dir = "content/uploads/$year/$month";
        if(!self::makeDir(self::path($target_dir))) {
            throw new \Exception(
                'Unable to create directory: ' . $target_dir,
                self::UNABLE_TO_CREATE_DIRECTORY
            );
        }

        // If a file with this name already exists, loop until we find a suffix that works
        $i = 1;
        while(file_exists(self::path($target_dir, $filename))) {
            $filename = $filename_without_extension . '-' . $i++ . '.' . $extension;
        }

        // Generate relative and full paths to the file
        $relative_path = "$target_dir/$filename";
        $full_path = self::path($relative_path);

        // Write it
        if(!file_put_contents($full_path, $file_data)) {
            throw new \Exception(
                'Unable to write file: ' . $filename,
                self::UNABLE_TO_WRITE_FILE
            );
        }

        // Get file size
        $size = (int) @filesize($full_path);

        // Get dimensions for supported image formats
        $width = $height = 0;
        if(in_array($extension, ['gif', 'jpg', 'jpeg', 'png', 'svg'])) {
            switch($extension) {
                case 'svg':
                    // Try to get default SVG dimensions
                    if(function_exists('simplexml_load_file')) {
                        $svg = simplexml_load_file($full_path);
                        $attributes = $svg->attributes();
                        $width = (int) $attributes->width;
                        $height = (int) $attributes->height;
                    }
                    break;

                default:
                    // Get image dimensions
                    $info = getimagesize($full_path);
                    if($info) {
                        $width = (int) $info[0];
                        $height = (int) $info[1];
                    } else {
                        throw new \Exception('Invalid image format', self::INVALID_IMAGE_FORMAT);
                    }
                    break;
            }
        }

        // Generate info to pass back
        $info = [
            'filename' => $filename,
            'extension' => $extension,
            'path' => $full_path,
            'relative_path' => $relative_path,
            'url' => parent::url($relative_path),
            'width' => $width,
            'height' => $height,
            'size' => $size
        ];

        try {
            // Create the upload
            $st = self::$database->prepare('
                INSERT INTO __uploads SET
                    path = :path,
                    created = NOW(),
                    filename = :filename,
                    extension = :extension,
                    size = :size,
                    width = :width,
                    height = :height
            ');
            $st->bindParam(':path', $relative_path);
            $st->bindParam(':filename', $filename);
            $st->bindParam(':extension', $extension);
            $st->bindParam(':size', $size, \PDO::PARAM_INT);
            $st->bindParam(':width', $width, \PDO::PARAM_INT);
            $st->bindParam(':height', $height, \PDO::PARAM_INT);
            $st->execute();
            $id = (int) self::$database->lastInsertId();
            return $id > 0 ? $id : false;
        } catch(\PDOException $e) {
            throw new \Exception('Database error: ' . $e->getMessage());
        }
    }

    // Deletes an upload
    public static function delete($id) {
        try {
            $st = self::$database->prepare('DELETE FROM __uploads WHERE id = :id');
            $st->bindParam(':id', $id);
            $st->execute();
            return $st->rowCount() > 0;
        } catch(\PDOException $e) {
            return false;
        }
    }

    // Gets a single upload. Returns an array on success, false if not found.
    public static function get($id) {
        try {
            $st = self::$database->prepare('
                SELECT id, path, created, filename, extension, size, width, height
                FROM __uploads
                WHERE id = :id
            ');
            $st->bindParam(':id', $id);
            $st->execute();
            $upload = $st->fetch(\PDO::FETCH_ASSOC);
            if(!$upload) return false;
        } catch(\PDOException $e) {
            return false;
        }

        // Normalize fields
        return self::normalize($upload);
    }

    // Gets multiple uploads. Returns an array of uploads on success, false if not found. If
    // $pagination is specified, it will be populated with pagination data generated by
    // Postleaf::paginate().
    public static function getMany($options = null, &$pagination = null) {
        // Merge options with defaults
        $options = array_merge([
            'query' => null,
            'page' => 1,
            'items_per_page' => 10
        ], (array) $options);

        // Generate select SQL
        $select_sql = '
            SELECT id, path, created, filename, extension, size, width, height
            FROM __uploads
        ';

        // Generate where SQL
        $where_sql = ' WHERE (filename LIKE :query OR extension LIKE :query)';

        // Generate order SQL
        $order_sql = ' ORDER BY created DESC';

        // Generate limit SQL
        $limit_sql = ' LIMIT :offset, :count';

        // Assemble count query to determine total matching uploads
        $count_sql = "SELECT COUNT(*) FROM __uploads $where_sql";

        // Assemble data query to fetch uploads
        $data_sql = "$select_sql $where_sql $order_sql $limit_sql";

        // Run the count query
        try {
            $query = '%' . Database::escapeLikeWildcards($options['query']) . '%';

            // Get count of all matching rows
            $st = self::$database->prepare($count_sql);
            $st->bindParam(':query', $query);
            $st->execute();
            $total_items = (int) $st->fetch()[0];
        } catch(\PDOException $e) {
            return false;
        }

        // Generate pagination
        $pagination = self::paginate(
            $total_items,
            $options['items_per_page'],
            $options['page']
        );

        $query = '%' . Database::escapeLikeWildcards($options['query']) . '%';
        $offset = ($pagination['current_page'] - 1) * $pagination['items_per_page'];
        $count = $pagination['items_per_page'];

        // Run the data query
        try {
            // Get matching rows
            $st = self::$database->prepare($data_sql);
            $st->bindParam(':query', $query);
            $st->bindParam(':offset', $offset, \PDO::PARAM_INT);
            $st->bindParam(':count', $count, \PDO::PARAM_INT);
            $st->execute();
            $uploads = $st->fetchAll(\PDO::FETCH_ASSOC);
        } catch(\PDOException $e) {
            return false;
        }

        // Normalize fields
        foreach($uploads as $key => $value) {
            $uploads[$key] = self::normalize($value);
        }

        return $uploads;
    }

    // Returns the maximum allowed upload size in bytes (per PHP's settings)
    public static function maxSize() {
		$ini = ini_get('upload_max_filesize');
		$size = intval($ini);
		$unit = preg_replace('/^[0-9]+/', '', $ini);

		switch(mb_strtoupper($unit)) {
			case 'G':
				$size *= 1000;
			case 'M':
				$size *= 1000;
			case 'K':
				$size *= 1000;
		}

		return (int) $size;
    }

}