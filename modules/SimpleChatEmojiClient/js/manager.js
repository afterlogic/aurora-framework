'use strict';

module.exports = function (oAppData) {
	return {
		/**
		 * Returns true if simple chat emoji module is available for certain user role and public or not public mode.
		 * 
		 * @param {int} iUserRole User role, wich enum values are described in modules/CoreClient/js/enums.js
		 * @param {boolean} bPublic **true** if applications runs in public mode (for example, public calendar or public contact)
		 * 
		 * @returns {Boolean}
		 */
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser || iUserRole === Enums.UserRole.RegisteredUser;
		},
		
		/**
		 * Runs before application start. Subscribes to the event before post displaying.
		 * 
		 * @param {Object} ModulesManager
		 */
		start: function (ModulesManager) {
			var App = require('modules/CoreClient/js/App.js');
			
			App.subscribeEvent('SimpleChat::DisplayPost::before', function (oParameters) {
				oParameters.Post.displayText = oParameters.Post.text.replace(':)', '&#128522;');
			});
		}
	};
};
