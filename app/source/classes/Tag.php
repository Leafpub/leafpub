<?php
//
// Postleaf\Tag: methods for working with tags
//
namespace Postleaf;

class Tag extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Constants
    ////////////////////////////////////////////////////////////////////////////////////////////////

    const
        ALREADY_EXISTS = 1,
        INVALID_NAME = 2,
        INVALID_SLUG = 3,
        NOT_FOUND = 4;

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Private methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Normalize data types for certain fields
    private static function normalize($tag) {
        // Cast to integer
        $tag['id'] = (int) $tag['id'];

        // Convert dates from UTC to local
        $tag['created'] = self::utcToLocal($tag['created']);

        return $tag;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Adds a tag
    public static function add($slug, $properties) {
        // Enforce slug syntax
        $slug = self::slug($slug);

        // Is the slug valid?
        if(!mb_strlen($slug) || self::isProtectedSlug($slug)) {
            throw new \Exception('Invalid slug: ' . $slug, self::INVALID_SLUG);
        }

        // Does a tag already exist here?
        if(self::exists($slug)) {
            throw new \Exception('Tag already exists: ' . $slug, self::ALREADY_EXISTS);
        }

        // Must have a name
        if(!mb_strlen($properties['name'])) {
            throw new \Exception('No name specified', self::INVALID_NAME);
        }

        // Don't allow null properties
        $properties['description'] = (string) $properties['description'];
        $properties['cover'] = (string) $properties['cover'];
        $properties['meta_title'] = (string) $properties['meta_title'];
        $properties['meta_description'] = (string) $properties['meta_description'];

        try {
            // Create the tag
            $st = self::$database->prepare('
                INSERT INTO __tags SET
                    slug = :slug,
                    created = NOW(),
                    name = :name,
                    description = :description,
                    cover = :cover,
                    meta_title = :meta_title,
                    meta_description = :meta_description
            ');
            $st->bindParam(':slug', $slug);
            $st->bindParam(':name', $properties['name']);
            $st->bindParam(':description', $properties['description']);
            $st->bindParam(':cover', $properties['cover']);
            $st->bindParam(':meta_title', $properties['meta_title']);
            $st->bindParam(':meta_description', $properties['meta_description']);
            $st->execute();
            return $st->rowCount() > 0;
        } catch(\PDOException $e) {
            throw new \Exception('Database error: ' . $e->getMessage());
        }
    }

    // Returns the total number of tags that exist
    public static function count() {
        try {
            $st = self::$database->query('SELECT COUNT(*) FROM __tags');
            return (int) $st->fetch()[0];
        } catch(\PDOException $e) {
            return false;
        }
    }

    // Deletes a tag
    public static function delete($slug) {
        try {
            // Cleanup post_tags
            $st = self::$database->prepare('
                DELETE FROM __post_tags WHERE
                tag = (SELECT id FROM __tags WHERE slug = :slug)
            ');
            $st->bindParam(':slug', $slug);
            $st->execute();

            // Delete the tag
            $st = self::$database->prepare('DELETE FROM __tags WHERE slug = :slug');
            $st->bindParam(':slug', $slug);
            $st->execute();
            return $st->rowCount() > 0;
        } catch(\PDOException $e) {
            return false;
        }
    }

    // Tells whether a tag exists
    public static function exists($slug) {
        try {
            $st = self::$database->prepare('SELECT id FROM __tags WHERE slug = :slug');
            $st->bindParam(':slug', $slug);
            $st->execute();
            return !!$st->fetch();
        } catch(\PDOException $e) {
            return false;
        }
    }

    // Gets a single tag. Returns an array on success, false if not found.
    public static function get($slug) {
        try {
            $st = self::$database->prepare('
                SELECT id, created, slug, name, description, cover, meta_title, meta_description
                FROM __tags
                WHERE slug = :slug
            ');
            $st->bindParam(':slug', $slug);
            $st->execute();
            $tag = $st->fetch(\PDO::FETCH_ASSOC);
            if(!$tag) return false;
        } catch(\PDOException $e) {
            return false;
        }

        // Normalize fields
        return self::normalize($tag);
    }

    // Gets multiple tags. Returns an array of tags on success, false if not found. If $pagination
    // is specified, it will be populated with pagination data generated by Postleaf::paginate().
    public static function getMany($options = null, &$pagination = null) {
        // Merge options with defaults
        $options = array_merge([
            'query' => null,
            'page' => 1,
            'items_per_page' => 10
        ], (array) $options);

        // Generate select SQL
        $select_sql = '
            SELECT id, created, slug, name, description, cover, meta_title, meta_description
            FROM __tags
        ';

        // Generate where SQL
        $where_sql = '
            WHERE (
                slug LIKE :query OR
                name LIKE :query OR
                description LIKE :query OR
                meta_title LIKE :query OR
                meta_description LIKE :query
            )
        ';

        // Generate order SQL
        $order_sql = ' ORDER BY name';

        // Generate limit SQL
        $limit_sql = ' LIMIT :offset, :count';

        // Assemble count query to determine total matching tags
        $count_sql = "SELECT COUNT(*) FROM __tags $where_sql";

        // Assemble data query to fetch tags
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

        // Run the data query
        try {
            $query = '%' . Database::escapeLikeWildcards($options['query']) . '%';
            $offset = ($pagination['current_page'] - 1) * $pagination['items_per_page'];
            $count = $pagination['items_per_page'];

            // Get matching rows
            $st = self::$database->prepare($data_sql);
            $st->bindParam(':query', $query);
            $st->bindParam(':offset', $offset, \PDO::PARAM_INT);
            $st->bindParam(':count', $count, \PDO::PARAM_INT);
            $st->execute();
            $tags = $st->fetchAll(\PDO::FETCH_ASSOC);
        } catch(\PDOException $e) {
            return false;
        }

        // Normalize fields
        foreach($tags as $key => $value) {
            $tags[$key] = self::normalize($value);
        }

        return $tags;
    }

    // Returns an array of all tag names and corresponding slugs
    public static function getNames() {
        try {
            $st = self::$database->query('SELECT slug, name FROM __tags ORDER BY name');
            $tags = $st->fetchAll(\PDO::FETCH_ASSOC);
        } catch(\PDOException $e) {
            return false;
        }

        return $tags;
    }

    // Renders a tag page
    public static function render($slug, $page = 1) {
        // Get the tag
        $tag = self::get($slug);
        if(!$tag) return false;

        // Get the tag's posts
        $posts = Post::getMany([
            'tag' => $slug,
            'page' => $page,
            'items_per_page' => Setting::get('posts_per_page')
        ], $pagination);

        // Make sure the requested page exists
        if($page > $pagination['total_pages']) return false;

        // Add previous/next links to pagination
        $pagination['next_page_url'] = $pagination['next_page'] ?
            self::url($slug, $pagination['next_page']) : null;
        $pagination['previous_page_url'] = $pagination['previous_page'] ?
            self::url($slug, $pagination['previous_page']) : null;

        // Render it
        return Renderer::render([
            'template' => Theme::getPath('tag.hbs'),
            'data' => [
                'tag' => $tag,
                'posts' => $posts,
                'pagination' => $pagination
            ],
            'special_vars' => [
                'meta' => [
                    'title'=> !empty($tag['meta_title']) ? $tag['meta_title'] : $tag['name'],
                    'description' => !empty($tag['meta_description']) ? $tag['meta_description'] : $tag['description'],
                    // JSON linked data (schema.org)
                    'ld_json' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'Series',
                        'publisher' => Setting::get('title'),
                        'url' => self::url($tag['slug']),
                        'image' => empty($tag['cover']) ? null : parent::url($tag['cover']),
                        'name' => !empty($tag['meta_description']) ?
                            $tag['meta_title'] :
                            $tag['name'],
                        'description' => !empty($tag['meta_description']) ?
                            $tag['meta_description'] :
                            strip_tags(self::markdownToHtml($tag['description'])),
                    ],
                    // Open Graph
                    'open_graph' => [
                        'og:type' => 'website',
                        'og:site_name' => Setting::get('title'),
                        'og:title' => !empty($tag['meta_title']) ?
                            $tag['meta_title'] :
                            $tag['name'] . ' &middot; ' . Setting::get('title'),
                        'og:description' => !empty($tag['meta_description']) ?
                            $tag['meta_description'] : $tag['description'],
                        'og:url' => self::url($tag['slug']),
                        'og:image' => !empty($tag['cover']) ?
                            parent::url($tag['cover']) : null
                    ],
                    // Twitter Card
                    'twitter_card' => [
                        'twitter:card' => !empty($tag['cover']) ?
                            'summary_large_image' : 'summary',
                        'twitter:site' => !empty(Setting::get('twitter')) ?
                            '@' . Setting::get('twitter') : null,
                        'twitter:title' => !empty($tag['meta_title']) ?
                            $tag['meta_title'] :
                            $tag['name'] . ' &middot; ' . Setting::get('title'),
                        'twitter:description' => !empty($tag['meta_description']) ?
                            $tag['meta_description'] : $tag['description'],
                        'twitter:url' => self::url($tag['slug']),
                        'twitter:image' => !empty($tag['cover']) ?
                            parent::url($tag['cover']) : null
                    ]
                ]
            ],
            'helpers' => ['url', 'utility', 'theme']
        ]);
    }

    // Updates a tag
    public static function update($slug, $properties) {
        // Get the tag
        $tag = self::get($slug);
        if(!$tag) {
            throw new \Exception('Tag not found: ' . $slug, self::NOT_FOUND);
        }

        // Merge properties
        $tag = array_merge($tag, $properties);

        // Must have a name
        if(!mb_strlen($tag['name'])) {
            throw new \Exception('No name specified', self::INVALID_NAME);
        }

        // Don't allow null properties
        $tag['description'] = (string) $tag['description'];
        $tag['cover'] = (string) $tag['cover'];
        $tag['meta_title'] = (string) $tag['meta_title'];
        $tag['meta_description'] = (string) $tag['meta_description'];

        // Change the slug?
        if($tag['slug'] !== $slug) {
            // Enforce slug syntax
            $tag['slug'] = self::slug($tag['slug']);

            // Is the slug valid?
            if(!mb_strlen($tag['slug']) || self::isProtectedSlug($tag['slug'])) {
                throw new \Exception('Invalid slug: ' . $tag['slug'], self::INVALID_SLUG);
            }

            // Does a tag already exist with this slug?
            if(self::exists($tag['slug'])) {
                throw new \Exception('Tag already exists: ' . $tag['slug'], self::ALREADY_EXISTS);
            }
        }

        // Update the tag
        try {
            $st = self::$database->prepare('
                UPDATE __tags SET
                    slug = :slug,
                    name = :name,
                    description = :description,
                    cover = :cover,
                    meta_title = :meta_title,
                    meta_description = :meta_description
                WHERE slug = :original_slug
            ');
            $st->bindParam(':slug', $tag['slug']);
            $st->bindParam(':name', $tag['name']);
            $st->bindParam(':description', $tag['description']);
            $st->bindParam(':cover', $tag['cover']);
            $st->bindParam(':meta_title', $tag['meta_title']);
            $st->bindParam(':meta_description', $tag['meta_description']);
            $st->bindParam(':original_slug', $slug);
            $st->execute();
            return $st->rowCount() > 0;
        } catch(\PDOException $e) {
            return false;
        }
    }

    // Returns a tag URL
    public static function url($slug = '', $page = 1) {
        return $page > 1 ?
            // example.com/tag/name/page/2
            parent::url(
                Setting::get('frag_tag'),
                $slug,
                Setting::get('frag_page'),
                $page
            ) :
            // example.com/tag/name
            parent::url(Setting::get('frag_tag'), $slug);
    }

}