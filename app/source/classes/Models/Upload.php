<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2017 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Models;

use Leafpub\Leafpub,
    Leafpub\Theme,
    Leafpub\Renderer,
    Leafpub\Events\Upload\Add,
    Leafpub\Events\Upload\Added,
    Leafpub\Events\Upload\Update,
    Leafpub\Events\Upload\Updated,
    Leafpub\Events\Upload\Delete,
    Leafpub\Events\Upload\Deleted,
    Leafpub\Events\Upload\Retrieve,
    Leafpub\Events\Upload\Retrieved,
    Leafpub\Events\Upload\ManyRetrieve,
    Leafpub\Events\Upload\ManyRetrieved,
    Leafpub\Events\Upload\GenerateThumbnail;

class Upload implements ModelInterface {
    /**
    * Constants
    **/
    const
        INVALID_IMAGE_FORMAT = 1,
        UNABLE_TO_CREATE_DIRECTORY = 2,
        UNABLE_TO_WRITE_FILE = 3,
        UNSUPPORTED_FILE_TYPE = 4;

    protected static $_instance = '';

    public static function getModel(){
		if (self::$_instance == null){
			self::$_instance	=	new Tables\Upload();
		}
		return self::$_instance;
	}

    public static function getMany(array $options = [], &$pagination = null){
        // Merge options with defaults
        $options = array_merge([
            'tag' => null,
            'query' => null,
            'page' => 1,
            'items_per_page' => 10
        ], (array) $options);

        $evt = new ManyRetrieve($options);
        Leafpub::dispatchEvent(ManyRetrieve::NAME, $evt);
        $options = $evt->getEventData();

        $where = function($wh) use($options){
            $wh->nest->like('filename', '%' . $options['query'] . '%')
                ->or->like('extension', '%' . $options['query'] . '%')
                ->or->like('caption', '%' . $options['query'] . '%')
                ->unnest();

            if ($options['tag']){
                $prefix = Tables\TableGateway::$prefix;
                $wh->expression('
                    (
                        SELECT COUNT(*) from ' . $prefix . 'tags
                        LEFT JOIN ' . $prefix . 'upload_tags ON ' . $prefix . 'upload_tags.tag = ' . $prefix . 'tags.id
                        WHERE ' . $prefix . 'upload_tags.upload = ' . $prefix . 'uploads.id AND slug = :tag
                    ) = 1
                ', $options['tag']);
            }
        };

        // Assemble count query to determine total matching uploads
        $total_items = self::count(['where' => $where]);

        // Generate pagination
        $pagination = Leafpub::paginate(
            $total_items,
            $options['items_per_page'],
            $options['page']
        );

        $offset = ($pagination['current_page'] - 1) * $pagination['items_per_page'];
        $count = $pagination['items_per_page'];

        $model = self::getModel();
        $select = $model->getSql()->select();
        $select->columns(
            [
                'id',
                'caption',
                ['path' => new \Zend\Db\Sql\Expression('CONCAT_WS(\'.\', CONCAT(path, filename), extension)')],
                ['thumbnail' => new \Zend\Db\Sql\Expression('CONCAT_WS(\'.\', CONCAT(path, \'thumbnails/\' , filename), extension)')],
                'created',
                'filename',
                'extension',
                'size',
                'width',
                'height'
            ]
        );

        $select->where($where);
        $select->order('created DESC');
        $select->offset($offset);
        $select->limit($count);
$model->showSqlAndExit($select);
        // Run the data query
        try {
            $uploads = $model->selectWith($select)->toArray();
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

    public static function getOne($file){
        $evt = new Retrieve($file);
        Leafpub::dispatchEvent(Retrieve::NAME, $evt);

        try {
            $model = self::getModel();
            $select = $model->getSql()->select();
            $select->columns(
                [
                    'id',
                    'caption',
                    ['path' => new \Zend\Db\Sql\Expression('CONCAT_WS(\'.\', CONCAT(path, filename), extension) as path')],
                    ['thumbnail' => new \Zend\Db\Sql\Expression('CONCAT_WS(\'.\', CONCAT(path, \'thumbnails/\' , filename), extension)')],
                    'created',
                    'filename',
                    'extension',
                    'size',
                    'width',
                    'height'
                ]
            )
            ->where(['filename' => $file]);
            
            $upload = $model->selectWith($select)->current()->getArrayCopy();
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

    public static function create($data){
        list($filename, $file_data, $info) = $data;

        // Get allowed upload types
        $allowed_upload_types = explode(',', Setting::getOne('allowed_upload_types'));

        // Get year and month for target folder
        $year = date('Y');
        $month = date('m');

        // Make filename web-safe
        $filename = Leafpub::safeFilename($filename);
         // Get extension
        $extension = Leafpub::fileExtension($filename);
        // Get filename without extension
        $filename_without_extension = Leafpub::fileName($filename);

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
        $target_dir = "content/uploads/$year/$month/";
        if(!Leafpub::makeDir(Leafpub::path($target_dir))) {
            throw new \Exception(
                'Unable to create directory: ' . $target_dir,
                self::UNABLE_TO_CREATE_DIRECTORY
            );
        }

        // If a file with this name already exists, loop until we find a suffix that works
        $i = 1;
        while(file_exists(Leafpub::path($target_dir, $filename))) {
            $filename = $filename_without_extension . '-' . $i++ . '.' . $extension;
        }

        // Generate relative and full paths to the file
        $relative_path = "$target_dir$filename";
        $full_path = Leafpub::path($relative_path);

        // Write it
        if(!file_put_contents($full_path, $file_data)) {
            throw new \Exception(
                'Unable to write file: ' . $filename,
                self::UNABLE_TO_WRITE_FILE
            );
        }

        //$target_dir .= '/thumbnails';
        if(!Leafpub::makeDir(Leafpub::path($target_dir.'thumbnails'))) {
            throw new \Exception(
                'Unable to create directory: ' . $target_dir.'thumbnails',
                self::UNABLE_TO_CREATE_DIRECTORY
            );
        }
        $relative_thumb = "$target_dir"."thumbnails/$filename";
        $thumb_path = Leafpub::path($relative_thumb);
        
        // Generate thumbnails via event to give
        // developers the possibility to overwrite the thumbnail generation
        $evt = new GenerateThumbnail([
            'fullPath' => $full_path,
            'thumbPath' => $thumb_path
        ]);
        Leafpub::dispatchEvent(GenerateThumbnail::NAME, $evt);
        
        //self::generateThumbnail($full_path, $thumb_path);

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
            'filename' => Leafpub::fileName($filename), // We use filename as our slug
            'extension' => $extension,
            'path' => $full_path,
            'relative_path' => $relative_path,
            'url' => Leafpub::url($relative_path),
            'thumbnail_path' => $thumb_path,
            'relative_thumb' => $relative_thumb,
            'thumbnail' => Leafpub::url($relative_thumb),
            'width' => $width,
            'height' => $height,
            'size' => $size
        ];

        $evt = new Add($info);
        Leafpub::dispatchEvent(Add::NAME, $evt);

        try {
            $insert = [
                'path' => $target_dir,
                'created' => new \Zend\Db\Sql\Expression('NOW()'),
                'filename' => Leafpub::fileName($filename),
                'extension' => $extension,
                'size' => $size,
                'width' => $width,
                'height' => $height
            ];
            
            $model = self::getModel();
            $model->insert($insert);
            
            $id = (int) $model->lastInsertValue();
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

    public static function edit($data){
        $filename = $data['filename'];
        $evt = new Update($data);
        Leafpub::dispatchEvent(Update::NAME, $evt);
        $data = $evt->getEventData();
        unset($data['filename']);

        $dbData = self::getOne($filename);

        if (($dbData['width'] !== $data['width']) || ($dbData['height'] !== $data['height'])){
            // Create a new image
        }
        
        $tags = $data['tags'];

        try {
             self::getModel()->update($data, ['filename' => $filename]);
        } catch(\PDOException $e){
            throw new \Exception('Database error: ' . $e->getMessage());
        }

         // Set upload tags
        self::setTags($dbData['id'], $tags);

        $evt = new Updated($filename);
        Leafpub::dispatchEvent(Updated::NAME, $evt);

        return true;
    }

    public static function delete($data){
        $evt = new Delete($filename);
        Leafpub::dispatchEvent(Delete::NAME, $evt);
        
        $file = self::getOne($filename);
        unlink(self::path($file['path']));
        unlink(self::path($file['thumbnail']));
        
        try {
           $rowCount = self::getModel()->delete(['filename' => $filename]);
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
    * Returns the Upload id
    *
    * @param String $path
    * @return int
    *
    */
    public static function getImageId($path){
        if ($path == ''){
            return '';
        }
        $model = self::getModel();
        return (int) $model->selectWith(
                  $model->getSql()->select()
                                  ->where(
                                      new \Zend\Db\Sql\Expression('CONCAT_WS(\'.\', CONCAT(path, filename), extension) = ?', $path)
                                  )
                                  ->columns(['id'])
                  )->current()['id'];
    }

    public static function getUploadsToPost($postId){
        try {
            $table = new Tables\PostUploads();
            $select1 = $table->getSql()->select()
                                        ->columns(['upload'])
                                        ->where(function($wh) use($postId){
                                            $wh->equalTo('post', $postId);
                                        });

            $model = self::getModel();
            $select = self::getModel()->getSql()->select()
                                                ->columns(['slug'])
                                                ->where(function($wh) use($select1){
                                                    $wh->in('id', $select1);
                                                });
           
            return $model->selectWith($select)->toArray();
        } catch(\Exception $e){
            return false;
        }
    }

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
        $upload['created'] = Leafpub::utcToLocal($upload['created']);
        
        $upload['tags'] = self::getTags($upload['id']);
        $upload['posts'] = self::getPosts($upload['id']);

        return $upload;
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
    * Returns the total number of uploads that exist
    *
    * @param array $options
    * @return mixed
    *
    **/
    public static function count($options = null) {
        // Merge options
        $options = array_merge([
            'tag' => null,
            'width' => null,
            'height' => null
        ], (array) $options);


        if($options['tag']){
            $where = function($wh) use($options){
                $prefix = Tables\TableGateway::$prefix;
                $wh->expression('
                    (
                        SELECT COUNT(*) from ' . $prefix . 'tags
                        LEFT JOIN ' . $prefix . 'upload_tags ON ' . $prefix . 'upload_tags.tag = ' . $prefix . 'tags.id
                        WHERE ' . $prefix . 'upload_tags.upload = ' . $prefix . 'uploads.id AND slug = :tag
                    ) = 1
                ', $options['tag']);
            };
        } 

        if ($options['where']){
            $where = $options['where'];
        }

        try {
            $model = self::getModel();
            $select = $model->getSql()->select()->columns(['num' => new \Zend\Db\Sql\Expression('COUNT(*)')]);
            if ($where !== null){
                $select->where($where);
            }
            $ret =  $model->selectWith($select);
            return $ret->current()['num'];
        } catch(PDOException $e) {
            return false;
        }
    }

    public static function generateThumbnail($sourcePath, $thumbPath){
        // Create a thumbnail
        $image = new \claviska\SimpleImage($sourcePath);
        try{
            $image->thumbnail(400, 300)->toFile($thumbPath);
        } catch(\Exception $e){
            throw new \Exception(
                'Unable to create thumbnail: ' . $filename,
                self::UNABLE_TO_WRITE_FILE
            );
        }
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

    /**
    * Handles the generateThumbnail Event
    *
    * @param GenerateThumbnail $evt
    * @return void
    *
    */
    public static function handleThumbnail(GenerateThumbnail $evt){
        $path = $evt->getEventData();
        return self::generateThumbnail($path['fullPath'], $path['thumbPath']);
    }
}