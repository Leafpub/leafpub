# Leafpub

**Simple, beautiful publishing.**

Created by [Cory LaViska](https://twitter.com/claviska)
Maintained by [Marc Apfelbaum](https://twitter.com/karsasmus)

- Website: [leafpub.org](https://www.leafpub.org/)
- Twitter: [@leafpubapp](https://twitter.com/leafpub)

## Installation

1. Upload everything in this folder to your web server.
2. Open a web browser and navigation to your domain.
3. The installer will take care of the rest.

You can safely delete this file (INSTALL.md) anytime.

## Updating

To update from a previous version, simply replace `index.php` and the `source` folder with the new versions.

**Important:** If you're updating from 1.0.0, update update your database first using the following statement (you may have to adjust the `leafpub_` prefix):

```
CREATE TABLE `leafpub_plugins` (
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

```

**Important:** If you're updating from 1.0.0-beta3 or below, update your database first using the following statement (you may have to adjust the `leafpub_` prefix):

```
ALTER TABLE `leafpub_posts` ADD `sticky` TINYINT NOT NULL AFTER `featured`;
ALTER TABLE `leafpub_users` ADD `twitter` VARCHAR(191) NOT NULL AFTER `avatar`;

```

## License

Licensed under the terms of the GNU GPLv3. See LICENSE.md for details.

©2016 Cory & Marc

## Support

Please visit [leafpub.org/support](https://www.leafpub.org/support) for support.