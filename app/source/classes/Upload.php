<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use Leafpub\Events\Upload\Add,
    Leafpub\Events\Upload\Added,
    Leafpub\Events\Upload\Update,
    Leafpub\Events\Upload\Updated,
    Leafpub\Events\Upload\Delete,
    Leafpub\Events\Upload\Deleted,
    Leafpub\Events\Upload\Retrieve,
    Leafpub\Events\Upload\Retrieved,
    Leafpub\Events\Upload\ManyRetrieve,
    Leafpub\Events\Upload\ManyRetrieved;
    //Leafpub\Events\Upload\BeforeRender;

/**
* Upload
*
* methods for working with uploads
* @package Leafpub
*
**/
class Upload extends Leafpub {

    /**
    * Constants
    **/
    const
        INVALID_IMAGE_FORMAT = 1,
        UNABLE_TO_CREATE_DIRECTORY = 2,
        UNABLE_TO_WRITE_FILE = 3,
        UNSUPPORTED_FILE_TYPE = 4;

    /**
    * Normalize data types for certain fields
    *
    * @param array $upload
    * @return array
    *
    **/
    private static function normalize($upload) {
        // Cast to integer
        $upload['id'] = (int) $upload['id'];
        $upload['size'] = (int) $upload['size'];
        $upload['width'] = (int) $upload['width'];
        $upload['height'] = (int) $upload['height'];

        // Convert dates from UTC to local
        $upload['created'] = self::utcToLocal($upload['created']);
        
        $upload['tags'] = self::getTags($upload['id']);

        return $upload;
    }

