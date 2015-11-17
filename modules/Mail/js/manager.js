'use strict';

module.exports = function (oSettings) {
	require('modules/Mail/js/enums.js');

	var
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
		registerMessagePaneTopController: function (oController) {
			var MessagePaneView = require('modules/Mail/js/views/MessagePaneView.js');
			MessagePaneView.registerTopController(oController);
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
		}
	};
};