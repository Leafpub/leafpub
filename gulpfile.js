'use strict';

var gulp = require('gulp-help')(require('gulp')),
    autoprefixer = require('gulp-autoprefixer'),
    cleanCSS = require('gulp-clean-css'),
    del = require('del'),
    fs = require('fs-extra'),
    imagemin = require('gulp-imagemin'),
    jshint = require('gulp-jshint'),
    notify = require('gulp-notify'),
    path = require('path'),
    preprocess = require('gulp-preprocess'),
    rename = require('gulp-rename'),
    sass = require('gulp-sass'),
    uglify = require('gulp-uglify'),
    watch = require('gulp-watch');

////////////////////////////////////////////////////////////////////////////////////////////////////
// Config
////////////////////////////////////////////////////////////////////////////////////////////////////

var assets = 'app/source/assets',
    fonts = {
        base : 'node_modules/font-awesome/fonts',
        source: 'node_modules/font-awesome/fonts/**/*.+(eot|svg|ttf|woff|woff2|otf)',
        target: assets + '/font'
    },
    images = {
        source: 'app/source/images/**/*.+(gif|jpg|jpeg|png|svg)',
        target: assets + '/img'
    },
    scripts = {
        source: [
            'app/source/scripts/**/*.js',
            '!app/source/scripts/modules/**/*.js'
        ],
        modules: 'app/source/scripts/modules/**/*.js',
        target: assets + '/js'
    },
    styles = {
        source: 'app/source/styles/**/*.scss',
        target: assets + '/css'
    };

////////////////////////////////////////////////////////////////////////////////////////////////////
// Build functions
////////////////////////////////////////////////////////////////////////////////////////////////////

// Copies font files to target
function buildFonts(source, target, base) {
    return gulp.src(source, { base: base })
        .pipe(gulp.dest(target))
        .pipe(notify({
            title: 'Postleaf',
            icon: path.join(__dirname, 'app/source/images/logo-color.png'),
            message: 'Build fonts task complete.',
            onLast: true
        }));
}

// Optimizes images in source and outputs them in target
function buildImages(source, target) {
    return gulp.src(source)
        .pipe(imagemin())
        .pipe(gulp.dest(target))
        .pipe(notify({
            title: 'Postleaf',
            icon: path.join(__dirname, 'app/source/images/logo-color.png'),
            message: 'Build images task complete.',
            onLast: true
        }));
}

// Minifies scripts in source and outputs them in target
function buildScripts(source, target) {
    return gulp.src(source)
        .pipe(preprocess({
            includeBase: __dirname
        }))
            .on('error', function(err) {
                notify({
                    title: 'Postleaf',
                    icon: path.join(__dirname, 'app/source/images/ladybug.png'),
                    message: 'Error including scripts: ' + err
                }).write(err);
                this.emit('end');
            })
        .pipe(uglify({
            preserveComments: 'license'
        }))
            .on('error', function(err) {
                notify({
                    title: 'Postleaf',
                    icon: path.join(__dirname, 'app/source/images/ladybug.png'),
                    message: 'Error minifying scripts: ' + err
                }).write(err);
                this.emit('end');
            })
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(target))
        .pipe(notify({
            title: 'Postleaf',
            icon: path.join(__dirname, 'app/source/images/logo-color.png'),
            message: 'Build scripts task complete.',
            onLast: true
        }));
}

// Compiles styles in source and outputs them in target
function buildStyles(source, target) {
    return gulp.src(source)
        .pipe(sass({
            includePaths: [
                'node_modules'
            ],
            precision: 8,
            outputStyle: 'compressed'
        }))
            .on('error', function(err) {
                notify({
                    title: 'Postleaf',
                    icon: path.join(__dirname, 'app/source/images/ladybug.png'),
                    message: 'Error compiling styles: ' + err
                }).write(err);
                this.emit('end');
            })
        .pipe(autoprefixer({
            browsers: ['last 2 versions']
        }))
        .pipe(cleanCSS({
            keepBreaks: true,
            keepSpecialComments: 1
        }))
        .pipe(gulp.dest(target))
        .pipe(notify({
            title: 'Postleaf',
            icon: path.join(__dirname, 'app/source/images/logo-color.png'),
            message: 'Build styles task complete.',
            onLast: true
        }));
}

