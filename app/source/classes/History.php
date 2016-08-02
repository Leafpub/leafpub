<?php
//
// Postleaf\History: methods for working with history
//
namespace Postleaf;

class History extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Private methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Normalize data types for certain fields
    private static function normalize($revision) {
        // Cast to integer
        $revision['id'] = (int) $revision['id'];
        $revision['initial'] = (int) $revision['initial'];

        // Convert dates from UTC to local
        $revision['rev_date'] = self::utcToLocal($revision['rev_date']);

        // Decode JSON data
        $revision['post_data'] = json_decode($revision['post_data'], true);

        return $revision;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Get a history item
    public static function get($id) {
        try {
            $st = self::$database->prepare('
                SELECT
                    id, rev_date, post_data, initial,
                    (SELECT slug FROM __posts WHERE id = post) AS slug
                FROM __history
                WHERE id = :id
            ');
            $st->bindParam(':id', $id);
            $st->execute();
            $revision = $st->fetch(\PDO::FETCH_ASSOC);
            if(!$revision) return false;
        } catch(\PDOException $e) {
            return false;
        }

        return self::normalize($revision);
    }

    // Get all history for the specified post
    public static function getAll($slug) {
        try {
            $st = self::$database->prepare('
                SELECT id, rev_date, post_data, initial
                FROM __history
                WHERE post = (SELECT id FROM __posts WHERE slug = :slug)
                ORDER BY rev_date DESC
            ');
            $st->bindParam(':slug', $slug);
            $st->execute();
            $revisions = $st->fetchAll(\PDO::FETCH_ASSOC);
            if(!$revisions) return false;
        } catch(\PDOException $e) {
            return false;
        }

        // Normalize fields
        foreach($revisions as $key => $value) {
            $revisions[$key] = self::normalize($value);
        }

        return $revisions;
    }

    // Adds a revision based on the specified post's current entry
    public static function add($slug, $initial = false) {
        $post = Post::get($slug);
        if(!$post) return false;
        $post_id = $post['id'];
        $rev_date = self::localToUtc(date('Y-m-d H:i:s')); // convert to UTC
        $post_data = json_encode($post);
        $initial = $initial ? 1 : 0;

        // Create the revision
        try {
            $st = self::$database->prepare('
                INSERT INTO __history SET
                    post = :post_id,
                    rev_date = :rev_date,
                    post_data = :post_data,
                    initial = :initial
            ');
            $st->bindParam(':post_id', $post_id);
            $st->bindParam(':rev_date', $rev_date);
            $st->bindParam(':post_data', $post_data);
            $st->bindParam(':initial', $initial);
            $st->execute();
            $history_id = (int) self::$database->lastInsertId();
        } catch(\PDOException $e) {
            return false;
        }

        return $history_id;
    }

    // Delete a history record
    public static function delete($id) {
        try {
            $st = self::$database->prepare('DELETE FROM __history WHERE id = :id');
            $st->bindParam(':id', $id);
            $st->execute();
            return $st->rowCount() > 0;
        } catch(\PDOException $e) {
            return false;
        }
    }

    // Remove all history linked to the specified post
    public static function flush($slug) {
        try {
            $st = self::$database->prepare('
                DELETE FROM __history WHERE
                post = (SELECT id FROM __posts WHERE slug = :slug)
            ');
            $st->bindParam(':slug', $slug);
            $st->execute();
            return true;
        } catch(\Exception $e) {
            return false;
        }
    }
}