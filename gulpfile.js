var
	browserify = require('browserify'),
	watchify = require('watchify'),
	gulp = require('gulp'),
	reactify = require('reactify'),
	source = require('vinyl-source-stream'),
	jshint = require('gulp-jshint'),
	uglify = require('gulp-uglify'),
	header = require('gulp-header'),
	buffer = require('vinyl-buffer'),
	rename = require('gulp-rename'),
	eol = require('gulp-eol'),
	gutil = require('gulp-util'),
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
		]
	},
	mobile: {
		dest: './static/js/',
		name: 'app-mobile.js',
		min: 'app-mobile.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./core/js/entry-mobile.js"
		]
	},
	message_newtab: {
		dest: './static/js/',
		name: 'app-message-newtab.js',
		min: 'app-message-newtab.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/Mail/js/entry-newtab.js"
		]
	},
	files_pub: {
		dest: './static/js/',
		name: 'app-files-pub.js',
		min: 'app-files-pub.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/Files/js/entry-pub.js"
		]
	},
	calendar_pub: {
		dest: './static/js/',
		name: 'app-calendar-pub.js',
		min: 'app-calendar-pub.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/Calendar/js/entry-pub.js"
		]
	},
	helpdesk_ext: {
		dest: './static/js/',
		name: 'app-helpdesk.js',
		min: 'app-helpdesk.min.js',
		afterlogic: true,
		watch: true,
		src: [
			"./modules/HelpDesk/js/entry-ext.js"
		]
	}
};

function jsTask(sName, oData)
{
	if (oData && oData.src)
	{
		gulp.task('js:' + sName, function() {
			var b = browserify(oData.src, {transform: [reactify], paths: ['./']});
			return	b.bundle()
				.pipe(source(oData.name))
				.pipe(buffer())
				.pipe(header(((oData.afterlogic ? cfg.license : '') || '') + jshintEnd))
				.pipe(eol('\n', true))
				.pipe(gulp.dest(oData.dest));
		});

		if (oData.watch)
		{
			gulp.task('js:' + sName + ':watch', function() {
				var bundler = watchify(browserify(oData.src));
				
				function rebundle() {
					return bundler.bundle()
						.pipe(source(oData.name))
						.pipe(buffer())
						.pipe(header(((oData.afterlogic ? cfg.license : '') || '') + jshintEnd))
						.pipe(eol('\n', true))
						.pipe(gulp.dest(oData.dest));
				}
				
				bundler.on('update', rebundle);

				return rebundle();
			});
		}

		if (oData.min)
		{
			gulp.task('js:' + sName + ':min', function() {
				return gulp.src(oData.dest + oData.name)
					.pipe(rename(oData.min))
					.pipe(uglify(cfg.uglify))
					.pipe(eol('\n', true))
					.pipe(gulp.dest(oData.dest))
					.on('error', gutil.log);
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

gulp.task('default', ['js:app']);

gulp.task('all', ['js:app', 'js:files_pub', 'js:calendar_pub', 'js:helpdesk_ext', 'js:message_newtab', 'js:mobile']);

gulp.task('files', ['js:app', 'js:files_pub']);

gulp.task('cal', ['js:app', 'js:calendar_pub']);

gulp.task('helpdesk', ['js:app', 'js:helpdesk_ext']);

gulp.task('msg', ['js:app', 'js:message_newtab']);

gulp.task('mob', ['js:app', 'js:mobile']);

gulp.task('min', ['lint', 'js:all:min']);

gulp.task('w', ['js:all:watch']);


var
	_ = require('underscore'),
	ini = require('cascade-ini')
;

/**
 * 
 * @param {string} sParamName
 * @param {string} sDefault
 * @returns {string}
 */
function GetParameterFromArgv(sParamName, sDefault)
{
	var sParamValue = sDefault || '';
	
	_.each(process.argv, function (sArg, iIndex) {
		if (sArg === sParamName)
		{
			sParamValue = process.argv[iIndex + 1];
		}
	});
	
	return sParamValue;
}

function GetModulesFromArgv()
{
	var sModules = GetParameterFromArgv('--modules');
	
	return _.union(['Core'], sModules.split(','));
}

gulp.task('langs', function() {
	var
		sLanguage = GetParameterFromArgv('--lang', 'English'),
		aModulesNames = GetModulesFromArgv(),
		aModules = _.map(aModulesNames, function (sModuleName) {
			return './modules/' + sModuleName + '/i18n/' + sLanguage + '.ini';
		}),
		stream = source(sLanguage + '.json'),
		iCount = 0,
		iTotal = aModules.length,
		fEndStream = function () {
			if (iCount === iTotal)
			{
				stream.write('\r\n}');
				process.nextTick(function() {
					stream.end();
				});
				stream
					.pipe(buffer())
					.pipe(gulp.dest('./static/i18n/'));
			}
		}
	;
	
	stream.write('{\r\n');
	
	_.each(aModules, function (sModulePath, iModuleIndex) {
		ini.parseFile(sModulePath, function(oError, oData) {
			if (oError)
			{
				console.log('Error', oError);
			}
			else
			{
				var
					sPrefix = aModulesNames[iModuleIndex].toUpperCase(),
					sConstants = ''
				;

				_.each(oData, function (sItem, sKey) {
					var bInQuotes = sItem.substr(0, 1) === '"';
					while(sItem.substr(0, 1) === '"' || sItem.substr(0, 1) === ' ')
					{
						sItem = sItem.substr(1);
					}
					while (sItem.substr(-1, 1) === '"' || sItem.substr(-1, 1) === ' ')
					{
						sItem = sItem.substr(0, sItem.length - 1);
					}
					if (!bInQuotes)
					{
						sItem = sItem.replace(/\"/g, '\\"').replace(/\r\n/g, '\\r\\n');
					}
					sConstants += '"' + sPrefix + '/' + sKey + '": "' + sItem + '",\r\n';
				});

				if (iCount < iTotal - 1)
				{
					if (sConstants !== '')
					{
						stream.write(sConstants + '\r\n');
					}
				}
				else
				{
					stream.write(sConstants.slice(0, -3));
				}
			}

			iCount++;

			fEndStream();
		});
	});
});
