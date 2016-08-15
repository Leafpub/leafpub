# <img src="https://www.postleaf.org/content/themes/postleaf/img/logo-color-text.svg" alt="Postleaf" width="300">

**Simple, beautiful publishing.**

Created by [Cory LaViska](https://twitter.com/claviska)

- Website: [postleaf.org](https://www.postleaf.org/)
- Twitter: [@postleafapp](https://twitter.com/postleafapp)

This software is dedicated to my daughter, Sophia. Chase your dreams, little tanuki. 💙💚

![Screenshots](https://www.postleaf.org/content/uploads/2016/07/homepage-splash.png)

## Requirements

- PHP 5.5+
- MySQL 5.5.3+

## Download

**This is the development repo!** You'll need to build Postleaf using the instructions below before running it.

Download the latest ready-to-use version from: https://www.postleaf.org/download

## Contributing

Postleaf uses Composer and NPM to manage dependencies and Gulp as its task runner. To contribute to this project, you'll need to clone the repository and install the required development tools listed below.

- [Composer](https://getcomposer.org/)
- [Node](https://nodejs.org/en/)
- [Gulp](http://gulpjs.com/) (Install using `npm install -g gulp-cli`)

Please read through our [contributing guidelines](https://github.com/postleaf/postleaf/blob/master/.github/CONTRIBUTING.md).

## Building

Once you have the necessary development tools installed:

1. Open a terminal
2. Navigate to the root directory of your cloned repo
3. Run the following command:

```
composer install
```

Composer will install its own dependencies and then run `npm install`. This may take a few minutes as packages are downloaded. Once complete, Composer will trigger `gulp build` which will generate all the assets you need to run Postleaf.

**Important:** You'll also need to add [the default theme](https://github.com/Postleaf/range) to `content/themes/range/` manually. This will happen automatically once Postleaf is out of beta.

## Using Gulp

From the root directory, you can use `gulp help` to see all available tasks:

```
Usage
    gulp [TASK] [OPTIONS...]

Available tasks
    build          Run all build tasks. [build:fonts, build:images, build:prune, build:scripts, build:styles]
    build:fonts    Build font assets. [clean:fonts]
    build:images   Optimize images. [clean:images]
    build:prune    Prune unused files from vendor packages.
    build:scripts  Build scripts. [jshint, clean:scripts]
    build:styles   Build styles. [clean:styles]
    clean          Clean up generated files. [clean:fonts, clean:images, clean:scripts, clean:styles]
    clean:fonts    Delete generated fonts.
    clean:images   Delete generated images.
    clean:scripts  Delete generated scripts.
    clean:styles   Delete generated styles.
    default        Run the default task. [watch]
    help           Display this help text.
    jshint         Lint source scripts with JSHint.
    release:clean  Delete all generated releases.
    release:make   Generate a release.
    watch          Watch for script and style changes.
```

For development, use `gulp watch` to automatically compile Sass/JavaScript as you work.

## Testing

You can run Postleaf on PHP's built in web server using the following command:

```
php -S localhost:8080 -t app
```

Then open http://localhost:8080 in your browser.

**Note:** You might need to use `127.0.0.1` instead of `localhost` in your database config!

## Versioning

Postleaf is maintained under the [Semantic Versioning guidelines](http://semver.org/) and we adhere to them as closely as possible.

## Developers

**Cory LaViska**

- http://twitter.com/claviska
- http://github.com/claviska

## License

©2016 A Beautiful Site, LLC

This software is copyrighted. You may use it under the terms of the GNU GPLv3 or later. See LICENSE.md for licensing details.

All code is copyright 2016 A Beautiful Site, LLC except where noted. Third-party libraries are copyrighted and licensed by their respective owners.

### Theme & Plugin Policy

We do not consider Postleaf themes and plugins to be derivative works, as they are used to extend and enhance the software's functionality strictly through its API and they do not in any way modify Postleaf's core codebase. Therefore, in our opinion, themes and plugins may be licensed completely at the author's discretion.

## Support

Please visit [postleaf.org/support](https://www.postleaf.org/support) for support.

------------------------------

*“The starting point of all achievement is desire.” — Napoleon Hill*