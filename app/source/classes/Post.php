<?php
//
// Postleaf\Post: methods for working with posts
//
namespace Postleaf;

class Post extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Constants
    ////////////////////////////////////////////////////////////////////////////////////////////////

    const
        ALREADY_EXISTS = 1,
        INVALID_SLUG = 2,
        INVALID_USER = 3,
        NOT_FOUND = 4;

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Private methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Gets the tags for the specified post.
    private static function getTags($post_id) {
        try {
           // Get a list of slugs
           $st = self::$database->prepare('
               SELECT slug FROM __tags
               LEFT JOIN __post_tags ON __post_tags.tag = __tags.id
               WHERE __post_tags.post = :post_id
               ORDER BY name
           ');
           $st->bindParam(':post_id', $post_id);
           $st->execute();
           return $st->fetchAll(\PDO::FETCH_COLUMN);
       } catch(\PDOException $e) {
           return false;
       }
    }

    // Normalize data types for certain fields
    private static function normalize($post) {
        // Cast to integer
        $post['id'] = (int) $post['id'];
        $post['page'] = (int) $post['page'];
        $post['featured'] = (int) $post['featured'];
        $post['sticky'] = (int) $post['sticky'];

        // Convert dates from UTC to local
        $post['created'] = self::utcToLocal($post['created']);
        $post['pub_date'] = self::utcToLocal($post['pub_date']);

        // Append tags
        $post['tags'] = self::getTags($post['id']);

        return $post;
    }

    // Sets the tags for the specified post. To remove all tags, call this method with $tags = null.
    private static function setTags($post_id, $tags = null) {
        // Remove old tags
        try {
            $st = self::$database->prepare('DELETE FROM __post_tags WHERE post = :post_id');
            $st->bindParam(':post_id', $post_id);
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
                    INSERT INTO __post_tags (post, tag)
                    SELECT :post_id, id FROM __tags
                    WHERE slug IN(' . implode(',', $tags) . ')
                ');
                $st->bindParam(':post_id', $post_id);
                $st->execute();
            } catch(\PDOException $e) {
                return false;
            }
        }

        return true;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Adds a post
    public static function add($slug, $properties) {
        // Enforce slug syntax
        $slug = self::slug($slug);

        // Is the slug valid?
        if(!mb_strlen($slug) || self::isProtectedSlug($slug)) {
            throw new \Exception('Invalid slug: ' . $slug, self::INVALID_SLUG);
        }

        // Does a post already exist here?
        if(self::exists($slug)) {
            throw new \Exception('Post already exists: ' . $slug, self::ALREADY_EXISTS);
        }

        // Parse publish date format and convert to UTC
        $properties['pub_date'] = self::localToUtc(self::parseDate($properties['pub_date']));

        // Translate author slug to ID
        $properties['author'] = User::getId($properties['author']);
        if(!$properties['author']) {
            throw new \Exception('Invalid user.', self::INVALID_USER);
        }

        // Empty title defaults to settings.default_title
        if(empty($properties['title'])) {
            $properties['title'] = Setting::get('default_title');
        }

        // Empty content defaults to settings.default_content
        if(empty($properties['content'])) {
            $properties['content'] = Setting::get('default_content');
        }

        // Don't allow null properties
        $properties['image'] = (string) $properties['image'];
        $properties['meta_title'] = (string) $properties['meta_title'];
        $properties['meta_description'] = (string) $properties['meta_description'];

        // Status must be `published` or `draft`
        if($properties['status'] !== 'draft') $properties['status'] = 'published';

        // Page, featured, and sticky must be 1 or 0
        foreach(['page', 'featured', 'sticky'] as $key) {
            $properties[$key] = filter_var($properties[$key], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        try {
            // Create the post
            $st = self::$database->prepare('
                INSERT INTO __posts SET
                    slug = :slug,
                    created = NOW(),
                    pub_date = :pub_date,
                    author = :author,
                    title = :title,
                    content = :content,
                    image = :image,
                    meta_title = :meta_title,
                    meta_description = :meta_description,
                    status = :status,
                    page = :page,
                    featured = :featured,
                    sticky = :sticky
            ');
            $st->bindParam(':slug', $slug);
            $st->bindParam(':pub_date', $properties['pub_date']);
            $st->bindParam(':author', $properties['author']);
            $st->bindParam(':title', $properties['title']);
            $st->bindParam(':content', $properties['content']);
            $st->bindParam(':image', $properties['image']);
            $st->bindParam(':meta_title', $properties['meta_title']);
            $st->bindParam(':meta_description', $properties['meta_description']);
            $st->bindParam(':status', $properties['status']);
            $st->bindParam(':page', $properties['page']);
            $st->bindParam(':featured', $properties['featured']);
            $st->bindParam(':sticky', $properties['sticky']);
            $st->execute();
            $post_id = (int) self::$database->lastInsertId();
            if($post_id <= 0) return false;
        } catch(\PDOException $e) {
            throw new \Exception('Database error: ' . $e->getMessage());
        }

        // Set post tags
        self::setTags($post_id, $properties['tags']);

        // Create the initial revision
        History::add($slug, true);

        return $post_id;
    }

    // Returns the total number of posts that exist
    public static function count($options = null) {
        // Merge options
        $options = array_merge([
            'author' => null,
            'end_date' => date('Y-m-d H:i:s'),
            'status' => 'published',
            'ignore_featured' => false,
            'ignore_sticky' => false,
            'ignore_pages' => true,
            'start_date' => null,
            'tag' => null
        ], (array) $options);

        // Convert dates to UTC
        if($options['start_date']) $start_date = self::localToUtc($options['start_date']);
        if($options['end_date']) $end_date = self::localToUtc($options['end_date']);

        // Build count query
        $sql = 'SELECT COUNT(*) FROM __posts WHERE 1 = 1';

        // Add options to query
        if($options['author']) $sql .= '
            AND author = (SELECT id FROM __users WHERE slug = :author)
        ';
        if($options['tag']) $sql .= '
            AND (
                SELECT COUNT(*) from __tags
                LEFT JOIN __post_tags ON __post_tags.tag = __tags.id
                WHERE __post_tags.post = __posts.id AND slug = :tag
            ) = 1
        ';
        if($options['status']) {
            $sql .= ' AND FIND_IN_SET(status, :status) > 0';
            $status = implode(',', (array) $options['status']);
        }
        if($options['ignore_featured']) $sql .= ' AND featured != 1';
        if($options['ignore_sticky']) $sql .= ' AND sticky != 1';
        if($options['ignore_pages']) $sql .= ' AND page != 1';
        if($options['start_date']) $sql .= ' AND pub_date >= :start_date';
        if($options['end_date']) $sql .= ' AND pub_date <= :end_date';

        // Fetch results
        try {
            $st = self::$database->prepare($sql);
            if($options['start_date']) $st->bindParam(':start_date', $start_date);
            if($options['end_date']) $st->bindParam(':end_date', $end_date);
            if($options['author']) $st->bindParam(':author', $options['author']);
            if($options['tag']) $st->bindParam(':tag', $options['tag']);
            if($options['status']) $st->bindParam(':status', $status);
            $st->execute();
            return (int) $st->fetch()[0];
        } catch(\PDOException $e) {
            return false;
        }
    }

    // Deletes a post
    public static function delete($slug) {
        // If this post is the custom homepage, update settings
        if($slug === Setting::get('homepage')) {
            Setting::update('homepage', '');
        }

        // Delete the post
        try {
            // Cleanup history
            History::flush($slug);

            // Cleanup post_tags
            $st = self::$database->prepare('
                DELETE FROM __post_tags WHERE
                post = (SELECT id FROM __posts WHERE slug = :slug)
            ');
            $st->bindParam(':slug', $slug);
            $st->execute();

            // Delete the post
            $st = self::$database->prepare('DELETE FROM __posts WHERE slug = :slug');
            $st->bindParam(':slug', $slug);
            $st->execute();
            return $st->rowCount() > 0;
        } catch(\PDOException $e) {
            return false;
        }
    }

    // Tells whether a post exists
    public static function exists($slug) {
        try {
            $st = self::$database->prepare('SELECT id FROM __posts WHERE slug = :slug');
            $st->bindParam(':slug', $slug);
            $st->execute();
            return !!$st->fetch();
        } catch(\PDOException $e) {
            return false;
        }
    }

    // Gets a single post. Returns an array on success, false if not found.
    public static function get($slug) {
        try {
            $st = self::$database->prepare('
                SELECT
                    id, slug, created, pub_date,
                    (SELECT slug FROM __users WHERE id = __posts.author) AS author,
                    title, content, image, meta_title, meta_description, status, page,
                    featured, sticky
                FROM __posts
                WHERE slug = :slug
            ');
            $st->bindParam(':slug', $slug);
            $st->execute();
            $post = $st->fetch(\PDO::FETCH_ASSOC);
            if(!$post) return false;
        } catch(\PDOException $e) {
            return false;
        }

        // Normalize fields
        $post = self::normalize($post);

        return $post;
    }

    // Gets one public post immediately before or after the target post
    public static function getAdjacent($slug, $options = null) {
        // Merge options
        $options = array_merge([
            'author' => null,
            'direction' => 'next',
            'end_date' => date('Y-m-d H:i:s'),
            'status' => 'published',
            'ignore_featured' => false,
            'ignore_sticky' => false,
            'ignore_pages' => true,
            'start_date' => null,
            'tag' => null
        ], (array) $options);

        // Convert dates to UTC
        if($options['start_date']) $start_date = self::localToUtc($options['start_date']);
        if($options['end_date']) $end_date = self::localToUtc($options['end_date']);

        // Build query
        $sql = '
            SELECT
                id, slug, pub_date AS date,
                (SELECT slug FROM __users WHERE id = __posts.author) AS author,
                title, content, image, meta_title, meta_description, status, page,
                featured, sticky
            FROM __posts
            WHERE 1 = 1
        ';

        if($options['author']) $sql .= '
            AND author = (SELECT id FROM __users WHERE slug = :author)
        ';
        if($options['tag']) $sql .= '
            AND (
                SELECT COUNT(*) from __tags
                LEFT JOIN __post_tags ON __post_tags.tag = tags.id
                WHERE __post_tags.post = __posts.id AND slug = :tag
            ) = 1
        ';
        if($options['status']) {
            $sql .= ' AND FIND_IN_SET(status, :status) > 0';
            $status = implode(',', (array) $options['status']);
        }
        if($options['ignore_featured']) $sql .= ' AND featured != 1';
        if($options['ignore_sticky']) $sql .= ' AND sticky != 1';
        if($options['ignore_pages']) $sql .= ' AND page != 1';
        if($options['start_date']) $sql .= ' AND pub_date >= :start_date';
        if($options['end_date']) $sql .= ' AND pub_date <= :end_date';

        // Determine direction
        $sort = $options['direction'] === 'next' ? 'ASC' : 'DESC';
        $compare = $options['direction'] === 'next' ? '>=' : '<=';

        $sql .= '
            AND slug != :slug
            AND CONCAT(pub_date, id) ' . $compare . ' (
                SELECT CONCAT(pub_date, id)
                FROM __posts
                WHERE slug = :slug
            )
            ORDER BY pub_date ' . $sort . '
            LIMIT 1
        ';

        try {
            $st = self::$database->prepare($sql);
            $st->bindParam(':slug', $slug);
            if($options['author']) $st->bindParam(':author', $options['author']);
            if($options['tag']) $st->bindParam(':tag', $options['tag']);
            if($options['status']) $st->bindParam(':status', $status);
            if($options['start_date']) $st->bindParam(':start_date', $start_date);
            if($options['end_date']) $st->bindParam(':end_date', $end_date);
            $st->execute();
            $post = $st->fetch(\PDO::FETCH_ASSOC);
            if(!$post) return false;
        } catch(\PDOException $e) {
            return false;
        }

        // Normalize fields
        $post = self::normalize($post);

        return $post;
    }

    // Gets multiple posts. Returns an array of posts on success, false if not found. If $pagination
    // is specified, it will be populated with pagination data generated by Postleaf::paginate().
    //
    // If a query is specified, this method will perform a full text search with basic scoring, very
    // similar to the solution recommended here: http://stackoverflow.com/a/600915/567486
    public static function getMany($options = null, &$pagination = null) {
        // Merge options with defaults
        $options = array_merge([
            'author' => null,
            'end_date' => date('Y-m-d H:i:s'),
            'status' => 'published',
            'ignore_featured' => false,
            'ignore_sticky' => false,
            'ignore_pages' => true,
            'ignore_posts' => false,
            'items_per_page' => 10,
            'page' => 1,
            'query' => null,
            'start_date' => null,
            'tag' => null
        ], (array) $options);

        // Convert dates to UTC
        if($options['start_date']) $start_date = self::localToUtc($options['start_date']);
        if($options['end_date']) $end_date = self::localToUtc($options['end_date']);

        // If there's a query of > 3 chars, make it a fulltext search
        $is_fulltext = mb_strlen($options['query']) > 3;

        // Generate select SQL
        $select_sql = '
            SELECT
                id, slug, created, pub_date,
                (SELECT slug FROM __users WHERE id = __posts.author) AS author,
                title, content, image, meta_title, meta_description, status, page,
                featured, sticky
        ';
        if($is_fulltext) {
            $select_sql .= ',
                MATCH(slug, title) AGAINST (:query) AS title_score,
                MATCH(content) AGAINST (:query) AS content_score
            ';
        }
        $select_sql .= ' FROM __posts';

        // Generate where SQL
        $where_sql = ' WHERE 1 = 1';
        if($is_fulltext) {
            // Fulltext search
            $where_sql .= ' AND MATCH(slug, title, content) AGAINST(:query)';
            $query = $options['query'];
        } else {
            // Standard search (only match slug and title for more relevant results)
            $where_sql .= ' AND CONCAT(slug, title) LIKE :query';
            $query = '%' . Database::escapeLikeWildcards($options['query']) . '%';
        }
        if($options['status']) {
            $where_sql .= ' AND FIND_IN_SET(status, :status) > 0';
            $status = implode(',', (array) $options['status']);
        }
        if($options['ignore_featured']) $where_sql .= ' AND featured != 1';
        if($options['ignore_sticky']) $where_sql .= ' AND sticky != 1';
        if($options['ignore_posts']) $where_sql .= ' AND page = 1';
        if($options['ignore_pages']) $where_sql .= ' AND page != 1';
        if($options['start_date']) $where_sql .= ' AND pub_date >= :start_date';
        if($options['end_date']) $where_sql .= ' AND pub_date <= :end_date';
        if($options['author']) $where_sql .= ' AND author = (SELECT id FROM __users WHERE slug = :author)';
        if($options['tag']) $where_sql .= '
            AND (
                SELECT COUNT(*) from __tags
                LEFT JOIN __post_tags ON __post_tags.tag = __tags.id
                WHERE __post_tags.post = __posts.id AND slug = :tag
            ) = 1
        ';

        // Generate order SQL
        if($is_fulltext) {
            $order_sql = ' ORDER BY (title_score * 1.5 + content_score) DESC';
        } else {
            $order_sql = ' ORDER BY sticky DESC, pub_date DESC, id DESC';
        }

        // Generate limit SQL
        $limit_sql = ' LIMIT :offset, :count';

        // Assemble count query to determine total matching posts
        $count_sql = "SELECT COUNT(*) FROM __posts $where_sql";

        // Assemble data query to fetch posts
        $data_sql = "$select_sql $where_sql $order_sql $limit_sql";

        // Run the count query
        try {
            // Get count of all matching rows
            $st = self::$database->prepare($count_sql);
            $st->bindParam(':query', $query);
            if($options['status']) $st->bindParam(':status', $status);
            if($options['start_date']) $st->bindParam(':start_date', $start_date);
            if($options['end_date']) $st->bindParam(':end_date', $end_date);
            if($options['author']) $st->bindParam(':author', $options['author']);
            if($options['tag']) $st->bindParam(':tag', $options['tag']);
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
        $offset = ($pagination['current_page'] - 1) * $pagination['items_per_page'];
        $count = $pagination['items_per_page'];

        // Run the data query
        try {
            // Get matching rows
            $st = self::$database->prepare($data_sql);
            $st->bindParam(':offset', $offset, \PDO::PARAM_INT);
            $st->bindParam(':count', $count, \PDO::PARAM_INT);
            $st->bindParam(':query', $query);
            if($options['status']) $st->bindParam(':status', $status);
            if($options['start_date']) $st->bindParam(':start_date', $start_date);
            if($options['end_date']) $st->bindParam(':end_date', $end_date);
            if($options['author']) $st->bindParam(':author', $options['author']);
            if($options['tag']) $st->bindParam(':tag', $options['tag']);
            $st->execute();
            $posts = $st->fetchAll(\PDO::FETCH_ASSOC);
        } catch(\PDOException $e) {
            return false;
        }

        // Normalize fields
        foreach($posts as $key => $value) {
            $posts[$key] = self::normalize($value);
        }

        return $posts;
    }

    // Gets suggested posts for the target post
    public static function getSuggested($slug, $options = null) {
        // Merge options
        $options = array_merge([
            'author' => null,
            'end_date' => date('Y-m-d H:i:s'),
            'status' => 'published',
            'ignore_featured' => false,
            'ignore_sticky' => false,
            'ignore_pages' => true,
            'max' => 5,
            'start_date' => null,
            'tag' => null
        ], (array) $options);

        // Convert dates to UTC
        if($options['start_date']) $start_date = self::localToUtc($options['start_date']);
        if($options['end_date']) $end_date = self::localToUtc($options['end_date']);

        // If there's a query of > 3 chars, make it a fulltext search
        $is_fulltext = mb_strlen($options['query']) > 3;

        // Build query
        $sql = '
            SELECT
                __posts.id,
                __posts.slug,
                __posts.pub_date,
                (SELECT slug FROM __users WHERE id = __posts.author) AS author,
                __posts.title,
                __posts.content,
                __posts.image,
                __posts.meta_title,
                __posts.meta_description,
                __posts.status,
                __posts.page,
                __posts.featured,
                __posts.sticky
            FROM __posts
            LEFT JOIN __users
                ON __users.id = __posts.author
            LEFT JOIN __post_tags
                ON __post_tags.post = __posts.id
            WHERE __posts.slug != :slug
        ';

        if($options['author']) $sql .= '
            AND author = (SELECT id FROM __users WHERE slug = :author)
        ';
        if($options['tag']) $sql .= '
            AND (
                SELECT COUNT(*) from __tags
                LEFT JOIN __post_tags ON __post_tags.tag = __tags.id
                WHERE __post_tags.post = __posts.id AND slug = :tag
            ) = 1
        ';
        if($options['status']) {
            $sql .= ' AND FIND_IN_SET(__posts.status, :status) > 0';
            $status = implode(',', (array) $options['status']);
        }
        if($options['ignore_featured']) $sql .= ' AND featured != 1';
        if($options['ignore_sticky']) $sql .= ' AND sticky != 1';
        if($options['ignore_pages']) $sql .= ' AND page != 1';
        if($options['start_date']) $sql .= ' AND __posts.pub_date >= :start_date';
        if($options['end_date']) $sql .= ' AND __posts.pub_date <= :end_date';

        $sql .= '
            AND __post_tags.tag IN(
                SELECT __post_tags.tag
                FROM __post_tags
                LEFT JOIN __posts
                    __posts ON __post_tags.post = __posts.id
                WHERE __posts.slug = :slug
            )
            GROUP BY __posts.id
            ORDER BY __posts.pub_date DESC
            LIMIT :max
        ';

        // Get matching posts
        try {
            $st = self::$database->prepare($sql);
            $st->bindParam(':slug', $slug);
            $st->bindParam(':max', $options['max'], \PDO::PARAM_INT);
            if($options['author']) $st->bindParam(':author', $options['author']);
            if($options['tag']) $st->bindParam(':tag', $options['tag']);
            if($options['status']) $st->bindParam(':status', $status);
            if($options['start_date']) $st->bindParam(':start_date', $start_date);
            if($options['end_date']) $st->bindParam(':end_date', $end_date);
            $st->execute();
            $posts = $st->fetchAll(\PDO::FETCH_ASSOC);
            if(!$posts) return false;
        } catch(\PDOException $e) {
            return false;
        }

        // Normalize fields
        foreach($posts as $key => $value) {
            $posts[$key] = self::normalize($value);
        }

        return $posts;
    }

    // Tells whether or not a post is e to the public
    public static function isVisible($post_or_slug) {
        // Get the post
        $post = is_string($post_or_slug) ? Post::get($post_or_slug) : $post_or_slug;
        if(!$post) return false;

        // Make sure pub date is a valid date format
        $post['pub_date'] = self::parseDate($post['pub_date']);
        $pub_date = new \DateTime($post['pub_date']);
        $pub_date->setTimeZone(new \DateTimeZone('UTC'));

        // Is it in the future?
        $now = new \DateTime('now');
        $now->setTimeZone(new \DateTimeZone('UTC'));
        if($pub_date > $now) return false;

        // Is is published?
        if($post['status'] !== 'published') return false;

        return true;
    }

    // Renders a post
    public static function render($slug_or_post, $options = null) {
        // Get the post
        if(is_array($slug_or_post)) {
            $post = $slug_or_post;
        } else {
            $post = self::get($slug_or_post);
            if(!$post) return false;
        }

        // Get the author
        $author = User::get($post['author']);

        // Make sure pub date is a valid date format
        $post['pub_date'] = self::parseDate($post['pub_date']);

        // Only render if it's visible to the public or a preview
        if(!Post::isVisible($post) && !$options['preview']) return false;

        // Determine which template to use
        if($options['zen']) {
            $template = self::path('source/templates/editor.zen.hbs');
        } else {
            $template = Theme::getPath($post['page'] ? 'page.hbs' : 'post.hbs');
        }

        // Render it
        $html = Renderer::render([
            'template' => $template,
            'data' => [
                'post' => $post
            ],
            'special_vars' => [
                'meta' => [
                    'editable' => !!$options['editable'],
                    'preview' => !!$options['preview'],
                    'title'=> !empty($post['meta_title']) ? $post['meta_title'] : $post['title'],
                    'description' => !empty($post['meta_description']) ?
                        $post['meta_description'] :
                        self::getChars(strip_tags($post['content']), 160),
                    // JSON linked data (schema.org)
                    'ld_json' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'Article',
                        'publisher' => [
                            '@type' => 'Organization',
                            'name' => Setting::get('title'),
                            'logo' => !empty(Setting::get('logo')) ?
                                parent::url(Setting::get('logo')) : null
                        ],
                        'author' => [
                            '@type' => 'Person',
                            'name' => $author['name'],
                            'description' => strip_tags(self::markdownToHtml($author['bio'])),
                            'image' => !empty($author['avatar']) ?
                                parent::url($author['avatar']) : null,
                            'sameAs' => !empty($author['website']) ?
                                [$author['website']] : null,
                        ],
                        'url' => Post::url($post['slug']),
                        'headline' => !empty($post['meta_title']) ?
                            $post['meta_title'] :
                            $post['title'],
                        'description' => !empty($post['meta_description']) ?
                            $post['meta_description'] :
                            self::getWords(strip_tags($post['content']), 50),
                        'image' => empty($post['image']) ? null : parent::url($post['image']),
                        'datePublished' => self::strftime('%FT%TZ', strtotime($post['pub_date'])),
                        'dateModified' => self::strftime('%FT%TZ', strtotime($post['pub_date']))
                    ],
                    // Open Graph
                    'open_graph' => [
                        'og:type' => 'article',
                        'og:site_name' => Setting::get('title'),
                        'og:title' => !empty($post['meta_title']) ?
                            $post['meta_title'] :
                            $post['title'],
                        'og:description' => !empty($post['meta_description']) ?
                            $post['meta_description'] :
                            self::getWords(strip_tags($post['content']), 50),
                        'og:url' => self::url($post['slug']),
                        'og:image' => empty($post['image']) ? '' : parent::url($post['image']),
                        'article:published_time' => $post['page'] ?
                            null : self::strftime('%FT%TZ', strtotime($post['pub_date'])),
                        'article:modified_time' => $post['page'] ?
                            null : self::strftime('%FT%TZ', strtotime($post['pub_date'])),
                        'article:tag' => $post['page'] ?
                            null : implode(', ', (array) $post['tags'])
                    ],
                    // Twitter Card
                    'twitter_card' => [
                        'twitter:card' => !empty($post['image']) ?
                            'summary_large_image' :
                            'summary',
                        'twitter:site' => !empty(Setting::get('twitter')) ?
                            '@' . Setting::get('twitter') : null,
                        'twitter:title' => !empty($post['meta_title']) ?
                            $post['meta_title'] :
                            $post['title'],
                        'twitter:description' => !empty($post['meta_description']) ?
                            $post['meta_description'] :
                            self::getWords(strip_tags($post['content']), 50),
                        'twitter:creator' => !empty($author['twitter']) ?
                            '@' . $author['twitter'] : null,
                        'twitter:url' => self::url($post['slug']),
                        'twitter:image' => !empty($post['image']) ?
                            parent::url($post['image']) :
                            null,
                        'twitter:label1' => !$post['page'] ?
                            'Written by' : null,
                        'twitter:data1' => !$post['page'] ?
                            $author['name'] : null,
                        'twitter:label2' => !$post['page'] ?
                            'Tagged with' : null,
                        'twitter:data2' => !$post['page'] ?
                            implode(', ', (array) $post['tags']) : null
                    ]
                ]
            ],
            'helpers' => ['theme', 'url', 'utility'],
            // If we're editing or previewing, don't pass in user data to simulate what an
            // unauthenticated user would see.
            'user' => ($options['editable'] || $options['preview']) ? false : Session::user()
        ]);

        // Check for required helpers
        if($options['editable']) {
            // There's no reliable way to check for the existence of a Handlebar helper without
            // parsing the template file and all of the partials it uses before rendering. As a
            // workaround, we check for certain known strings that will be in the HTML when a post
            // is rendered.
            foreach([
                // The helper       // The string to search for
                '{{title editable="true"}}'         => '<div data-postleaf-id="post:title"',
                '{{content editable="true"}}'       => '<div data-postleaf-id="post:content"',
                '{{postleaf_head}}' => '<!--{{postleaf_head}}-->',
                '{{postleaf_foot}}' => '<!--{{postleaf_foot}}-->'
            ] as $helper => $test_string ) {
                if(!mb_strstr($html, $test_string)) {
                    throw new \Exception("The $helper helper is missing in $template.");
                }
            }
        }

        // Append a <base> tag for editable posts and post previews so they render properly no
        // matter where the rendering document exists.
        if($options['editable'] || $options['preview']) {
            $html = str_replace(
                '<!--{{postleaf_head}}-->',
                '<!--{{postleaf_head}}--><base href="' .
                    // The base should always end with a slash
                    htmlspecialchars(rtrim(self::url(), '/')) . '/">',
                $html
            );
        }

        return $html;
    }

    // Updates a post
    public static function update($slug, $properties) {
        // Get the post
        $post = self::get($slug);
        if(!$post) {
            throw new \Exception('Post not found: ' . $slug, self::NOT_FOUND);
        }

        // Merge options
        $post = array_merge($post, $properties);

        // Parse publish date format and convert to UTC
        $post['pub_date'] = self::localToUtc(self::parseDate($post['pub_date']));

        // Translate author slug to ID
        $post['author'] = User::getId($post['author']);
        if(!$post['author']) {
            throw new \Exception('Invalid user.', self::INVALID_USER);
        }

        // Empty title defaults to settings.default_title
        if(empty($post['title'])) {
            $post['title'] = Setting::get('default_title');
        }

        // Empty content defaults to settings.default_content
        if(empty($post['content'])) {
            $post['content'] = Setting::get('default_content');
        }

        // Don't allow null properties
        $post['image'] = (string) $post['image'];
        $post['meta_title'] = (string) $post['meta_title'];
        $post['meta_description'] = (string) $post['meta_description'];

        // Status must be `published` or `draft`
        if($properties['status'] !== 'draft') $properties['status'] = 'published';

        // Page, featured, and sticky must be 1 or 0
        foreach(['page', 'featured', 'sticky'] as $key) {
            $post[$key] = filter_var($post[$key], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        // Change the slug?
        if($post['slug'] !== $slug) {
            // Enforce slug syntax
            $post['slug'] = self::slug($post['slug']);

            // Is the slug valid?
            if(!mb_strlen($post['slug']) || self::isProtectedSlug($post['slug'])) {
                throw new \Exception('Invalid slug: ' . $post['slug'], self::INVALID_SLUG);
            }

            // Does a post already exist here?
            if(self::exists($post['slug'])) {
                throw new \Exception('Post already exists: ' . $post['slug'], self::ALREADY_EXISTS);
            }

            // If this is the custom homepage, update settings
            if(Setting::get('homepage') === $slug) {
                Setting::update('homepage', $post['slug']);
            }
        }

        try {
            // Update the post
            $st = self::$database->prepare('
                UPDATE __posts SET
                    slug = :slug,
                    pub_date = :pub_date,
                    author = :author,
                    title = :title,
                    content = :content,
                    image = :image,
                    meta_title = :meta_title,
                    meta_description = :meta_description,
                    status = :status,
                    page = :page,
                    featured = :featured,
                    sticky = :sticky
                WHERE slug = :original_slug
            ');
            $st->bindParam(':slug', $post['slug']);
            $st->bindParam(':pub_date', $post['pub_date']);
            $st->bindParam(':author', $post['author']);
            $st->bindParam(':title', $post['title']);
            $st->bindParam(':content', $post['content']);
            $st->bindParam(':image', $post['image']);
            $st->bindParam(':meta_title', $post['meta_title']);
            $st->bindParam(':meta_description', $post['meta_description']);
            $st->bindParam(':status', $post['status']);
            $st->bindParam(':page', $post['page']);
            $st->bindParam(':featured', $post['featured']);
            $st->bindParam(':sticky', $post['sticky']);
            $st->bindParam(':original_slug', $slug);
            $st->execute();
        } catch(\PDOException $e) {
            throw new \Exception('Database error: ' . $e->getMessage());
        }

        // Set post tags
        self::setTags($post['id'], $post['tags']);

        // Create a revision
        History::add($post['slug']);

        return true;
    }

    // Returns a post URL
    public static function url($slug = '') {
        // example.com/slug
        return parent::url($slug);
    }

}