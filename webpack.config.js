import path from 'path';
import {fileURLToPath} from 'url';
import webpack from 'webpack';
import BrowserSyncPlugin from 'browser-sync-webpack-plugin';
import EslintFriendlyFormatter from 'eslint-friendly-formatter';
import AutoPrefixer from 'autoprefixer';
import TerserPlugin from 'terser-webpack-plugin';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';
import { VueLoaderPlugin } from 'vue-loader';
import BuildHashPlugin from 'build-hash-webpack-plugin';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const isProduction = process.env.NODE_ENV === 'production';
const isDevelopment = process.env.NODE_ENV === 'development';
var files = getFilePaths();

const webpackConfig = {
    mode: process.env.NODE_ENV,
    context: files.context.base,
    entry: files.js.src,
    output: getOutput(),
    module: getModule(),
    devtool: getDevtool(),
    devServer: getDevServer(),
    resolve: getResolve(),
    performance: getPerformance(),
    plugins: getPlugins(),
    optimization: getOptimization()
}

addBrowserSyncPluginForDevelopment();
addHashFileForProductionCacheBusting();
addHotModuleReplacementPluginForDevelopment();
setWatchPollingForDevelopment();

function getFilePaths(){
    return {
        context: {
            base: path.resolve(__dirname, './'),
            build: path.resolve(__dirname, './public'),
            node_modules: path.resolve(__dirname, './node_modules'),
        },
        js: {
            src: path.resolve(__dirname, './resources/app/app.js'),
            build: isProduction && process.env.BUILD_TARGET !== 'ios' ? 'js/app.[hash].js' : 'js/app.js',
        },
        css: {
            src: path.resolve(__dirname, './resources/app/app.scss'),
            build: isProduction && process.env.BUILD_TARGET !== 'ios' ? 'css/app.[hash].css' : 'css/app.css',
        },
    }
}
function getOutput(){
    return {
        path: files.context.build,
        publicPath: '',
        filename: files.js.build,
    }
}
function getModule(){
    return {
        rules: [
            getJsRule(),
            getMainStyleFileRule(),
            getVueRule(),
            getCssRule(),
            getSassRule(),
            getHtmlRule(),
            getAssetRule(),
            // getImageRule(),
            // getFontRule(),
            getVueEslintRule(),
        ]
    }

    function getJsRule(){
        return {
            test: /\.jsx?$/,
            exclude: /(node_modules|bower_components)/,
            use: [
                {
                    loader: 'babel-loader',
                    options:getBabelConfig(),
                },
                {
                    loader: 'eslint-loader',
                    options: {
                      formatter: EslintFriendlyFormatter,
                    },
                }
            ],
            resolve: {
                fullySpecified: false,
            }
        }
    }
    function getBabelConfig(){
        return {
            cacheDirectory: true,
            presets: [
                [
                    '@babel/preset-env',
                    {
                        modules: false,
                        forceAllTransforms: true,
                        useBuiltIns: 'entry',
                        corejs: '3.6',
                        targets: {
                            browsers: ['> 1%']
                        }
                    },
                ]
            ],
        }
    }
    function getCssRule(){
        return {
            test: /\.css$/,
            exclude: [],
            rules: [{
                loader: 'style-loader'
            }, {
                loader: 'css-loader'
            }]
        }
    }
    function getSassRule(){
        return {
            test: /\.s[ac]ss$/,
            exclude: [files.css.src],
            use: [
                'style-loader',
                {
                    loader: 'css-loader',
                    options: {
                        url: false,
                        sourceMap: true,
                        importLoaders: 1,
                    }
                },
                {
                    loader: 'resolve-url-loader',
                    options: {
                        sourceMap: true,
                    }
                },
                {
                    loader: 'sass-loader',
                    options: {
                        sourceMap: true,
                        sassOptions: {
                            outputStyle: 'expanded',
                            precision: 8,
                            sourceMapRoot: '/'
                        }
                    }
                }
            ]
        }
    }
    function getHtmlRule(){
        return {
            test: /\.html$/,
            use: ['html-loader']
        }
    }
    function getAssetRule(){
        return {
            test: /\.(png|jpe?g|gif|woff(2)?|ttf|eot|svg)(\?v=\d+\.\d+\.\d+)?$/,
            type: 'asset/resource',
            generator: {
                publicPath: '/cdn-assets/',
                outputPath: 'cdn-assets/',
            }
         };
    }
    function getImageRule(){
        return {
            test: /\.(png|jpe?g|gif)$/,
            rules: [
                {
                    loader: 'file-loader',
                    options: {
                        name: path => {
                            if (! /node_modules|bower_components/.test(path)) {
                                return 'images/[name].[ext]?[hash]';
                            }

                            return 'images/vendor/' + path
                                .replace(/\\/g, '/')
                                .replace(
                                    /((.*(node_modules|bower_components))|images|image|img|assets)\//g, ''
                                ) + '?[hash]';
                        },
                        publicPath: '/',
                        esModule: false
                    }
                },
                {
                    loader: 'img-loader',
                    options: {
                        plugins: [
                        ]
                    }
                }
            ]
        }
    }
    function getFontRule(){
        return {
            test: /\.(woff(2)?|ttf|eot|svg)(\?v=\d+\.\d+\.\d+)?$/,
            loader: 'file-loader',
            options: {
                name: function(path){

                    var isNotAPackageFile = ! /node_modules|bower_components/.test(path);

                    if (isNotAPackageFile) {
                        return 'fonts/[name].[ext]?[hash]';
                    }

                    return 'fonts/vendor/' + path
                        .replace(/\\/g, '/')
                        .replace(
                            /((.*(node_modules|bower_components))|fonts|font|assets)\//g, ''
                        ) + '?[hash]';
                },
                publicPath: '/',
                esModule: false
            }
        }
    }
    function getMainStyleFileRule(){
        return {
            test: files.css.src,
            use: [
                {
                    loader: MiniCssExtractPlugin.loader,
                },
                {
                    loader: 'css-loader',
                    options: {
                        url: false,
                        sourceMap: true,
                        importLoaders: 1
                    }
                },
                {
                    loader: 'postcss-loader',
                    options: {
                        sourceMap: true,
                        postcssOptions: {
                            ident: 'postcss',
                            plugins: [
                                AutoPrefixer(),
                            ]
                        }
                    }
                },
                {
                    loader: 'resolve-url-loader',
                    options: {
                        sourceMap: true,
                    }
                },
                {
                    loader: 'sass-loader',
                    options: {
                        sassOptions: {
                            precision: 8,
                            outputStyle: 'expanded',
                            sourceMap: true
                        }
                    }
                }
            ]
        }
    }
    function getVueEslintRule(){
        return {
            enforce: 'pre',
            test: /\.vue$/,
            loader: 'eslint-loader',
            exclude: /node_modules/,
            options: {
                formatter: EslintFriendlyFormatter,
            },
        }
    }
    function getVueRule(){
        return {
            test: /\.vue$/,
            loader: 'vue-loader',
            exclude: /bower_components/,
            options: {
                loaders: {
                    js: {
                        loader: 'babel-loader!eslint-loader',
                        options: getBabelConfig(),
                    }
                },
                postcss: [],
                preLoaders: {},
                postLoaders: {}
            }
        }
    }
}
function getDevtool(){
    if(isProduction){
        return 'source-map';
    } else {
        return 'inline-source-map';
    }
}
function getDevServer(){
    return {
        headers: {
            'Access-Control-Allow-Origin': '*'
        },
        contentBase: files.context.build,
        historyApiFallback: true,
        noInfo: true,
        compress: true,
        quiet: true,
        stats: 'minimal'
    }
}
function getResolve(){
    return {
        extensions: ['*', '.js', '.jsx', '.vue'],
        alias: {
            'vue$': 'vue/dist/vue.common.js',
            vue_root: path.resolve(__dirname, './resources/app')
        }
    }
}
function getPerformance(){
    return {
        hints: false,
    }
}
function getPlugins(){
    return [
        defineProcessEnv(),
        new VueLoaderPlugin(),
        new MiniCssExtractPlugin({
            filename: files.css.build,
        }),
        getLoaderOptions()
    ]

    function getLoaderOptions(){
        return new webpack.LoaderOptionsPlugin({
            minimize: isProduction,
            options: {
                context: __dirname,
                output: { path: './' }
            }
        })
    }
    function defineProcessEnv(){
        if(isProduction){
            return new webpack.DefinePlugin({
                'process.env': {
                    NODE_ENV: '"production"'
                }
            })
        } else if(process.env.NODE_ENV === 'development'){
            return new webpack.DefinePlugin({
                'process.env': {
                    NODE_ENV: '"development"'
                }
            })
        }
    }
}

