var
	_ = require('underscore'),
	argv = require('./argv.js'),
	buffer = require('vinyl-buffer'),
	fileExists = require('file-exists'),
	gulp = require('gulp'),
	ini = require('cascade-ini'),
	source = require('vinyl-source-stream')
;

function BuildLang(sLanguage)
{
	var
		aModulesNames = argv.getModules(),
		aModules = _.map(aModulesNames, function (sModuleName) {
			var sFilePath = './modules/' + sModuleName + '/i18n/' + sLanguage + '.ini';
			return fileExists(sFilePath) ? sFilePath : './modules/' + sModuleName + '/i18n/English.ini';
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
}

gulp.task('langs', function() {
	var
		sLanguages = argv.getParameter('--langs', 'English'),
		aLanguages = sLanguages.split(',')
	;
	
	_.each(aLanguages, function (sLanguage) {
		BuildLang(sLanguage);
	});
});

module.exports = {};
