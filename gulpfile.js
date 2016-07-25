var
	gulp = require('gulp'),
	source = require('vinyl-source-stream'),
	buffer = require('vinyl-buffer'),
	jshint = require('gulp-jshint'),
	header = require('gulp-header'),
	rename = require('gulp-rename'),
	eol = require('gulp-eol'),
	gutil = require('gulp-util'),
	webpack = require('webpack'),
	path = require('path'),
	
	
	crlf = '\n',
	jshintStart = '/* jshint ignore:end */' + crlf + '\'use strict\';' + crlf + crlf,
	jshintEnd = crlf + crlf + '/* jshint ignore:start */',
	cfg = {
		license:
crlf +
'/*!' + crlf +
' * Copyright 2004-2015, AfterLogic Corp.' + crlf +
' * Licensed under AGPLv3 license or AfterLogic license' + crlf +
' * if commerical version of the product was purchased.' + crlf +
' * See the LICENSE file for a full license statement.' + crlf +
' */' + crlf + crlf,
		paths: {},
		watch: [],
		all: [],
		min: [],
		summary: {
			verbose: true,
			reasonCol: 'cyan,bold',
			codeCol: 'green'
		}
	}
;

cfg.paths.js = {
	app: {
		dest: './static/js/',
		name: 'app.js',
		min: 'app.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/CoreClient/js/entry.js"
		]
	},
	mobile: {
		dest: './static/js/',
		name: 'app-mobile.js',
		min: 'app-mobile.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/CoreClient/js/entry-mobile.js"
		]
	},
	message_newtab: {
		dest: './static/js/',
		name: 'app-message-newtab.js',
		min: 'app-message-newtab.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/MailClient/js/entry-newtab.js"
		]
	},
	adminpanel: {
		dest: './static/js/',
		name: 'app-adminpanel.js',
		min: 'app-adminpanel.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/AdminPanelClient/js/entry.js"
		]
	},
	files_pub: {
		dest: './static/js/',
		name: 'app-files-pub.js',
		min: 'app-files-pub.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/FilesClient/js/entry-pub.js"
		]
	},
	calendar_pub: {
		dest: './static/js/',
		name: 'app-calendar-pub.js',
		min: 'app-calendar-pub.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/CalendarClient/js/entry-pub.js"
		]
	},
	helpdesk_ext: {
		dest: './static/js/',
		name: 'app-helpdesk.js',
		min: 'app-helpdesk.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/HelpDeskClient/js/entry-ext.js"
		]
	},
	custom: {
		dest: './static/js/',
		name: 'app.js',
		min: 'app.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"entry.js"
		]
	}
};

function jsTask(sName, oData)
{
	if (oData && oData.src)
	{
		var 
			oWebPackConfig = {
				entry: oData.src,
				output: {
					path: oData.dest,
					filename: oData.name
				},
				resolveLoader: {
					alias: {
						"replace-module-names-loader": path.join(__dirname, "./gulp-tasks/replace-module-names-loader.js")
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
			compileCallback = function (sTaskName, err, stats) {
				if (err)
				{ 
					throw new gutil.PluginError(sTaskName, err);
				}
				gutil.log(sTaskName, stats.toString({
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
		
		if (sName !== 'custom')
		{
			cfg.all.push('js:' + sName);
		}
		
		gulp.task('js:' + sName, function() {
			webpack(oWebPackConfig).run(function (err, stats) {
				compileCallback.call(null, 'js:' + sName, err, stats);
			});
		});
		
		if (sName !== 'custom' && oData.watch)
		{
			cfg.watch.push('js:' + sName + ':watch');
			gulp.task('js:' + sName + ':watch', function() {
				webpack(oWebPackConfig).watch({ // watch options:
					aggregateTimeout: 300, // wait so long for more changes
					poll: true // use polling instead of native watchers
					// pass a number to set the polling interval
				}, function (err, stats) {
					compileCallback.call(null, 'js:' + sName + ':watch', err, stats);
				});
			});
		}

		if (sName !== 'custom' && oData.min)
		{
			cfg.min.push('js:' + sName + ':min');
			gulp.task('js:' + sName + ':min', function() {
				oWebPackConfig.output.filename = oData.min;
				oWebPackConfig.plugins = [
					new webpack.optimize.UglifyJsPlugin({
						compress: {
							warnings: false,
							drop_console: true,
							unsafe: true
						}
					})
				];
				delete oWebPackConfig.devtool;
				
				webpack(oWebPackConfig).run(function (err, stats) {
					compileCallback.call(null, 'js:' + sName + ':min', err, stats);
				});
			});
		}
	}
}

gulp.task('lint', function() {
  return gulp.src('./js/**/*.js')
    .pipe(jshint())
    .pipe(jshint.reporter('default'));
});

for (name in cfg.paths.js)
{
	if (cfg.paths.js.hasOwnProperty(name))
	{
		jsTask(name, cfg.paths.js[name]);
	}
}

gulp.task('all', cfg.all);
gulp.task('min', cfg.min);
gulp.task('w', cfg.watch);

gulp.task('default', ['js:app:watch']);

gulp.task('custom', ['js:custom']);

gulp.task('files', ['js:app:watch', 'js:files_pub:watch']);

gulp.task('cal', ['js:app:watch', 'js:calendar_pub:watch']);

gulp.task('helpdesk', ['js:app:watch', 'js:helpdesk_ext:watch']);

gulp.task('msg', ['js:app:watch', 'js:message_newtab:watch']);

gulp.task('mob', ['js:app:watch', 'js:mobile:watch']);

gulp.task('adm', ['js:adminpanel:watch']);

require('./gulp-tasks/langs.js');

require('./gulp-tasks/styles.js');

//require('./gulp-tasks/html.js');

//require('./gulp-tasks/convert-langs.js');
