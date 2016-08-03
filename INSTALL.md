# Postleaf

**Simple, beautiful publishing.**

Created by [Cory LaViska](https://twitter.com/claviska)

- Website: [postleaf.org](https://www.postleaf.org/)
- Twitter: [@postleafapp](https://twitter.com/postleafapp)

## Installation

1. Upload everything in this folder to your web server.
2. Open a web browser and navigation to your domain.
3. The installer will take care of the rest.

You can safely delete this file (INSTALL.md) anytime.

## Updating

To update from a previous version, simply replace `index.php` and the `source` folder with the new versions.

**Important:** If you're updating from 1.0.0-beta3 or below, update your database first using the following statement (you may have to adjust the `postleaf_` prefix):

```
ALTER TABLE `postleaf_posts` ADD `sticky` TINYINT NOT NULL AFTER `featured`;
ALTER TABLE `postleaf_users` ADD `twitter` VARCHAR(191) NOT NULL AFTER `avatar`;

```

## License

Licensed under the terms of the GNU GPLv3. See LICENSE.md for details.

©2016 A Beautiful Site, LLC

## Support

Please visit [postleaf.org/support](https://www.postleaf.org/support) for support.