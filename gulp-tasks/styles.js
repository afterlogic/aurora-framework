var
	_ = require('underscore'),
	argv = require('./argv.js'),
	buffer = require('vinyl-buffer'),
	del = require('delete'),
	gulp = require('gulp'),
	less = require('gulp-less'),
	source = require('vinyl-source-stream')
;

gulp.task('styles', function () {
	var
		stream = source('styles.less'),
		aModulesNames = argv.getModules()
	;
	
	_.each(aModulesNames, function (sModuleName) {
		stream.write('@import "./modules/' + sModuleName + '/styles/styles.less";\r\n');
	});
	
	process.nextTick(function() {
		stream.end();
	});
	
	stream
		.pipe(buffer())
		.pipe(gulp.dest('./gulp-tasks/'))
		.on('end', function() {
			gulp.src('./gulp-tasks/styles.less')
				.pipe(less())
				.pipe(gulp.dest('./skins/Default'))
				.on('end', function () {
					del('./gulp-tasks/styles.less');
				});
		});
});

module.exports = {};
