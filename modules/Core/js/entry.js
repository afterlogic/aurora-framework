'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'Auth': require('modules/AuthClient/js/manager.js'),
			'Mail': require('modules/MailClient/js/manager.js'),
			'Contacts': require('modules/ContactsClient/js/manager.js'),
			'Calendar': require('modules/CalendarClient/js/manager.js'),
			'Files': require('modules/FilesClient/js/manager.js'),
			'HelpDesk': require('modules/HelpDeskClient/js/manager.js'),
			'Phone': require('modules/PhoneClient/js/manager.js'),
			'SettingsClient': require('modules/SettingsClient/js/manager.js'),
			'SimpleChat': require('modules/SimpleChatClient/js/manager.js'),
			'SimpleChatEmoji': require('modules/SimpleChatEmojiClient/js/manager.js'),
			
			'OpenPgp': require('modules/OpenPgpClient/js/manager.js'),
			'MailSensitivity': require('modules/MailSensitivityClient/js/manager.js'),
			'SessionTimeout': require('modules/SessionTimeoutClient/js/manager.js'),
			'ChangePassword': require('modules/ChangePasswordClient/js/manager.js'),
			'MobileSync': require('modules/MobileSyncClient/js/manager.js'),
			'OutlookSync': require('modules/OutlookSyncClient/js/manager.js')
		},
		ModulesManager = require('modules/Core/js/ModulesManager.js'),
		App = require('modules/Core/js/App.js'),
		bSwitchingToMobile = App.checkMobile()
	;
	
	if (!bSwitchingToMobile)
	{
		ModulesManager.init(oAvaliableModules, App.getUserRole(), App.isPublic());
		App.init();
	}
});
