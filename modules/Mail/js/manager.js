'use strict';

module.exports = function (oSettings) {
	require('modules/Mail/js/enums.js');

	var
		_ = require('underscore'),
		
		Settings = require('modules/Mail/js/Settings.js'),
		Cache = null
	;

	Settings.init(oSettings);
	
	Cache = require('modules/Mail/js/Cache.js');
	Cache.init();
	
	return {
		start: function () {
			require('modules/Mail/js/koBindings.js');
		},
		screens: {
			'main': function () {
				return require('modules/Mail/js/views/MailView.js');
			}
		},
		getHeaderItem: function () {
			return require('modules/Mail/js/views/HeaderItemView.js');
		},
		prefetcher: require('modules/Mail/js/Prefetcher.js'),
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
				bMobileApp = false,
				bAllowSendMail = true,
				ComposeUtils = bMobileApp ? require('modules/Mail/js/utils/ScreenCompose.js') : require('modules/Mail/js/utils/PopupCompose.js')
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