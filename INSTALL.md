# Leafpub

**Simple, beautiful publishing.**

Created by [Cory LaViska](https://twitter.com/claviska)
Maintained by [Marc Apfelbaum](https://twitter.com/karsasmus)

- Website: [leafpub.org](https://www.leafpub.org/)
- Twitter: [@leafpubapp](https://twitter.com/leafpubapp)

## Installation

1. Upload everything in this folder to your web server.
2. Open a web browser and navigation to your domain.
3. The installer will take care of the rest.

You can safely delete this file (INSTALL.md) anytime.

## Updating

To update from a previous version, simply replace `index.php` and the `source` folder with the new versions.

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