var
	_ = require('underscore'),
	argv = require('./argv.js'),
	fileExists = require('file-exists'),
	gulp = require('gulp'),
	concat = require('gulp-concat-util'),
	plumber = require('gulp-plumber'),
	ini = require('iniparser'),
	gutil = require('gulp-util'),
	
	aModulesNames = argv.getModules(),
	sTenantName = argv.getParameter('--tenant')
;

function GetModuleName(sFilePath)
{
	return sFilePath.replace(/.*modules[\\\/]{1}(.*?)[\\\/]{1}i18n.*/, "$1");
}

function BuildLang(sLanguage)
{
	var
		aModules = _.map(aModulesNames, function (sModuleName) {
			var 
				sFilePath = './modules/' + sModuleName + '/i18n/' + sLanguage + '.ini',
				sTenantFilePath = 'tenants/' + sTenantName + '/modules/' + sModuleName + '/i18n/' + sLanguage + '.ini',
				sFoundedFilePath = ''
			;
			
			//check module override
			if (fileExists(sTenantFilePath))
			{
				sFoundedFilePath = sTenantFilePath;
			}
			else
			{
				sFoundedFilePath = fileExists(sFilePath) ? sFilePath : './modules/' + sModuleName + '/i18n/English.ini';
			}

			return sFoundedFilePath;
		})
	;
	
	gulp.src(aModules)
		.pipe(concat(sLanguage + '.json', {
			sep: ',\r\n\r\n',
			process: function(sSrc, sFilePath) {
				var
					sPrefix = GetModuleName(sFilePath).toUpperCase(),
					sConstants = '',
					oData = ini.parseString(sSrc)
				;
				
				if (oData)
				{
					var 
						iCount = Object.keys(oData).length
					;
					
					_.each(oData, function (sItem, sKey) {
						iCount--;
						
						var bInQuotes = sItem.substr(0, 1) === '"';
						while(sItem.substr(0, 1) === '"' || sItem.substr(0, 1) === ' ')
						{
							sItem = sItem.substr(1);
						}
						while (sItem.substr(-1, 1) === '"' || sItem.substr(-1, 1) === ' ')
						{
							sItem = sItem.substr(0, sItem.length - 1);
						}
						
						// remove inline comments
						sItem = sItem.replace(/(:?\")(:?\s*)\;.*$/g, '');
						
						if (!bInQuotes)
						{
							sItem = sItem.replace(/\"/g, '\\"').replace(/\r\n/g, '\\r\\n');
						}
						sConstants += '"' + sPrefix + '/' + sKey + '": "' + sItem + '"'+(iCount > 0 ? ',\r\n' : '' );
					});
				}
				
				return sConstants + '\r\n'; 
			}
		}))
		.pipe(concat.header('{\r\n'))
		.pipe(concat.footer('}\r\n'))
		.pipe(plumber({
			errorHandler: function (err) {
				console.log(err.toString());
				gutil.beep();
				this.emit('end');
			}
		}))
		.pipe(sTenantName ? gulp.dest('./tenants/' +sTenantName+ '/static/i18n/') : gulp.dest('./static/i18n/'))
	;
	
	return;
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
