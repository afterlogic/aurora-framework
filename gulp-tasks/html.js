var
	_ = require('underscore'),
	argv = require('./argv.js'),
	buffer = require('vinyl-buffer'),
	gulp = require('gulp'),
	source = require('vinyl-source-stream'),
	fs = require('fs'),
	path = require('path')
;

function BuildHtmlFromDirectory(sDir, sModuleName, sModuleNameUpperCase, stream)
{
	var aFiles = fs.readdirSync(sDir);

	_.each(aFiles, function (sFileName) {
		var sFilePath = sDir + '/' + sFileName;
		if (fs.lstatSync(sFilePath).isDirectory())
		{
			BuildHtmlFromDirectory(sFilePath, sModuleName, sModuleNameUpperCase, stream);
		}
		else
		{
			console.log(sFilePath);
			var
				sContent = fs.readFileSync(sFilePath, 'utf8'),
				sBaseName = path.basename(sFileName, '.html')
			;
			stream.write('\r\n<script id="' + sModuleName + '_' + sBaseName + '" type="text/html">\r\n');
			stream.write(sContent.replace(/%ModuleName%/g, sModuleNameUpperCase));
			stream.write('</script>\r\n');
		}
	});
}

function BuildHtml()
{
	var
		aModulesNames = argv.getModules(),
		stream = source('templates.html')
	;

	_.each(aModulesNames, function (sModuleName) {
		var sDir = 'modules/' + sModuleName + '/templates';
		if (fs.existsSync(sDir) && fs.lstatSync(sDir).isDirectory())
		{
			BuildHtmlFromDirectory(sDir, sModuleName, sModuleName.toUpperCase(), stream);
		}
	});
	
	process.nextTick(function() {
		stream.end();
	});
	
	stream
		.pipe(buffer())
		.pipe(gulp.dest('./static/html/'));
}

gulp.task('html', function() {
	BuildHtml();
});

module.exports = {};