    /**
    * Adds an upload and returns its ID on success
    *
    * @param String $filename
    * @param Stream $file_data
    * @param null &$info
    * @return mixed
    * @throws \Exception
    *
    **/
    public static function add($filename, $file_data, &$info = null) {
        // Get allowed upload types
        $allowed_upload_types = explode(',', Setting::get('allowed_upload_types'));

        // Get year and month for target folder
        $year = date('Y');
        $month = date('m');

        // Make filename web-safe
        $filename = self::safeFilename($filename);
         // Get extension
        $extension = self::fileExtension($filename);
        // Get filename without extension
        $filename_without_extension = self::fileName($filename);

        // See Issue #87
        if (mb_strlen($filename_without_extension) > 90){
            $filename_without_extension = mb_substr($filename_without_extension, 0, 90);
            $filename = $filename_without_extension . '.' . $extension;
        }

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

        $target_dir .= '/thumbnails';
        if(!self::makeDir(self::path($target_dir))) {
            throw new \Exception(
                'Unable to create directory: ' . $target_dir,
                self::UNABLE_TO_CREATE_DIRECTORY
            );
        }
        $relative_thumb = "$target_dir/$filename";
        $thumb_path = self::path($relative_thumb);
        // Create a thumbnail
        $image = new \claviska\SimpleImage($full_path);
        try{
            $image->thumbnail(400, 300)->toFile($thumb_path);
        } catch(\Exception $e){
            throw new \Exception(
                'Unable to create thumbnail: ' . $filename,
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
            'filename' => self::fileName($filename), // We use filename as our slug
            'extension' => $extension,
            'path' => $full_path,
            'relative_path' => $relative_path,
            'url' => parent::url($relative_path),
            'thumbnail_path' => $thumb_path,
            'relative_thumb' => $relative_thumb,
            'thumbnail' => parent::url($relative_thumb),
            'width' => $width,
            'height' => $height,
            'size' => $size
        ];

        $evt = new Add($info);
        Leafpub::dispatchEvent(Add::NAME, $evt);

        try {
            // Create the upload
            $st = self::$database->prepare('
                INSERT INTO __uploads SET
                    path = :path,
                    thumbnail = :thumb_path,
                    created = NOW(),
                    filename = :filename,
                    extension = :extension,
                    size = :size,
                    width = :width,
                    height = :height
            ');
            $st->bindParam(':path', $relative_path);
            $st->bindParam(':thumb_path', $relative_thumb);
            $st->bindParam(':filename', self::fileName($filename));
            $st->bindParam(':extension', $extension);
            $st->bindParam(':size', $size, \PDO::PARAM_INT);
            $st->bindParam(':width', $width, \PDO::PARAM_INT);
            $st->bindParam(':height', $height, \PDO::PARAM_INT);
            $st->execute();
            $id = (int) self::$database->lastInsertId();
            if ($id > 0){
                $evt = new Added($id);
                Leafpub::dispatchEvent(Added::NAME, $evt);

                return $id;
             } else {
                return false;
             }
        } catch(\PDOException $e) {
            throw new \Exception('Database error: ' . $e->getMessage());
        }
    }

    public static function edit($filename, $data){
        $evt = new Update(array_merge($data, array('filename' => $filename)));
        Leafpub::dispatchEvent(Update::NAME, $evt);
        $data = $evt->getEventData();
        unset($data['filename']);

        $dbData = self::get($filename);

        if (($dbData['width'] !== $data['width']) || ($dbData['height'] !== $data['height'])){
            // Create a new image
        }
        
        $tags = $data['tags'];

        try {
             $st = self::$database->prepare('
                UPDATE __uploads SET
                    caption = :caption,
                    width = :width,
                    height = :height
                WHERE filename = :filename;
            ');
            $st->bindParam(':caption', $data['caption']);
            $st->bindParam(':width', $data['width']);
            $st->bindParam(':height', $data['height']);
            $st->bindParam(':filename', $filename);
            $st->execute();
        } catch(\PDOException $e){
            throw new \Exception('Database error: ' . $e->getMessage());
        }

         // Set upload tags
        self::setTags($dbData['id'], $tags);

        $evt = new Updated($filename);
        Leafpub::dispatchEvent(Updated::NAME, $evt);

        return true;
    }

    /**
    * Deletes an upload
    *
    * @param int $id
    * @return bool
    *
    **/
    public static function delete($filename) {
        $evt = new Delete($filename);
        Leafpub::dispatchEvent(Delete::NAME, $evt);

        try {
            $st = self::$database->prepare('DELETE FROM __uploads WHERE filename = :filename');
            $st->bindParam(':filename', $filename);
            $st->execute();
            if ($st->rowCount() > 0){
                $evt = new Deleted($filename);
                Leafpub::dispatchEvent(Deleted::NAME, $evt);

                return true;
            } else {
                return false;
            }
        } catch(\PDOException $e) {
            return false;
        }
    }

    /**
    * Gets a single upload. Returns an array on success, false if not found.
    *
    * @param int $id
    * @return mixed
    *
    **/
    public static function get($file) {
        $evt = new Retrieve($file);
        Leafpub::dispatchEvent(Retrieve::NAME, $evt);

        try {
            $st = self::$database->prepare('
                SELECT id, caption, path, thumbnail, created, filename, extension, size, width, height
                FROM __uploads
                WHERE filename = :file
            ');
            $st->bindParam(':file', $file);
            $st->execute();
            $upload = $st->fetch(\PDO::FETCH_ASSOC);
            if(!$upload) return false;
        } catch(\PDOException $e) {
            return false;
        }

        // Normalize fields
        $upload = self::normalize($upload);

        $evt = new Retrieved($upload);
        Leafpub::dispatchEvent(Retrieved::NAME, $evt);
        
        return $upload; 
    }

    /**
    * Gets multiple uploads. Returns an array of uploads on success, false if not found. If
    * $pagination is specified, it will be populated with pagination data generated by
    * Leafpub::paginate().
    *
    * @param null $options
    * @param null &$pagination
    * @return mixed
    *
    **/
    public static function getMany($options = null, &$pagination = null) {
        // Merge options with defaults
        $options = array_merge([
            'query' => null,
            'page' => 1,
            'items_per_page' => 10
        ], (array) $options);

        $evt = new ManyRetrieve($options);
        Leafpub::dispatchEvent(ManyRetrieve::NAME, $evt);
        $options = $evt->getEventData();

        // Generate select SQL
        $select_sql = '
            SELECT id, caption, path, thumbnail, created, filename, extension, size, width, height
            FROM __uploads
        ';

        // Generate where SQL
        $where_sql = ' WHERE (filename LIKE :query OR extension LIKE :query OR caption LIKE :query)';

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

        $evt = new ManyRetrieved($uploads);
        Leafpub::dispatchEvent(ManyRetrieved::NAME, $evt);
        
        return $uploads;
    }

     /**
    * Gets the tags for the specified media file.
    *
    * @param int $upload_id
    * @return mixed
    *
    **/
    private static function getTags($upload_id) {
        try {
           // Get a list of slugs
           $st = self::$database->prepare('
               SELECT slug FROM __tags
               LEFT JOIN __upload_tags ON __upload_tags.tag = __tags.id
               WHERE __upload_tags.upload = :upload_id
               ORDER BY name
           ');
           $st->bindParam(':upload_id', $upload_id);
           $st->execute();
           return $st->fetchAll(\PDO::FETCH_COLUMN);
       } catch(\PDOException $e) {
           return false;
       }
    }

    /**
    * Sets the tags for the specified media file. To remove all tags, call this method with $tags = null.
    *
    * @param int $upload_id
    * @param null $tags
    * @return bool
    *
    **/
    private static function setTags($upload_id, $tags = null) {
        // Remove old tags
        try {
            $st = self::$database->prepare('DELETE FROM __upload_tags WHERE upload = :upload_id');
            $st->bindParam(':upload_id', $upload_id);
            $st->execute();
        } catch(\PDOException $e) {
            return false;
        }

        // Assign new tags
        if(count($tags)) {
            // Escape slugs
            foreach($tags as $key => $value) {
                $tags[$key] = self::$database->quote($value);
            }

            // Assign tags
            try {
                $st = self::$database->prepare('
                    INSERT INTO __upload_tags (upload, tag)
                    SELECT :upload_id, id FROM __tags
                    WHERE slug IN(' . implode(',', $tags) . ')
                ');
                $st->bindParam(':upload_id', $upload_id);
                $st->execute();
            } catch(\PDOException $e) {
                return false;
            }
        }

        return true;
    }

    /**
    * Returns the maximum allowed upload size in bytes (per PHP's settings)
    *
    * @return int
    *
    **/
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