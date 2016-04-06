var
	_ = require('underscore'),
	argv = require('./argv.js'),
	fileExists = require('file-exists'),
	gulp = require('gulp'),
	less = require('gulp-less'),
	gutil = require('gulp-util'),
	concat = require('gulp-concat-util'),
	plumber = require('gulp-plumber'),
	fs = require('fs'),
	copyDir = require('copy-dir'),
	ncp = require('ncp').ncp,
	mkdirp = require('mkdirp'),

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

function BuildLibsCss()
{
	var
		aLibsFiles = [
			'modules/Core/styles/vendors/normalize.css',
			'modules/Core/styles/vendors/jquery/jquery-ui-1.10.4.custom.min.css',
			'modules/Core/styles/vendors/fullcalendar-2.2.3.min.css',
			'modules/Core/styles/vendors/inputosaurus.css'
		],
		sDestPath = 'static/styles/libs/',
		fBuild = function () {
			gulp.src(aLibsFiles)
				.pipe(concat('libs.css'))
				.pipe(gulp.dest(sDestPath))
				.on('error', gutil.log);
		}
	;
	
	CheckFolderAndCallHandler(sDestPath, fBuild);
}

function BuildThemeCss(sTheme, bMobile)
{
	var
		aModulesFiles = [],
		sPostfix = bMobile ? '-mobile' : ''
	;
	
	aModulesNames.forEach(function (sModuleName) {
		if (fileExists('modules/' + sModuleName + '/styles/styles' + sPostfix + '.less'))
		{
			aModulesFiles.push('modules/' + sModuleName + '/styles/styles' + sPostfix + '.less');
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
		.pipe(gulp.dest('static/styles/themes/' + sTheme))
		.on('error', gutil.log);
}

function CheckFolderAndCallHandler(sDir, fHandler)
{
	if (fs.existsSync(sDir))
	{
		fHandler();
	}
	else
	{
		mkdirp(sDir, function (oErr) {
			if (!fs.existsSync(sDir))
			{
				console.log(sDir + ' directory creating was failed: ', oErr);
			}
			else
			{
				fHandler();
			}
		});
	}
}

function MoveFiles(sFromDir, sToDir)
{
	var
		fCopyDir = function () {
			copyDir(sFromDir, sToDir, function (oErr) {
				if (oErr)
				{
					console.log(sFromDir + ' directory copying was failed: ', oErr);
				}
			});	
		}
	;
	
	if (fs.existsSync(sFromDir))
	{
		CheckFolderAndCallHandler(sToDir, fCopyDir);
	}
	else
	{
		console.log(sFromDir + ' directory does not exist');
	}
}

function MoveSharingCss()
{
	var
		fCopySharing = function () {
			ncp('modules/Core/styles/sharing.css', 'static/styles/sharing.css', function (oErr) {
				if (oErr)
				{
					console.log('static/styles/sharing.css file copying was failed: ', oErr);
				}
			});
		}
	;
	
	CheckFolderAndCallHandler('static/styles', fCopySharing);
}

gulp.task('styles', function () {
	BuildLibsCss();
	MoveFiles('modules/Core/styles/vendors/jquery/images', 'static/styles/libs/images');
	MoveFiles('modules/Core/styles/fonts', 'static/styles/fonts');
	MoveFiles('modules/Core/styles/images', 'static/styles/images');
	MoveSharingCss();
	_.each(aThemes, function (sTheme) {
		MoveFiles('modules/Core/styles/themes/' + sTheme.toLowerCase() + '-images', 'static/styles/themes/' + sTheme + '/images');
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
