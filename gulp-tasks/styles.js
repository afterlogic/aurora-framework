var
	_ = require('underscore'),
	argv = require('./argv.js'),
	fileExists = require('file-exists'),
	gulp = require('gulp'),
	less = require('gulp-less'),
	gutil = require('gulp-util'),
	concat = require('gulp-concat-util'),
	plumber = require('gulp-plumber'),

	aModulesNames = argv.getModules(),
	aModulesWatchPaths = [],
	
	aThemes = argv.getParameter('--themes').split(',')
;

aModulesNames.forEach(function (sModuleName) {
	if (fileExists('./modules/' + sModuleName + '/styles/styles.less'))
	{
		aModulesWatchPaths.push('./modules/' + sModuleName + '/styles/**/*.less');
	}
});

function BuildThemeCss(sTheme, bMobile)
{
	var
		aModulesFiles = [],
		sPostfix = bMobile ? '-mobile' : ''
	;
	
	aModulesNames.forEach(function (sModuleName) {
		if (fileExists('./modules/' + sModuleName + '/styles/styles' + sPostfix + '.less'))
		{
			aModulesFiles.push('./modules/' + sModuleName + '/styles/styles' + sPostfix + '.less');
		}
	});

	gulp.src(aModulesFiles)
		.pipe(concat('styles' + sPostfix + '.css', {
			process: function(sSrc, sFilePath) {
				var
					sThemePath = sFilePath.replace('styles' + sPostfix + '.less', 'themes/' + sTheme + '.less'),
					sRes = fileExists(sThemePath) ? '@import "' + sThemePath + '";\r\n' : ''
				;
				
				return sRes + '@import "' + sFilePath + '";\r\n'; 
			}
		}))
		.pipe(plumber({
			errorHandler: function (err) {
				console.log(err.toString());
				gutil.beep();
				this.emit('end');
			}
		}))
		.pipe(less())
		.pipe(gulp.dest('./skins/' + sTheme))
		.on('error', gutil.log);
}

function MoveThemeFiles(sTheme)
{
	var
		fs = require('fs'),
		copyDir = require('copy-dir'),
		ncp = require('ncp').ncp,
		mkdirp = require('mkdirp'),
		aDirs = [
			{from: 'modules/Core/styles/themes/fonts', to: 'skins/' + sTheme + '/fonts'},
			{from: 'modules/Core/styles/themes/images', to: 'skins/' + sTheme + '/images'},
			{from: 'modules/Core/styles/themes/' + sTheme.toLowerCase() + '-images', to: 'skins/' + sTheme + '/images'}
		],
		fCopyDir = function (oDirs) {
			copyDir(oDirs.from, oDirs.to, function (oErr) {
				if (oErr)
				{
					console.log(oDirs.from + ' directory copying was failed: ', oErr);
				}
			});	
		},
		fCopySharing = function (sTheme) {
			ncp('./modules/Core/styles/sharing.css', './skins/' + sTheme + '/sharing.css', function (oErr) {
				if (oErr)
				{
					console.log(sTheme + '/sharing.css file copying was failed: ', oErr);
				}
			});
		}
	;
	
	_.each(aDirs, function (oDirs) {
		if (fs.existsSync(oDirs.from))
		{
			if (fs.existsSync(oDirs.to))
			{
				fCopyDir(oDirs);
			}
			else
			{
				mkdirp(oDirs.to, function (oErr) {
					if (!fs.existsSync(oDirs.to))
					{
						console.log(oDirs.to + ' directory creating was failed: ', oErr);
					}
					else
					{
						fCopyDir(oDirs);
					}
				});
			}
		}
		else
		{
			console.log(oDirs.from + ' directory does not exist');
		}
	});
	
	if (fs.existsSync('./skins/' + sTheme))
	{
		fCopySharing(sTheme);
	}
	else
	{
		mkdirp('./skins/' + sTheme, function (oErr) {
			if (oErr)
			{
				console.log('./skins/' + sTheme + ' directory creating was failed: ', oErr);
			}
			else
			{
				fCopySharing(sTheme);
			}
		});
	}
}

gulp.task('styles', function () {
	_.each(aThemes, function (sTheme) {
		MoveThemeFiles(sTheme);
		BuildThemeCss(sTheme.toLowerCase(), false);
		BuildThemeCss(sTheme.toLowerCase(), true);
	});
});

gulp.task('cssonly', function () {
	_.each(aThemes, function (sTheme) {
		BuildThemeCss(sTheme.toLowerCase(), false);
		BuildThemeCss(sTheme.toLowerCase(), true);
	});
});

gulp.task('styles:watch', ['styles'], function () {
	gulp.watch(aModulesWatchPaths, {interval: 500}, ['cssonly']);
});

module.exports = {};
