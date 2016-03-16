var
	browserify = require('browserify'),
	watchify = require('watchify'),
	gulp = require('gulp'),
	//reactify = require('reactify'),
	source = require('vinyl-source-stream'),
	buffer = require('vinyl-buffer'),
	jshint = require('gulp-jshint'),
	uglify = require('gulp-uglify'),
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
		summary: {
			verbose: true,
			reasonCol: 'cyan,bold',
			codeCol: 'green'
		},
		uglify: {
			mangle: true,
			compress: true,
			drop_console: true,
			preserveComments: 'some'
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
			"./core/js/entry.js"
		],
		webpack: true
	},
	mobile: {
		dest: './static/js/',
		name: 'app-mobile.js',
		min: 'app-mobile.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./core/js/entry-mobile.js"
		],
		webpack: true
	},
	message_newtab: {
		dest: './static/js/',
		name: 'app-message-newtab.js',
		min: 'app-message-newtab.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/Mail/js/entry-newtab.js"
		],
		webpack: true
	},
	adminpanel: {
		dest: './static/js/',
		name: 'app-adminpanel.js',
		min: 'app-adminpanel.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/AdminPanel/js/entry.js"
		],
		webpack: true
	},
	files_pub: {
		dest: './static/js/',
		name: 'app-files-pub.js',
		min: 'app-files-pub.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/Files/js/entry-pub.js"
		],
		webpack: true
	},
	calendar_pub: {
		dest: './static/js/',
		name: 'app-calendar-pub.js',
		min: 'app-calendar-pub.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/Calendar/js/entry-pub.js"
		],
		webpack: true
	},
	helpdesk_ext: {
		dest: './static/js/',
		name: 'app-helpdesk.js',
		min: 'app-helpdesk.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/HelpDesk/js/entry-ext.js"
		],
		webpack: true
	}
};

function jsTask(sName, oData)
{
	if (oData && oData.src)
	{
		if (oData.webpack)
		{
			gulp.task('js:' + sName, function() {
				var 
					compiler = webpack({
						//context: './', // исходная директория
						entry: oData.src, // файл для сборки, если несколько - указываем hash (entry name => filename)
						output: {
							path: oData.dest,
							filename: oData.name
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
								}
							]
						}
					}),
					compileCallback = function (err, stats) {
						if (err)
						{ 
							throw new gutil.PluginError('js:' + sName, err);
						}
						gutil.log('js:' + sName, stats.toString({
							colors: true,
							//context: true,
							hash: false,
							version: false,
							timings: false,
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
				//compiler.run(compileCallback);
				//Or
				compiler.watch({ // watch options:
					aggregateTimeout: 300, // wait so long for more changes
					poll: true // use polling instead of native watchers
					// pass a number to set the polling interval
				}, compileCallback);
			});
		}
		else
		{
			gulp.task('js:' + sName, function() {
				var b = browserify(oData.src, {
					//transform: [reactify],
					paths: ['./']
				});
				return b.bundle()
					.pipe(source(oData.name))
					.pipe(buffer())
					.pipe(header(((oData.afterlogic ? cfg.license : '') || '') + jshintEnd))
					.pipe(eol('\n', true))
					.pipe(gulp.dest(oData.dest));
			});
		}
		

		//if (oData.watch)
		//{
		//	gulp.task('js:' + sName + ':watch', function() {
		//		var bundler = watchify(browserify(oData.src));
				
		//		function rebundle() {
		//			return bundler.bundle()
		//				.pipe(source(oData.name))
		//				.pipe(buffer())
		//				.pipe(header(((oData.afterlogic ? cfg.license : '') || '') + jshintEnd))
		//				.pipe(eol('\n', true))
		//				.pipe(gulp.dest(oData.dest));
		//		}
		//		
		//		bundler.on('update', rebundle);
//
//				return rebundle();
//			});
//		}

//		if (oData.min)
//		{
//			gulp.task('js:' + sName + ':min', function() {
//				return gulp.src(oData.dest + oData.name)
//					.pipe(rename(oData.min))
//					.pipe(uglify(cfg.uglify))
//					.pipe(eol('\n', true))
//					.pipe(gulp.dest(oData.dest))
//					.on('error', gutil.log);
//			});
		//}
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

gulp.task('default', ['js:app']);

gulp.task('all', ['js:app', 'js:files_pub', 'js:calendar_pub', 'js:helpdesk_ext', 'js:message_newtab', 'js:mobile', 'js:adminpanel' ]);

gulp.task('files', ['js:app', 'js:files_pub']);

gulp.task('cal', ['js:app', 'js:calendar_pub']);

gulp.task('helpdesk', ['js:app', 'js:helpdesk_ext']);

gulp.task('msg', ['js:app', 'js:message_newtab']);

gulp.task('mob', ['js:app', 'js:mobile']);

gulp.task('adm', ['js:adminpanel']);

gulp.task('min', ['lint', 'js:all:min']);

gulp.task('w', ['js:all:watch']);

require('./gulp-tasks/langs.js');

require('./gulp-tasks/styles.js');
