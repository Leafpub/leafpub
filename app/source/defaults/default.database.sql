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
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

####################################################################################################
# uploads
####################################################################################################

DROP TABLE IF EXISTS `__uploads`;

CREATE TABLE `__uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL,
  `path` varchar(191) NOT NULL,
  `filename` varchar(191) NOT NULL,
  `extension` varchar(191) NOT NULL,
  `size` int(11) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `path` (`path`)
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