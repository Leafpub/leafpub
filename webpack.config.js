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
        Popper: ['popper.js', 'default'],
        number: 'jquery-number.js'
    })

    //#####################################################################################################
    // Entries
    //####################################################################################################
    .addEntry('vue', [
        "./node_modules/vue/dist/vue.js",
        "./node_modules/vuex/dist/vuex.js"
    ])

    .addStyleEntry(
        'lib',
        [
            './app/source/styles/lib.scss'
        ]
    )
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