####################################################################################################
# history
####################################################################################################

DROP TABLE IF EXISTS `__history`;

CREATE TABLE `__history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post` int(11) NOT NULL,
  `rev_date` datetime NOT NULL,
  `post_data` longtext NOT NULL,
  `initial` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `post` (`post`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

####################################################################################################
# post_tags
####################################################################################################

DROP TABLE IF EXISTS `__post_tags`;

CREATE TABLE `__post_tags` (
  `post` int(11) NOT NULL,
  `tag` int(11) NOT NULL,
  KEY `post` (`post`),
  KEY `tag` (`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

####################################################################################################
# posts
####################################################################################################

DROP TABLE IF EXISTS `__posts`;

CREATE TABLE `__posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(191) NOT NULL,
  `created` datetime NOT NULL,
  `pub_date` datetime NOT NULL,
  `author` int(11) NOT NULL,
  `title` text NOT NULL,
  `content` longtext NOT NULL,
  `image` text NOT NULL,
  `meta_title` text NOT NULL,
  `meta_description` text NOT NULL,
  `status` enum('published','draft') NOT NULL DEFAULT 'published',
  `page` tinyint(4) NOT NULL,
  `featured` tinyint(4) NOT NULL,
  `sticky` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`) USING BTREE,
  KEY `pub_date` (`pub_date`),
  FULLTEXT KEY `title_fts` (`slug`,`title`),
  FULLTEXT KEY `content_fts` (`content`),
  FULLTEXT KEY `all_fts` (`slug`,`title`,`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

####################################################################################################
# plugins
####################################################################################################

DROP TABLE IF EXISTS `__plugins`;

CREATE TABLE `__plugins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(51) NOT NULL,
  `description` varchar(100) NOT NULL,
  `author` varchar(51) NOT NULL,
  `version` varchar(8) NOT NULL,
  `requires` varchar(8) NOT NULL,
  `license` varchar(8) NOT NULL,
  `dir` varchar(51) NOT NULL,
  `img` varchar(100) DEFAULT NULL,
  `link` varchar(100) DEFAULT NULL,
  `isAdminPlugin` tinyint(1) NOT NULL DEFAULT '0',
  `isMiddleware` tinyint(1) NOT NULL DEFAULT '0',
  `install_date` datetime NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `enable_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dir` (`dir`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

####################################################################################################
# settings
####################################################################################################

DROP TABLE IF EXISTS `__settings`;

CREATE TABLE `__settings` (
  `name` varchar(191) NOT NULL,
  `value` longtext NOT NULL,
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

####################################################################################################
# tags
####################################################################################################

DROP TABLE IF EXISTS `__tags`;

CREATE TABLE `__tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(191) NOT NULL,
  `created` datetime NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `cover` text NOT NULL,
  `meta_title` text NOT NULL,
  `meta_description` text NOT NULL,
  `type` enum('post','upload') NOT NULL DEFAULT 'post',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

####################################################################################################
# uploads
####################################################################################################

DROP TABLE IF EXISTS `__uploads`;

CREATE TABLE `__uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caption` varchar(191) DEFAULT NULL,
  `created` datetime NOT NULL,
  `path` varchar(191) NOT NULL,
  #`thumbnail` varchar(191) NOT NULL,
  `filename` varchar(90) NOT NULL,
  `extension` varchar(10) NOT NULL,
  `size` int(11) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  #UNIQUE KEY `path` (`path`),
  UNIQUE KEY `filename` (`filename`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `__upload_tags` (
  `upload` int(11) NOT NULL,
  `tag` int(11) NOT NULL,
  KEY `upload` (`upload`),
  KEY `tag` (`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO `__uploads` (`id`, `caption`, `created`, `path`, `filename`, `extension`, `size`, `width`, `height`) VALUES
(1, '', '2017-02-01 12:23:45', 'content/uploads/2016/10/', 'leaves', 'jpg', 254734, 3000, 2008),
(2, '', '2017-02-01 12:24:28', 'content/uploads/2016/10/', 'sunflower', 'jpg', 280779, 3000, 1990),
(3, '', '2017-02-01 12:24:40', 'content/uploads/2016/10/', 'autumn', 'jpg', 383879, 3000, 2000),
(4, '', '2017-02-01 12:24:54', 'content/uploads/2016/10/', 'ladybug', 'jpg', 277815, 3000, 1993);

####################################################################################################
# post_uploads
####################################################################################################

DROP TABLE IF EXISTS `__post_uploads`;

CREATE TABLE `__post_uploads` (
  `post` int(11) NOT NULL,
  `upload` int(11) NOT NULL,
  KEY `post` (`post`),
  KEY `upload` (`upload`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

####################################################################################################
# users
####################################################################################################

DROP TABLE IF EXISTS `__users`;

CREATE TABLE `__users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(191) NOT NULL,
  `created` datetime NOT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(191) NOT NULL,
  `reset_token` varchar(191) NOT NULL,
  `role` enum('owner','admin','editor','author') NOT NULL DEFAULT 'author',
  `bio` text NOT NULL,
  `cover` text NOT NULL,
  `avatar` text NOT NULL,
  `twitter` VARCHAR(191) NOT NULL,
  `location` text NOT NULL,
  `website` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

####################################################################################################
# view_posts
####################################################################################################

CREATE VIEW __view_posts AS
    SELECT  
    a.id, a.slug, a.created, a.pub_date, c.slug as author, a.title, a.content, 
    a.meta_title, a.meta_description, a.status, a.page, a.featured, a.sticky, 
    CONCAT_WS('.', CONCAT(b.path, b.filename), b.extension) as image
    FROM 
    `__posts` a
    LEFT JOIN 
    `__uploads` b
    ON 
    a.image = b.id
    INNER JOIN
    `__users` c
    ON
    a.author = c.id