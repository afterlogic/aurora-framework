var
    _ = require('underscore'),
    argv = require('./argv.js'),
	fs = require('fs'),
    gulp = require('gulp'),
    gutil = require('gulp-util'),
    concat = require('gulp-concat-util'),
    plumber = require('gulp-plumber'),
	webpack = require('webpack'),
    gulpWebpack = require('webpack-stream'),
    path = require('path'),

    sTenantName = argv.getParameter('--tenant'),
    sOutputName = argv.getParameter('--output'), /* app, app-mobile, app-message-newtab, app-adminpanel, app-files-pub, app-calendar-pub, app-helpdesk*/
    aModulesNames = argv.getModules(),
    sPath = sTenantName ? './tenants/' + sTenantName + '/static/js/' : './static/js/',
    crlf = '\n'
;

function GetModuleName(sFilePath) {
    return sFilePath.replace(/.*modules[\\/](.*?)[\\/]js.*/, "$1");
}

var 
	aModules = _.compact(_.map(aModulesNames, function (sModuleName) {
		var
			sFilePath = './modules/' + sModuleName + '/js/manager.js',
			sTenantFilePath = './tenants/' + sTenantName + '/modules/' + sModuleName + '/js/manager.js',
			sFoundedFilePath = ''
		;

		if (fs.existsSync(sTenantFilePath))
		{
			sFoundedFilePath = sTenantFilePath;
		}
		else if (fs.existsSync(sFilePath))
		{
			sFoundedFilePath = sFilePath;
		}

		return sFoundedFilePath;
	})),
	oWebPackConfig = {
		resolveLoader: {
			alias: {
				"replace-module-names-loader": path.join(__dirname, "replace-module-names-loader.js")
			}
		},
		resolve: {
			root: [
				path.resolve('./')
			]
		},
		module: {
			loaders: [
				{
					test: /[\\\/]modernizr\.js$/,
					loader: "imports?this=>window!exports?window.Modernizr"
				},
				{
					test: /\.js$/,
					loader: 'replace-module-names-loader'
				},
				{
					test: /\.less$/,
					loader: "style-loader!css-loader!less-loader"
				}
			]
		}
	},
	compileCallback = function (err, stats) {
		if (err) {
			throw new gutil.PluginError(err);
		}
		gutil.log(stats.toString({
			colors: true,
			//context: true,
			hash: false,
			version: false,
			timings: true,
			assets: false,
			chunks: false,
			chunkModules: false,
			modules: false,
			children: false,
			cached: false,
			reasons: false,
			source: false,
			errorDetails: false,
			chunkOrigins: false
		}));
	}
;


function jsTask(sTaskName, sName, oWebPackConfig) {
	var
		bPublic = sName.indexOf('-pub') !== -1,
		sPublicInit = bPublic ? "\t\t" + "App.setPublic();" + crlf : ''
	;

    gulp.src(aModules)
    // gulp.src('./static/js/app-entry.js')
		.pipe(plumber({
            errorHandler: function (err) {
                console.log(err.toString());
                gutil.beep();
                this.emit('end');
            }
        }))
        .pipe(concat('_' + sName + '-entry.js', {
            sep: crlf,
            process: function (sSrc, sFilePath) {
                var sModuleName = GetModuleName(sFilePath);
                // return "\t\t\t'" + sModuleName + "': " + "require(" + "'" + 'modules/' + sModuleName + '/js/manager.js' + "')";
				
				return "\t\t"+"if (window.aAvaliableModules.indexOf('"+sModuleName+"') >= 0) {" + crlf +
					"\t\t\t"+"oAvaliableModules['"+sModuleName+"'] = new Promise(function(resolve, reject) {" + crlf +
						"\t\t\t\t"+"require.ensure([], function(require) {var oModule = require('modules/"+sModuleName+"/js/manager.js'); resolve(oModule); }, '"+sModuleName+"');" + crlf +
					"\t\t\t"+"});" + crlf +
				"\t\t"+"}";
            }
        }))
        .pipe(concat.header("'use strict';" + crlf +
            "var $ = require('jquery'), _ = require('underscore'), Promise = require('bluebird');" + crlf +
            "$('body').ready(function () {" + crlf +
            // "\t" + "//for " + sTaskName + crlf +
            // "\t" + "var" + crlf
            // "\t\t" + "oAvaliableModules = {" + crlf
            "\t" + "var oAvaliableModules = {};" + crlf +
            "\t" + "if (window.aAvaliableModules) {" + crlf
        ))
        .pipe(concat.footer(
			crlf + "\t}" + crlf +
		// crlf + "\t\t}," + crlf +
			
			"\t" + "Promise.all(_.values(oAvaliableModules)).then(function(aModules){" + crlf +
			"\t" + "var" + crlf +
            "\t\t" + "ModulesManager = require('modules/CoreWebclient/js/ModulesManager.js')," + crlf +
            "\t\t" + "App = require('modules/CoreWebclient/js/App.js')," + crlf +
            "\t\t" + "bSwitchingToMobile = App.checkMobile()" + crlf +
            "\t" + ";" + crlf +
            "\t" + "if (!bSwitchingToMobile)" + crlf +
            "\t" + "{" + crlf +
            // sPublicInit +
			"\t\t" + "if (window.isPublic) {" + crlf +
			"\t\t\t" + "App.setPublic();" + crlf +
			"\t\t" + "}" + crlf +
            "\t\t" + "ModulesManager.init(_.object(_.keys(oAvaliableModules), aModules));" + crlf +
            "\t\t" + "App.init();" + crlf +
            "\t" + "}" + crlf +
            "\t});" + crlf +
            "});" + crlf
        ))
		.pipe(gulp.dest(sPath))
		.pipe(gulpWebpack(oWebPackConfig, webpack, compileCallback))
		.pipe(plumber.stop())
        .pipe(gulp.dest(sPath))
	;
}

gulp.task('js:build1', function () {
	// console.log(__dirname + "/");
	console.log(path.resolve('./'));
	 webpack( _.defaults({
		'entry': './static/js/app-entry.js',
		'output':  {
			'path': './static/js1/',
			'filename': sOutputName + '.js',
			'chunkFilename': '[name].' + sOutputName + '.js',
			'publicPath': sPath,
			'pathinfo': true
		}
	}, oWebPackConfig), compileCallback);
});
gulp.task('js:build', function () {
	jsTask('js:build', sOutputName, _.defaults({
		'output':  {
			'filename': sOutputName + '.js',
			'chunkFilename': '[name].' + sOutputName + '.js',
			'publicPath': sPath,
			'pathinfo': true
		},
		'plugins': [
			new webpack.optimize.DedupePlugin()
		]
	}, oWebPackConfig));
});

gulp.task('js:watch', function () {
	jsTask('js:watch', sOutputName, _.defaults({
		'watch': true,
		'aggregateTimeout': 300,
		'poll': true,
		'output':  {
			'filename': sOutputName + '.js',
			'chunkFilename': '[name].' + sOutputName + '.js',
			'publicPath': sPath
		}
	}, oWebPackConfig));
});

gulp.task('js:min', function () {
	jsTask('js:min', sOutputName, _.defaults({
		'plugins': [
			new webpack.optimize.UglifyJsPlugin({
				compress: {
					warnings: false,
					drop_console: true,
					unsafe: true
				}
			})
		],
		'output':  {
			'filename': sOutputName + '.min.js',
			'chunkFilename': '[name].' + sOutputName + '.js',
			'publicPath': sPath
		}
	}, oWebPackConfig));
});

module.exports = {};