// Runs source through JSHint
function lintScripts(source) {
    return gulp.src(source)
        .pipe(jshint('.jshintrc'))
        .pipe(jshint.reporter('jshint-stylish'))
            .on('error', function(err) {
                notify({
                    title: 'Postleaf',
                    icon: path.join(__dirname, 'app/source/images/ladybug.png'),
                    message: 'Error linting scripts: ' + err
                }).write(err);
                this.emit('end');
            });
}

// Removes unused files from app/source/vendor
function pruneVendor() {
    //
    // Beware: the glob pattern ** matches all children AND the parent! You must explicitly ignore
    // ALL the parent directories as well as the files you wish to keep.
    //
    return del.sync([
        // abeautifulsite/simpleimage
        'app/source/vendor/abeautifulsite/simpleimage/example',
        'app/source/vendor/abeautifulsite/simpleimage/.gitignore',
        'app/source/vendor/abeautifulsite/simpleimage/composer.json',

        // container-interop/container-interop
        'app/source/vendor/container-interop/container-interop/docs',
        'app/source/vendor/container-interop/container-interop/.gitignore',
        'app/source/vendor/container-interop/container-interop/composer.json',
        'app/source/vendor/container-interop/container-interop/README.md',

        // erusev/parsedown
        'app/source/vendor/erusev/parsedown/test',
        'app/source/vendor/erusev/parsedown/.travis.yml',
        'app/source/vendor/erusev/parsedown/composer.json',
        'app/source/vendor/erusev/parsedown/phpunit.xml.dist',
        'app/source/vendor/erusev/parsedown/README.md',

        // firebase/php-jwt
        'app/source/vendor/firebase/php-jwt/tests',
        'app/source/vendor/firebase/php-jwt/.gitignore',
        'app/source/vendor/firebase/php-jwt/.travis.yml',
        'app/source/vendor/firebase/php-jwt/composer.json',
        'app/source/vendor/firebase/php-jwt/composer.lock',
        'app/source/vendor/firebase/php-jwt/package.xml',
        'app/source/vendor/firebase/php-jwt/README.md',
        'app/source/vendor/firebase/php-jwt/run-tests.sh',
        'app/source/vendor/firebase/php-jwt/phpunit.xml.dist',

        // nikic/fast-route
        'app/source/vendor/nikic/fast-route/test',
        'app/source/vendor/nikic/fast-route/.hhconfig',
        'app/source/vendor/nikic/fast-route/.travis.yml',
        'app/source/vendor/nikic/fast-route/composer.json',
        'app/source/vendor/nikic/fast-route/FastRoute.hhi',
        'app/source/vendor/nikic/fast-route/phpunit.xml',
        'app/source/vendor/nikic/fast-route/README.md',

        // pimple/pimple
        'app/source/vendor/pimple/pimple/ext/tests',
        'app/source/vendor/pimple/pimple/src/Pimple/Tests',
        'app/source/vendor/pimple/pimple/.gitignore',
        'app/source/vendor/pimple/pimple/.travis.yml',
        'app/source/vendor/pimple/pimple/CHANGELOG',
        'app/source/vendor/pimple/pimple/composer.json',
        'app/source/vendor/pimple/pimple/phpunit.xml.dist',
        'app/source/vendor/pimple/pimple/README.rst',

        // slim/slim
        'app/source/vendor/slim/slim/example',
        'app/source/vendor/slim/slim/composer.json',
        'app/source/vendor/slim/slim/CONTRIBUTING.md',
        'app/source/vendor/slim/slim/README.md',

        // tinymce/tinymce
        'app/source/vendor/tinymce/tinymce/**',
        '!app/source/vendor/tinymce/tinymce',
        '!app/source/vendor/tinymce/tinymce/tinymce.min.js',
        '!app/source/vendor/tinymce/tinymce/license.txt',
        '!app/source/vendor/tinymce/tinymce/plugins',
        '!app/source/vendor/tinymce/tinymce/plugins/lists',
        '!app/source/vendor/tinymce/tinymce/plugins/lists/plugin.min.js',
        '!app/source/vendor/tinymce/tinymce/plugins/paste',
        '!app/source/vendor/tinymce/tinymce/plugins/paste/plugin.min.js',
        '!app/source/vendor/tinymce/tinymce/plugins/table',
        '!app/source/vendor/tinymce/tinymce/plugins/table/plugin.min.js',
        '!app/source/vendor/tinymce/tinymce/plugins/textpattern',
        '!app/source/vendor/tinymce/tinymce/plugins/textpattern/plugin.min.js',
        '!app/source/vendor/tinymce/tinymce/themes',
        '!app/source/vendor/tinymce/tinymce/themes/modern',
        '!app/source/vendor/tinymce/tinymce/themes/modern/theme.min.js',

        // zordius/lightncandy
        'app/source/vendor/zordius/lightncandy/.github',
        'app/source/vendor/zordius/lightncandy/build',
        'app/source/vendor/zordius/lightncandy/specs',
        'app/source/vendor/zordius/lightncandy/tests',
        'app/source/vendor/zordius/lightncandy/.gitignore',
        'app/source/vendor/zordius/lightncandy/.gitmodules',
        'app/source/vendor/zordius/lightncandy/.scrutinizer.yml',
        'app/source/vendor/zordius/lightncandy/.travis.yml',
        'app/source/vendor/zordius/lightncandy/HISTORY.md',
        'app/source/vendor/zordius/lightncandy/README.md',
        'app/source/vendor/zordius/lightncandy/UPGRADE.md',
        'app/source/vendor/zordius/lightncandy/composer.json',
        'app/source/vendor/zordius/lightncandy/example_debug.png',
        'app/source/vendor/zordius/lightncandy/phpunit.xml'
    ]);
}

