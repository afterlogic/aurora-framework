var
    _ = require('underscore'),
    argv = require('./argv.js'),
    fileExists = require('file-exists'),
    gulp = require('gulp'),
    gutil = require('gulp-util'),
    concat = require('gulp-concat-util'),
    plumber = require('gulp-plumber'),
    fs = require('fs'),
    gulpWebpack = require('webpack-stream'),
    path = require('path'),

    sTenantName = argv.getParameter('--tenant'),
    sOutputName = argv.getParameter('--output'), /* app, app-mobile, app-message-newtab, app-adminpanel, app-files-pub, app-calendar-pub, app-helpdesk*/
    aModulesNames = argv.getModules(),
    // aModulesWatchPaths = [],
    sPath = sTenantName ? './tenants/' + sTenantName + '/static/js/' : './static/js/',
    crlf = '\n'
;

// aModulesNames.forEach(function (sModuleName) {
    // if (fileExists('./modules/' + sModuleName + '/js/*.js')) {
        // aModulesWatchPaths.push('./modules/' + sModuleName + '/js/**/*.js');
    // }
// });

function GetModuleName(sFilePath) {
    return sFilePath.replace(/.*modules[\\/](.*?)[\\/]js.*/, "$1");
}

var 
	aModules = _.map(aModulesNames, function (sModuleName) {
		var
			sFilePath = './modules/' + sModuleName + '/js/manager.js',
			sTenantFilePath = './tenants/' + sTenantName + '/modules/' + sModuleName + '/js/manager.js',
			sFoundedFilePath = ''
		;

		if (fileExists(sTenantFilePath)) {
			sFoundedFilePath = sTenantFilePath;
		} else if (fileExists(sFilePath)) {
			sFoundedFilePath = sFilePath;
		}

		return sFoundedFilePath;
	}),
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
				}
			]
		}
	},
	compileCallback = function (sName, err, stats) {
		if (err) {
			throw new gutil.PluginError(sName, err);
		}
		gutil.log(sName, stats.toString({
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
	
    gulp.src(aModules)
		.pipe(plumber({
            errorHandler: function (err) {
                console.log(err.toString());
                gutil.beep();
                this.emit('end');
            }
        }))
        .pipe(concat('_' + sName + '-entry.js', {
            sep: ',' + crlf,
            process: function (sSrc, sFilePath) {
                var sModuleName = GetModuleName(sFilePath);
                return "\t\t\t'" + sModuleName + "': " + "require(" + "'" + 'modules/' + sModuleName + '/js/manager.js' + "')";
            }
        }))
        .pipe(concat.header("'use strict';" + crlf +
            "var $ = require('jquery');" + crlf +
            "$('body').ready(function () {" + crlf +
            "\t" + "//for " + sTaskName + crlf +
            "\t" + "var" + crlf +
            "\t\t" + "oAvaliableModules = {" + crlf
        ))
        .pipe(concat.footer(crlf + "\t\t}," + crlf +
            "\t\t" + "ModulesManager = require('modules/CoreWebclient/js/ModulesManager.js')," + crlf +
            "\t\t" + "App = require('modules/CoreWebclient/js/App.js')," + crlf +
            "\t\t" + "bSwitchingToMobile = App.checkMobile()" + crlf +
            "\t" + ";" + crlf +
            "\t" + "if (!bSwitchingToMobile)" + crlf +
            "\t" + "{" + crlf +
            "\t\t" + "ModulesManager.init(oAvaliableModules);" + crlf +
            "\t\t" + "App.init();" + crlf +
            "\t" + "}" + crlf +
            "});" + crlf
        ))
		.pipe(gulp.dest(sPath))
		.pipe(gulpWebpack(oWebPackConfig, null, function (err, stats) {
			compileCallback.call(null, sTaskName, err, stats);
		}))
		// .pipe(concat(oData.name)) //break file saving in watch mode
		.pipe(plumber.stop())
        .pipe(gulp.dest(sPath))
	;
}

gulp.task('js1:build', function () {
	jsTask('js1:build', sOutputName, _.defaults({
		'output':  {
			'filename': sOutputName + '.js'
		}
	}, oWebPackConfig));
});

gulp.task('js1:watch', function () {
	jsTask('js1:watch', sOutputName, _.defaults({
		'watch': true,
		'aggregateTimeout': 300,
		'poll': true,
		'output':  {
			'filename': sOutputName + '.js'
		}
	}, oWebPackConfig));
});

gulp.task('js1:min', function () {
	jsTask('js1:min', sOutputName, _.defaults({
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
			'filename': sOutputName + '.min.js'
		}
	}, oWebPackConfig));
});

module.exports = {};
