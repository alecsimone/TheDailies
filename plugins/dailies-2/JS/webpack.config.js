//webpack.config.js
//For the Dailies 2 Plugin
var webpack = require('webpack');
var ExtractTextPlugin = require('extract-text-webpack-plugin');
var OptimizeCssAssetsPlugin = require('optimize-css-assets-webpack-plugin');

var version = '-v2.351';

module.exports = {
	devtool: 'cheap-module-source-map',
    entry: {
    	main: "./Entries/main-entry.js",
    	live: "./Entries/live-entry.js",
    	submit: "./Entries/submit-entry.js",
    	livevotingmachine: "./Entries/live-voting-machine-entry.js",
    	livevotebar: "./Entries/live-votebar-entry.js",
    	weed: "./Entries/weed-entry.js",
    	hopefuls: "./Entries/hopefuls-entry.js",
    	multiplewinnersdiscussion: "./Entries/multipleWinnersDiscussion-entry.js",
    	global: "./Entries/global-entry.js", //Global must be kept last because it contains all the CSS files to be combined
    },
	output: {
		path: __dirname + "/Bundles",
		filename: "[name]-bundle" + version + ".js"
	},
	watch: true,
	module: {
		loaders: [
			{
				test: /\.scss$/, 
				loader: ExtractTextPlugin.extract({
					fallback: 'style-loader',
					use: ['css-loader', 'sass-loader'],
				}), 
				exclude: /node_modules/
			},
			{
                test: /\.jsx?$/,
                loaders: 'babel-loader',
                exclude: /node_modules/,
                query: {
                	presets: ['es2015', 'react']
                }
            },
            {
                test: /\.js$/,
                loaders: 'babel-loader',
                exclude: /node_modules/,
                query: {
                	presets: ['es2015']
                }
            }
		]
	},
		plugins: [
			new ExtractTextPlugin("../style" + version + ".css"),
			new OptimizeCssAssetsPlugin(),
			// Turn the following lines off for dev, on for prod
			new webpack.DefinePlugin({
				'process.env': {
					'NODE_ENV': JSON.stringify('production')
				}
			}),
			new webpack.optimize.AggressiveMergingPlugin(),
			new webpack.optimize.UglifyJsPlugin(),
		],
};