function addBrowserSyncPluginForDevelopment(){
    if (!isProduction) {
        webpackConfig.plugins = (webpackConfig.plugins || []).concat([
            new BrowserSyncPlugin(
                {
                    port: 3000,
                    proxy: '0.0.0.0',
                    open: false,
                    host: 'localhost',
                    files: [
                        'app/**/*.php',
                        'resources/views/**/*.php',
                        'public/js/**/*.js',
                        'public/css/**/*.css',
                    ],
                    ghostmode: false
                },
                { reload: true }
            )
        ]);
    }
}

function addHashFileForProductionCacheBusting(){
    if (isProduction && process.env.BUILD_TARGET !== 'ios') {
        var buildPath = files.context.build;
        var hashFileLocation = path.resolve(buildPath, './hash.json');
        webpackConfig.plugins = (webpackConfig.plugins || []).concat([
            new BuildHashPlugin({filename: hashFileLocation})
        ]);
    }
}

function addHotModuleReplacementPluginForDevelopment(){
    if (isDevelopment) {
        webpackConfig.plugins = (webpackConfig.plugins || []).concat([
            new webpack.HotModuleReplacementPlugin()
        ]);
    }
}

function setWatchPollingForDevelopment(){
    if (isDevelopment) {
        webpackConfig.watchOptions = {
            poll: 2000
        };
    }
}

function getOptimization(){
    const optimizationOptions = {
        minimize: isProduction
    };
    if(isProduction){
        optimizationOptions.minimizer = [
            new TerserPlugin({
                terserOptions: {
                    sourceMap: true,
                }
            })
        ];
    }
    return optimizationOptions;
}

export default webpackConfig;