////////////////////////////////////////////////////////////////////////////////////////////////////
// Build tasks
////////////////////////////////////////////////////////////////////////////////////////////////////

// Build fonts
gulp.task('build:fonts', 'Build font assets.', ['clean:fonts'], function() {
    buildFonts(fonts.source, fonts.target, fonts.base);
});

// Build images
gulp.task('build:images', 'Optimize images.', ['clean:images'], function() {
    buildImages(images.source, images.target);
});

// Prune vendor packages
gulp.task('build:prune', 'Prune unused files from vendor packages.', function() {
    pruneVendor();
});

// Build scripts
gulp.task('build:scripts', 'Build scripts.', ['jshint', 'clean:scripts'], function() {
    buildScripts(scripts.source, scripts.target);
});

// Build styles
gulp.task('build:styles', 'Build styles.', ['clean:styles'], function() {
    buildStyles(styles.source, styles.target);
});

// Build all
gulp.task('build', 'Run all build tasks.', [
    'build:fonts',
    'build:images',
    'build:prune',
    'build:scripts',
    'build:styles'
]);

////////////////////////////////////////////////////////////////////////////////////////////////////
// Clean tasks
////////////////////////////////////////////////////////////////////////////////////////////////////

// Clean fonts
gulp.task('clean:fonts', 'Delete generated fonts.', function() {
    return del(fonts.target);
});

// Clean images
gulp.task('clean:images', 'Delete generated images.', function() {
    return del(images.target);
});

// Clean scripts
gulp.task('clean:scripts', 'Delete generated scripts.', function() {
    return del(scripts.target);
});

// Clean styles
gulp.task('clean:styles', 'Delete generated styles.', function() {
    return del(styles.target);
});

// Clean all
gulp.task('clean', 'Clean up generated files.', [
    'clean:fonts',
    'clean:images',
    'clean:scripts',
    'clean:styles'
], function() {
    return del(assets);
});

