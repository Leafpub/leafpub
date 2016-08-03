<?php
//
// Postleaf\User: methods for working with users
//
namespace Postleaf;

use Exception,
    PDO,
    PDOException;

class User extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Constants
    ////////////////////////////////////////////////////////////////////////////////////////////////

    const
        ALREADY_EXISTS = 1,
        CANNOT_CHANGE_OWNER = 2,
        CANNOT_DELETE_OWNER = 3,
        INVALID_EMAIL = 4,
        INVALID_NAME = 5,
        INVALID_PASSWORD = 6,
        INVALID_SLUG = 7,
        INVALID_USER = 8,
        NOT_FOUND = 9,
        PASSWORD_TOO_SHORT = 10,
        UNABLE_TO_ASSIGN_POSTS = 11;

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Private methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Normalize data types for certain fields
    private static function normalize($user) {
        // Cast to integer
        $user['id'] = (int) $user['id'];

        // Convert dates from UTC to local
        $user['created'] = self::utcToLocal($user['created']);

        return $user;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Adds a user
    public static function add($slug, $properties) {
        // Enforce slug syntax
        $slug = self::slug($slug);

        // Is the slug valid?
        if(!mb_strlen($slug) || self::isProtectedSlug($slug)) {
            throw new Exception('Invalid slug: ' . $slug, self::INVALID_SLUG);
        }

        // Does a user already exist here?
        if(self::exists($slug)) {
            throw new Exception('User already exists: ' . $slug, self::ALREADY_EXISTS);
        }

        // Must have a name
        if(!mb_strlen($properties['name'])) {
            throw new Exception('No name specified', self::INVALID_NAME);
        }

        // Must have a valid email address
        if(!self::isValidEmail($properties['email'])) {
            throw new Exception(
                'Invalid email address: ' . $properties['email'],
                self::INVALID_EMAIL
            );
        }

        // Must have a long enough password
        if(mb_strlen($properties['password']) < Setting::get('password_min_length')) {
            throw new Exception(
                'Passwords must be at least ' . Setting::get('password_min_length') . ' characters long',
                self::PASSWORD_TOO_SHORT
            );
        }

        // Cannot create an owner if one already exists
        if($properties['role'] === 'owner' && self::getOwner()) {
            throw new Exception(
                'The owner role cannot be revoked or reassigned',
                self::CANNOT_CHANGE_OWNER
            );
        }

        // Don't allow null properties
        $properties['reset_token'] = (string) $properties['reset_token'];
        $properties['bio'] = (string) $properties['bio'];
        $properties['cover'] = (string) $properties['cover'];
        $properties['avatar'] = (string) $properties['avatar'];
        $properties['twitter'] = (string) $properties['twitter'];
        $properties['location'] = (string) $properties['location'];
        $properties['website'] = (string) $properties['website'];

        // Remove @ from Twitter handle
        $properties['twitter'] = preg_replace('/@/', '', $properties['twitter']);

        // Role must be owner, admin, or editor
        if(!in_array($properties['role'], ['owner', 'admin', 'editor', 'author'])) {
            $properties['role'] = 'author';
        }

        // Hash the password
        $properties['password'] = password_hash($properties['password'], PASSWORD_DEFAULT);
        if($properties['password'] === false) {
            throw new Exception('Invalid password', self::INVALID_PASSWORD);
        }

        try {
            // Create the user
            $st = self::$database->prepare('
                INSERT INTO __users SET
                    slug = :slug,
                    created = NOW(),
                    name = :name,
                    email = :email,
                    password = :password,
                    reset_token = :reset_token,
                    role = :role,
                    bio = :bio,
                    cover = :cover,
                    avatar = :avatar,
                    twitter = :twitter,
                    location = :location,
                    website = :website
            ');
            $st->bindParam(':slug', $slug);
            $st->bindParam(':name', $properties['name']);
            $st->bindParam(':email', $properties['email']);
            $st->bindParam(':password', $properties['password']);
            $st->bindParam(':reset_token', $properties['reset_token']);
            $st->bindParam(':role', $properties['role']);
            $st->bindParam(':bio', $properties['bio']);
            $st->bindParam(':cover', $properties['cover']);
            $st->bindParam(':avatar', $properties['avatar']);
            $st->bindParam(':twitter', $properties['twitter']);
            $st->bindParam(':location', $properties['location']);
            $st->bindParam(':website', $properties['website']);
            $st->execute();
            return $st->rowCount() > 0;
        } catch(PDOException $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    // Returns the total number of users that exist
    public static function count() {
        try {
            $st = self::$database->query('SELECT COUNT(*) FROM __users');
            return (int) $st->fetch()[0];
        } catch(PDOException $e) {
            return false;
        }
    }

    // Deletes a user
    public static function delete($slug, $recipient_slug = null) {
        // Get target user
        $user = self::get($slug);
        if(!$user) throw new Exception('Invalid user.', self::INVALID_USER);

        // Can't delete the owner
        if($user['role'] === 'owner') {
            throw new Exception('Cannot delete the owner account.', self::CANNOT_DELETE_OWNER);
        }

        // Get the user that will receive the orphaned posts
        if($recipient_slug) {
            // Use the specified recipient
            $recipient = self::get($recipient_slug);
        } else {
            // Use the owner
            $recipient = self::getOwner();
        }
        if(!$recipient) {
            throw new Exception(
                'Invalid recipient: ' . $recipient['slug'],
                self::UNABLE_TO_ASSIGN_POSTS
            );
        }

        // Assign posts to new user
        try {
            $st = self::$database->prepare('
                UPDATE __posts
                SET author = :recipient
                WHERE author = :user
            ');
            $st->bindParam(':recipient', $recipient['id']);
            $st->bindParam(':user', $user['id']);
            $st->execute();
        } catch(PDOException $e) {
            throw new Exception(
                'Unable to assign posts to new user: ' . $recipient['slug'],
                self::UNABLE_TO_ASSIGN_POSTS
            );
        }

        // Delete the target user
        try {
            // Never allow the owner user to be deleted
            $st = self::$database->prepare('
                DELETE FROM __users WHERE slug = :slug AND role != \'owner\'
            ');
            $st->bindParam(':slug', $slug);
            $st->execute();
            return $st->rowCount() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }

    // Tells whether a user exists
    public static function exists($slug) {
        try {
            $st = self::$database->prepare('SELECT id FROM __users WHERE slug = :slug');
            $st->bindParam(':slug', $slug);
            $st->execute();
            return !!$st->fetch();
        } catch(PDOException $e) {
            return false;
        }
    }

    // Gets a single user. Returns an array on success, false if not found.
    public static function get($slug) {
        try {
            $st = self::$database->prepare('
                SELECT
                    id, slug, created, name, email, password, reset_token, role, bio, cover,
                    avatar, twitter, location, website
                FROM __users
                WHERE slug = :slug
            ');
            $st->bindParam(':slug', $slug);
            $st->execute();
            $user = $st->fetch(PDO::FETCH_ASSOC);
            if(!$user) return false;
        } catch(PDOException $e) {
            return false;
        }

        // Normalize fields
        return self::normalize($user);
    }

    // Converts a user slug to an ID
    public static function getId($slug) {
        try {
            $st = self::$database->prepare('SELECT id FROM __users WHERE slug = :slug');
            $st->bindParam(':slug', $slug);
            $st->execute();
            return (int) $st->fetch()[0];
        } catch(PDOException $e) {
            return false;
        }
    }

    // Gets multiple users. Returns an array of tags on success, false if not found. If $pagination
    // is specified, it will be populated with pagination data generated by Postleaf::paginate().
    public static function getMany($options = null, &$pagination = null) {
        // Merge options with defaults
        $options = array_merge([
            'query' => null,
            'role' => null,
            'page' => 1,
            'items_per_page' => 10
        ], (array) $options);

        // Generate select SQL
        $select_sql = '
            SELECT
                id, slug, created, name, email, password, reset_token, role, bio, cover, avatar,
                twitter, location, website
            FROM __users
        ';

        // Generate where SQL
        $where_sql = '
            WHERE (
                slug LIKE :query OR
                name LIKE :query OR
                email LIKE :query OR
                bio LIKE :query OR
                location LIKE :query
            )
        ';

        if($options['role']) {
            $where_sql .= ' AND FIND_IN_SET(role, :role) > 0';
            $role = implode(',', (array) $options['role']);
        }

        // Generate order SQL
        $order_sql = ' ORDER BY name';

        // Generate limit SQL
        $limit_sql = ' LIMIT :offset, :count';

        // Assemble count query to determine total matching users
        $count_sql = "SELECT COUNT(*) FROM __users $where_sql";

        // Assemble data query to fetch users
        $data_sql = "$select_sql $where_sql $order_sql $limit_sql";

        // Run the count query
        try {
            $query = '%' . Database::escapeLikeWildcards($options['query']) . '%';

            // Get count of all matching rows
            $st = self::$database->prepare($count_sql);
            $st->bindParam(':query', $query);
            if($options['role']) $st->bindParam(':role', $role);
            $st->execute();
            $total_items = (int) $st->fetch()[0];
        } catch(PDOException $e) {
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
            if($options['role']) $st->bindParam(':role', $role);
            $st->bindParam(':offset', $offset, PDO::PARAM_INT);
            $st->bindParam(':count', $count, PDO::PARAM_INT);
            $st->execute();
            $users = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }

        // Normalize fields
        foreach($users as $key => $value) {
            $users[$key] = self::normalize($value);
        }

        return $users;
    }

    // Returns an array of all user names and corresponding slugs
    public static function getNames() {
        try {
            $st = self::$database->query('SELECT slug, name FROM __users ORDER BY name');
            $users = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }

        return $users;
    }

    // Gets the owner account
    public static function getOwner() {
        try {
            $st = self::$database->query('
                SELECT slug
                FROM __users
                WHERE role = "owner"
                ORDER BY created
                LIMIT 1
            ');
            $result = $st->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }

        return self::get($result['slug']);
    }

    // Renders an author page
    public static function render($slug, $page = 1) {
        // Get the author
        $author = self::get($slug);
        if(!$author) return false;

        // Get the author's posts
        $posts = Post::getMany([
            'author' => $slug,
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
            'template' => Theme::getPath('author.hbs'),
            'data' => [
                'author' => $author,
                'posts' => $posts,
                'pagination' => $pagination
            ],
            'special_vars' => [
                'meta' => [
                    'title'=> $author['name'],
                    'description' => self::getChars($author['bio'], 160),
                    // JSON linked data (schema.org)
                    'ld_json' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'Person',
                        'name' => $author['name'],
                        'description' => strip_tags(self::markdownToHtml($author['bio'])),
                        'url' => self::url($author['slug']),
                        'image' => !empty($author['avatar']) ?
                            parent::url($author['avatar']) : null,
                        'sameAs' => !empty($author['website']) ?
                            [$author['website']] : null
                    ],
                    // Open Graph
                    'open_graph' => [
                        'og:type' => 'profile',
                        'og:site_name' => Setting::get('title'),
                        'og:title' => $author['name'] . ' &middot; ' . Setting::get('title'),
                        'og:description' => strip_tags(self::markdownToHtml($author['bio'])),
                        'og:url' => self::url($author['slug']),
                        'og:image' => !empty($author['avatar']) ?
                            parent::url($author['avatar']) : null
                    ],
                    // Twitter Card
                    'twitter_card' => [
                        'twitter:card' => !empty($author['cover']) ?
                            'summary_large_image' : 'summary',
                        'twitter:site' => !empty(Setting::get('twitter')) ?
                            '@' . Setting::get('twitter') : null,
                        'twitter:title' => $author['name'] . ' &middot; ' . Setting::get('title'),
                        'twitter:description' => strip_tags(self::markdownToHtml($author['bio'])),
                        'twitter:creator' => !empty($author['twitter']) ?
                            '@' . $author['twitter'] : null,
                        'twitter:url' => self::url($author['slug']),
                        'twitter:image' => !empty($author['cover']) ?
                            parent::url($author['cover']) : null
                    ]
                ]
            ],
            'helpers' => ['url', 'utility', 'theme']
        ]);
    }

    // Updates a user
    public static function update($slug, $properties) {
        // Get the user
        $user = self::get($slug);
        if(!$user) {
            throw new Exception('User not found: ' . $slug, self::NOT_FOUND);
        }

        // The owner role cannot be revoked or reassigned
        if(
            isset($properties['role']) && (
                // Can't go from owner to non-owner
                ($user['role'] === 'owner' && $properties['role'] !== 'owner') ||
                // Can't go from non-owner to owner
                ($user['role'] !== 'owner' && $properties['role'] === 'owner')
            )
        ) {
            throw new Exception(
                'The owner role cannot be revoked or reassigned',
                self::CANNOT_CHANGE_OWNER
            );
        }

        // Ignore the password property if a string wasn't passed in. This prevents the password
        // from being overwritten during array_merge().
        if(!is_string($properties['password'])) unset($properties['password']);

        // Merge properties
        $user = array_merge($user, $properties);

        // Must have a name
        if(!mb_strlen($user['name'])) {
            throw new Exception('No name specified', self::INVALID_NAME);
        }

        // Must have an email address
        if(!self::isValidEmail($user['email'])) {
            throw new Exception('Invalid email address: ' . $user['email'], self::INVALID_EMAIL);
        }

        // Don't allow null properties
        $user['reset_token'] = (string) $user['reset_token'];
        $user['bio'] = (string) $user['bio'];
        $user['cover'] = (string) $user['cover'];
        $user['avatar'] = (string) $user['avatar'];
        $user['twitter'] = (string) $user['twitter'];
        $user['location'] = (string) $user['location'];
        $user['website'] = (string) $user['website'];

        // Remove @ from Twitter handle
        $user['twitter'] = preg_replace('/@/', '', $user['twitter']);

        // Role must be owner, admin, or editor
        if(!in_array($user['role'], ['owner', 'admin', 'editor', 'author'])) {
            $user['role'] = 'author';
        }

        // Change the password?
        if(is_string($properties['password'])) {
            // Must have a long enough password
            if(mb_strlen($properties['password']) < Setting::get('password_min_length')) {
                throw new Exception(
                    'Passwords must be at least ' . Setting::get('password_min_length') . ' characters long',
                    self::PASSWORD_TOO_SHORT
                );
            }

            // Hash the password
            $user['password'] = password_hash($properties['password'], PASSWORD_DEFAULT);
            if($user['password'] === false) {
                throw new Exception('Invalid password', self::INVALID_PASSWORD);
            }
        }

        // Change the slug?
        if($user['slug'] !== $slug) {
            // Enforce slug syntax
            $user['slug'] = self::slug($user['slug']);

            // Is the slug valid?
            if(!mb_strlen($user['slug']) || self::isProtectedSlug($user['slug'])) {
                throw new Exception('Invalid slug: ' . $user['slug'], self::INVALID_SLUG);
            }

            // Does a user already exist here?
            if(self::exists($user['slug'])) {
                throw new Exception('User already exists: ' . $user['slug'], self::ALREADY_EXISTS);
            }
        }

        // Update the user
        try {
            $st = self::$database->prepare('
                UPDATE __users SET
                    slug = :slug,
                    name = :name,
                    email = :email,
                    password = :password,
                    reset_token = :reset_token,
                    role = :role,
                    bio = :bio,
                    cover = :cover,
                    avatar = :avatar,
                    twitter = :twitter,
                    location = :location,
                    website = :website
                WHERE slug = :original_slug
            ');
            $st->bindParam(':slug', $user['slug']);
            $st->bindParam(':name', $user['name']);
            $st->bindParam(':email', $user['email']);
            $st->bindParam(':password', $user['password']);
            $st->bindParam(':reset_token', $user['reset_token']);
            $st->bindParam(':role', $user['role']);
            $st->bindParam(':bio', $user['bio']);
            $st->bindParam(':cover', $user['cover']);
            $st->bindParam(':avatar', $user['avatar']);
            $st->bindParam(':twitter', $user['twitter']);
            $st->bindParam(':location', $user['location']);
            $st->bindParam(':website', $user['website']);
            $st->bindParam(':original_slug', $slug);
            $st->execute();
        } catch(PDOException $e) {
            return false;
        }

        // Update session data for the authenticated user
        if(Session::user()['slug'] === $slug) {
            Session::update($user['slug']);
        }

        return $st->rowCount() > 0;
    }

    // Returns an author (user) URL
    public static function url($slug = '', $page = 1) {
        return $page > 1 ?
            // example.com/author/name/page/2
            parent::url(
                Setting::get('frag_author'),
                $slug,
                Setting::get('frag_page'),
                $page
            ) :
            // example.com/author/name
            parent::url(Setting::get('frag_author'), $slug);
    }

    // Verifies a user's password and returns true on success
    public static function verifyPassword($slug, $password) {
        // Get the user
        $user = User::get($slug);
        if(!$user) return false;

        // Verify the password
        return password_verify($password, $user['password']);
    }

}