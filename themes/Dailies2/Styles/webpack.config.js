//webpack.config.js
// For the Dailies 2 Theme
var webpack = require('webpack');
var ExtractTextPlugin = require('extract-text-webpack-plugin');
var OptimizeCssAssetsPlugin = require('optimize-css-assets-webpack-plugin');

var version = '-v2.349';

module.exports = {
	devtool: 'cheap-module-source-map',
	mode: "production",
    entry: {
    	global: "./webpack-entry.js",
    },
	output: {
		path: __dirname,
		filename: "[name]-bundle" + version + ".js"
	},
	watch: true,
	module: {
		rules: [
			{
				test: /\.scss$/, 
				loader: ExtractTextPlugin.extract({
					fallback: 'style-loader',
					use: ['css-loader', 'sass-loader'],
				}), 
				exclude: /node_modules/,
			},
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
	]
}