var
	_ = require('underscore'),
	gulp = require('gulp'),
	ini = require('iniparser'),
	buffer = require('vinyl-buffer'),
	source = require('vinyl-source-stream')
;

function BuildLangFiles(sLang)
{
	var
		oLangIni = ini.parseSync('./i18n/' + sLang + '.ini'),
		aModules = ['Auth', 'Calendar', 'ChangePassword', 'Contacts', 'Core', 'Files', 'HelpDesk', 'Mail', 'MailSensitivity', 'MobileSync', 'OpenPgp', 'OutlookSync', 'Phone']
	;
//	console.log(oLangIni['CALENDAR']);
//	console.log(oLangIni['CALENDAR']['APPOINTMENT_CANCELED']);
	
	_.each(aModules, function (sModule) {
		var oModuleIni = ini.parseSync('./modules/' + sModule + '/i18n/English.ini');
		
		BuildLangFile(sLang, oLangIni, sModule, oModuleIni);
	});
}

function GetSectionByModule(sModule)
{
	var sSection = sModule.toUpperCase();
	switch (sModule)
	{
		case 'Auth':
			sSection = 'LOGIN';
			break;
		case 'Files':
			sSection = 'FILESTORAGE';
			break;
	}
	return sSection;
}

function BuildLangFile(sLang, oLangIni, sModule, oModuleIni)
{
	var
		stream = source(sLang + '.ini'),
		iNotFound = 0
	;
	
	_.each(oModuleIni, function (sItem, sKey) {
		var
			aItem = sItem.split(';'),
			sValue = aItem[0].trim(),
			sComm = aItem[1] ? aItem[1].trim() : '',
			aComm = sComm.split('/'),
			sSection = aComm[0].trim(),
			sName = aComm[1] ? aComm[1].trim() : ''
		;
		if (sSection === '')
		{
			sSection = GetSectionByModule(sModule);
		}
		if (sName === '')
		{
			sName = sKey;
		}
		if (oLangIni[sSection] && oLangIni[sSection][sName])
		{
			stream.write(sKey + ' = ' + oLangIni[sSection][sName] + '\r\n');
		}
		else
		{
			if (sModule !== 'HelpDesk' && sModule !== 'Phone')
			{
//				console.log('sLang', sLang, 'sKey', sKey, 'sSection', sSection, 'sName', sName);
			}

			stream.write(sKey + ' = ' + sValue + '\r\n');
			iNotFound++;
		}
	});
	
	process.nextTick(function() {
		stream.end();
	});
	stream
		.pipe(buffer())
		.pipe(gulp.dest('./modules/' + sModule + '/i18n/'));

	if (iNotFound > 0)
	{
		console.log('not found', sLang, iNotFound, sModule);
	}
}

gulp.task('cnvl', function () {
	var aLangs = ['Arabic', 'Bulgarian', 'Chinese-Simplified', 'Chinese-Traditional', 'Czech', 'Danish', 'Dutch', 'Estonian', 'Finnish', 'French', 'German', 'Greek', 
		'Hebrew', 'Hungarian', 'Italian', 'Japanese', 'Korean', 'Latvian', 'Lithuanian', 'Norwegian', 'Persian', 'Polish', 'Portuguese-Brazil', 'Portuguese-Portuguese', 
		'Romanian', 'Russian', 'Serbian', 'Slovenian', 'Spanish', 'Swedish', 'Thai', 'Turkish', 'Ukrainian', 'Vietnamese'];
	
	_.each(aLangs, function (sLang) {
		BuildLangFiles(sLang);
	});
});

module.exports = {};