////////////////////////////////////////////////////////////////////////////////////////////////////
// Release tasks
////////////////////////////////////////////////////////////////////////////////////////////////////

// Generate a release
gulp.task('release:make', 'Generate a release.', function() {
    var config = require(path.join(__dirname, 'package.json')),
        dist = path.join(__dirname, 'dist'),
        target = path.join(dist, 'postleaf-' + config.version);

    // Delete the target directory if it exists
    del.sync(target);

    // Create dist directory
    fs.mkdirsSync(dist);

    // Copy app/ to dist/postleaf-<version>/
    fs.copySync(path.join(__dirname, 'app'), target);

    // Copy license and installation instructions
    fs.copySync(path.join(__dirname, 'LICENSE.md'), path.join(target, 'LICENSE.md'));
    fs.copySync(path.join(__dirname, 'INSTALL.md'), path.join(target, 'INSTALL.md'));

    // Inject version number into runtime.php
    try {
        fs.writeFileSync(
            path.join(target, 'source/runtime.php'),
            fs.readFileSync(path.join(target, 'source/runtime.php'))
                .toString()
                .replace('{{version}}', config.version)
        );
    } catch(err) {
        return console.error(err);
    }

    // Delete .htaccess, database.php
    del.sync(path.join(target, '.htaccess'));
    del.sync(path.join(target, 'database.php'));

    // Empty backups, content/cache, content/themes, content/uploads
    del.sync(path.join(target, 'backups/*'));
    del.sync(path.join(target, 'content/cache/*'));
    del.sync(path.join(target, 'content/themes/*'));
    del.sync(path.join(target, 'content/uploads/*'));

    // Prune source/images, source/scripts, and source/styles
    del.sync(path.join(target, 'source/images/**'));
    del.sync(path.join(target, 'source/scripts/**'));
    del.sync(path.join(target, 'source/styles/**'));

    // Little message to celebrate
    console.log(
        '\nPostleaf ' + config.version + ' has been released! 🎉\n\n' +
        'Location: ' + target + '\n'
    );
});

// Clean releases
gulp.task('release:clean', 'Delete all generated releases.', function() {
    return del(path.join(__dirname, 'dist'));
});

////////////////////////////////////////////////////////////////////////////////////////////////////
// Other tasks
////////////////////////////////////////////////////////////////////////////////////////////////////

// JSHint
gulp.task('jshint', 'Lint source scripts with JSHint.', function() {
    lintScripts(scripts.source);
});

// Watch for changes
gulp.task('watch', 'Watch for script and style changes.', function() {
    // Watch fonts
    gulp.src(fonts.source)
        .pipe(watch(fonts.source))
            .on('add', (file) => buildFonts(file, fonts.target))
            .on('change', (file) => buildFonts(file, fonts.target));

    // Watch images
    gulp.src(images.source)
        .pipe(watch(images.source))
            .on('add', (file) => buildImages(file, images.target))
            .on('change', (file) => buildImages(file, images.target));

    // Watch scripts
    gulp.src(scripts.source)
        .pipe(watch(scripts.source))
            .on('add', (file) => {
                lintScripts(file);
                buildScripts(file, scripts.target);
            })
            .on('change', (file) => {
                lintScripts(file);
                buildScripts(file, scripts.target)
            });

    // Watch script modules (compile into lib.min.js)
    gulp.src(scripts.modules)
        .pipe(watch(scripts.modules))
            .on('add', (file) => {
                buildScripts(scripts.source, scripts.target);
            })
            .on('change', (file) => {
                buildScripts(scripts.source, scripts.target);
            });

    // Watch styles
    gulp.src(styles.source)
        .pipe(watch(styles.source))
            // Recompile all styles since changes to _partials.scss won't
            // compile on their own.
            .on('add', (file) => buildStyles(styles.source, styles.target))
            .on('change', (file) => buildStyles(styles.source, styles.target));
});

// Default
gulp.task('default', 'Run the default task.', ['watch']);