'use strict';

module.exports = function (oSettings) {
	var
		Settings = require('modules/SimpleChatClient/js/Settings.js')
	;
	Settings.init(oSettings);
	
	return {
		isAvaliable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser || iUserRole === Enums.UserRole.RegisteredUser;
		},
		start: function (ModulesManager) {
			var App = require('modules/Core/js/App.js');
			App.subscribeEvent('SimpleChat::DisplayPost::before', function (oParameters) {
				oParameters.Post.displayText = oParameters.Post.text.replace(':)', '&#128522;');
			});
		}
	};
};
