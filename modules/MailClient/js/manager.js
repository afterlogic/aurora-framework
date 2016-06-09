'use strict';

module.exports = function (oSettings) {
	require('modules/%ModuleName%/js/enums.js');

	var
		_ = require('underscore'),
		
		App = require('modules/Core/js/App.js'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		Cache = null,
		
		oScreens = {}
	;

	Settings.init(oSettings);
	
	Cache = require('modules/%ModuleName%/js/Cache.js');
	Cache.init();
	
	oScreens[Settings.HashModuleName] = function () {
		return require('modules/%ModuleName%/js/views/MailView.js');
	};
	if (App.isMobile())
	{
		oScreens[Settings.HashModuleName + '-compose'] = function () {
			var CComposeView = require('modules/%ModuleName%/js/views/CComposeView.js');
			return new CComposeView();
		};
	}
	
	return {
		enableModule: Settings.enableModule,
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		start: function (ModulesManager) {
			var
				TextUtils = require('modules/Core/js/utils/Text.js'),
				Browser = require('modules/Core/js/Browser.js'),
				MailUtils = require('modules/%ModuleName%/js/utils/Mail.js')
			;
			
			require('modules/%ModuleName%/js/koBindings.js');
			if (!App.isMobile())
			{
				require('modules/%ModuleName%/js/koBindingSearchHighlighter.js');
			}
			
			if (Settings.AllowAppRegisterMailto)
			{
				MailUtils.registerMailto(Browser.firefox);
			}
			
			if (Settings.enableModule())
			{
				ModulesManager.run('SettingsClient', 'registerSettingsTab', [function () { return require('modules/%ModuleName%/js/views/settings/MailSettingsPaneView.js'); }, Settings.HashModuleName, TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB')]);
				ModulesManager.run('SettingsClient', 'registerSettingsTab', [function () { return require('modules/%ModuleName%/js/views/settings/AccountsSettingsPaneView.js'); }, Settings.HashModuleName + '-accounts', TextUtils.i18n('%MODULENAME%/LABEL_ACCOUNTS_SETTINGS_TAB')]);
			}
		},
		getScreens: function () {
			return oScreens;
		},
		getHeaderItem: function () {
			return {
				item: require('modules/%ModuleName%/js/views/HeaderItemView.js'),
				name: Settings.HashModuleName
			};
		},
		getPrefetcher: function () {
			return require('modules/%ModuleName%/js/Prefetcher.js');
		},
		registerMessagePaneController: function (oController, sPlace) {
			var MessagePaneView = require('modules/%ModuleName%/js/views/MessagePaneView.js');
			MessagePaneView.registerController(oController, sPlace);
		},
		registerComposeToolbarController: function (oController) {
			var ComposePopup = require('modules/%ModuleName%/js/popups/ComposePopup.js');
			ComposePopup.registerToolbarController(oController);
		},
		getComposeMessageToAddresses: function () {
			var
				bAllowSendMail = true,
				ComposeUtils = (App.isMobile() || App.isNewTab()) ? require('modules/%ModuleName%/js/utils/ScreenCompose.js') : require('modules/%ModuleName%/js/utils/PopupCompose.js')
			;
			
			return bAllowSendMail ? ComposeUtils.composeMessageToAddresses : false;
		},
		getSearchMessagesInInbox: function () {
			return _.bind(Cache.searchMessagesInInbox, Cache);
		},
		getSearchMessagesInCurrentFolder: function () {
			return _.bind(Cache.searchMessagesInCurrentFolder, Cache);
		},
		getAllAccountsFullEmails: function () {
			var AccountList = require('modules/%ModuleName%/js/AccountList.js');
			return AccountList.getAllFullEmails();
		},
		getCreateAccountPopup: function () {
			return require('modules/%ModuleName%/js/popups/CreateAccountPopup.js');
		}
	};
};
