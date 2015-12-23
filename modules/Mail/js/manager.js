'use strict';

module.exports = function (oSettings) {
	require('modules/Mail/js/enums.js');

	var
		_ = require('underscore'),
		
		App = require('core/js/App.js'),
		
		Settings = require('modules/Mail/js/Settings.js'),
		Cache = null,
		
		oScreens = {
			'main': function () {
				return require('modules/Mail/js/views/MailView.js');
			}
		}
	;

	Settings.init(oSettings);
	
	Cache = require('modules/Mail/js/Cache.js');
	Cache.init();
	
	if (App.isMobile())
	{
		oScreens['compose'] = function () {
			var CComposeView = require('modules/Mail/js/views/CComposeView.js');
			return new CComposeView();
		};
	}
	
	return {
		start: function (ModulesManager) {
			var
				TextUtils = require('core/js/utils/Text.js'),
				Browser = require('core/js/Browser.js'),
				MailUtils = require('modules/Mail/js/utils/Mail.js')
			;
			
			require('modules/Mail/js/koBindings.js');
			if (!App.isMobile())
			{
				require('modules/Mail/js/koBindingSearchHighlighter.js');
			}
			
			if (Settings.AllowAppRegisterMailto)
			{
				MailUtils.registerMailto(Browser.firefox);
			}
			
			ModulesManager.run('Settings', 'registerSettingsTab', [function () { return require('modules/Mail/js/views/MailSettingsTabView.js'); }, 'mail', TextUtils.i18n('TITLE/LOGIN')]);
		},
		screens: oScreens,
		getHeaderItem: function () {
			return require('modules/Mail/js/views/HeaderItemView.js');
		},
		getPrefetcher: function () {
			return require('modules/Mail/js/Prefetcher.js');
		},
		registerMessagePaneController: function (oController, sPlace) {
			var MessagePaneView = require('modules/Mail/js/views/MessagePaneView.js');
			MessagePaneView.registerController(oController, sPlace);
		},
		registerComposeToolbarController: function (oController) {
			var ComposePopup = require('modules/Mail/js/popups/ComposePopup.js');
			ComposePopup.registerToolbarController(oController);
		},
		getComposeMessageToAddresses: function () {
			var
				bAllowSendMail = true,
				ComposeUtils = (App.isMobile() || App.isNewTab()) ? require('modules/Mail/js/utils/ScreenCompose.js') : require('modules/Mail/js/utils/PopupCompose.js')
			;
			
			return bAllowSendMail ? ComposeUtils.composeMessageToAddresses : false;
		},
		getSearchMessagesInInbox: function () {
			return _.bind(Cache.searchMessagesInInbox, Cache);
		},
		getSearchMessagesInCurrentFolder: function () {
			return _.bind(Cache.searchMessagesInCurrentFolder, Cache);
		}
	};
};