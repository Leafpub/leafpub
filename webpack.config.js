// webpack.config.js

var Encore = require('@symfony/webpack-encore');
var path = require('path');
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const CopyWebpackPlugin = require("copy-webpack-plugin");
const webpack = require("webpack");
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');
const SWPrecacheWebpackPlugin = require('sw-precache-webpack-plugin');

const extractLess = new ExtractTextPlugin({
    filename: "[name].[hash].css"
    //disable: process.env.NODE_ENV === "development"
});

const prodMode = new webpack.DefinePlugin({
    'process.env': {
        NODE_ENV: '"production"'
    }
});

const preCache = new SWPrecacheWebpackPlugin({
                    cacheId: 'leafpub-admin',
                    filename: 'service-worker.js',
                    staticFileGlobs: ['assets/**/*.{js,html,css}'],
                    minify: true,
                    stripPrefix: 'assets/'
                });
Encore
    //#####################################################################################################
    // Settings
    //####################################################################################################
    .enableBuildNotifications(true, function(options) {
             options.title = 'Leafpub build process';
             options.alwaysNotify = true;
             options.contentImage = path.join(__dirname, '/app/source/images/logo-color.png')
    })

    // directory where all compiled assets will be stored
    .setOutputPath('app/assets')

    // what's the public path to this directory (relative to your project's document root dir)
    .setPublicPath('/assets')

    // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()

    .enableSourceMaps(!Encore.isProduction())

    // create hashed filenames (e.g. app.abc123.css)
    .enableVersioning()

    // allow legacy applications to use $/jQuery as a global variable
    .autoProvidejQuery()

    .addPlugin(new CopyWebpackPlugin([
            {
                from: path.join(__dirname, '/app/source/images'), to: path.join(__dirname, 'app/source/assets/img')
            },/*
            {
                from: path.join(__dirname, '/app/source/fonts'), to: path.join(__dirname, 'app/source/assets/fonts')
            }
            */
        ],
        {
            copyUnmodified: true
        }
    ))

    .addPlugin(extractLess)

    .addPlugin(
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /de/)
    )



    //#####################################################################################################
    // Loader
    //####################################################################################################
    .addLoader({
        test: /\.scss$|\.sass$/,
        use: extractLess.extract({
            use: [
                {
                    loader: 'css-loader'
                },
                {
                    loader: 'resolve-url-loader', options: {
                        debug: !Encore.isProduction(),
                        root: './app/source/styles/'
                    }
                },
                {
                    loader: 'postcss-loader', // Run post css actions
                    options: {
                        plugins: function () { // post css plugins, can be exported to postcss.config.js
                            return [
                                require('precss'),
                                require('autoprefixer')
                            ];
                        },
                        sourceMap: true
                    }
                },
                {
                    loader: "sass-loader", options: {
                        sourceMap: true
                    }
                }
            ],
            // use style-loader in development
            fallback: "style-loader"
        })
    })

    .addLoader({
        // Ask webpack to check: If this file ends with .vue, then apply some transforms
        test: /\.vue$/,
        // don't transform node_modules folder (which don't need to be compiled)
        exclude: /(node_modules|bower_components)/,
        // Transform it with vue
        use: [
            {
                loader: 'vue-loader',
                options: {
                    loaders: {
                        'scss': 'vue-style-loader!css-loader!sass-loader',
                        'sass': 'vue-style-loader!css-loader!sass-loader?indentedSyntax',
                    }
                }
            }
        ],
    })

    .autoProvideVariables({
        Popper: ['popper.js', 'default']
    })

    //#####################################################################################################
    // Entries
    //####################################################################################################
    .addEntry('dashboard', [
        './app/source/scripts/dashboard.js',
        './app/source/styles/dashboard.scss',
        //'./node_modules/gridstack/src/gridstack.scss',
        //'./node_modules/gridstack/src/gridstack.js'
    ])

    .addEntry('editor', [
        './app/source/scripts/editor.js',
        './app/source/styles/editor.scss',
    ])

    .addEntry('import', [
        './app/source/scripts/import.js',
        './app/source/styles/import.scss',
    ])

    .addEntry('login', [
        './app/source/scripts/login.js',
        './app/source/styles/login.scss',
    ])

    .addEntry('navigation', [
        './app/source/scripts/navigation.js',
        './app/source/styles/navigation.scss',
    ])

    .addEntry('plugins', [
        './app/source/scripts/plugins.js',
        './app/source/styles/plugins.scss',
    ])

    .addEntry('posts.edit', [
        './app/source/scripts/posts.edit.js',
        './app/source/styles/posts.edit.scss',
    ])

    .addEntry('posts', [
        './app/source/scripts/posts.js',
        './app/source/styles/posts.scss',
    ])

    .addEntry('settings', [
        './app/source/scripts/settings.js',
        './app/source/styles/settings.scss',
    ])

    .addEntry('tags.edit', [
        './app/source/scripts/tags.edit.js',
        './app/source/styles/tags.edit.scss',
    ])

    .addEntry('tags', [
        './app/source/scripts/tags.js',
        './app/source/styles/tags.scss',
    ])

    .addEntry('update', [
        './app/source/scripts/update.js',
        './app/source/styles/update.scss',
    ])

    .addEntry('uploads', [
        './app/source/scripts/uploads.js',
        './app/source/styles/uploads.scss',
    ])

    .addEntry('users.edit', [
        './app/source/scripts/users.edit.js',
        './app/source/styles/users.edit.scss',
    ])

    .addEntry('users', [
        './app/source/scripts/users.js',
        './app/source/styles/users.scss',
    ])

    .addEntry('vue', [
        "./node_modules/vue/dist/vue.js",
        "./node_modules/vuex/dist/vuex.js"
    ])

    .addEntry('lib', [
        './app/source/styles/lib.scss',
        './node_modules/jquery/dist/jquery.js',
        './app/source/scripts/modules/jquery-ui.min.js',
        './node_modules/tether/dist/js/tether.min.js',
        './node_modules/popper.js/dist/umd/popper.js',
        './node_modules/bootstrap/dist/js/bootstrap.bundle.js',
        './node_modules/nanobar/nanobar.min.js',
        './node_modules/selectize/dist/js/standalone/selectize.min.js',
        './node_modules/js-cookie/src/js.cookie.js',
        './node_modules/showdown/dist/showdown.min.js',
        './node_modules/sortablejs/Sortable.min.js',
        './node_modules/velocity-animate/velocity.min.js',
        './node_modules/velocity-animate/velocity.ui.min.js',
        './node_modules/@claviska/jquery-alertable/jquery.alertable.js',
        './node_modules/@claviska/jquery-offscreen/jquery.offscreen.js',
        './node_modules/@claviska/jquery-selectable/jquery.selectable.js',
        './node_modules/lodash/lodash.min.js',
        './node_modules/slugify/index.js',
        './app/source/scripts/modules/leafpub.js',
        './app/source/scripts/modules/locater.js',
        './app/source/scripts/modules/reauthenticate.js',
        './app/source/scripts/modules/shared.js'
    ])

    .addStyleEntry('admin-toolbar', [
        './app/source/styles/admin-toolbar.scss'
    ])
;

if (Encore.isProduction()){
    Encore.addPlugin(prodMode);
    Encore.addPlugin(preCache);
}

var webPackConfig = Encore.getWebpackConfig();
webPackConfig.resolve.extensions.push('.json');
webPackConfig.resolve.extensions.push('.vue');

webPackConfig.resolve.alias = {
    "datatables.net": "datatables.net/js/jquery.dataTables.js",
    "datetime": 'datatables.net-plugins/sorting/datetime-moment.js',
    //'vue': 'vue/dist/vue.js',
    'vue$': 'vue/dist/vue.esm.js',
};

webPackConfig.watchOptions = {
    poll: true
};

// Remove the old version first
webPackConfig.plugins = webPackConfig.plugins.filter(
    plugin => !(plugin instanceof webpack.optimize.UglifyJsPlugin)
);

// Add the new one
if (Encore.isProduction()) {
    webPackConfig.plugins.push(new UglifyJsPlugin());
}

// export the final configuration
module.exports = webPackConfig;