'use strict';

var ko = require('knockout');

module.exports = {
	ServerModuleName: 'SimpleChat',
	HashModuleName: 'simplechat',
	
	/**
	 * Setting indicates if module is enabled by user or not.
	 * The Core subscribes to this setting changes and if it is **true** displays module tab in header and its screens.
	 * Otherwise the Core doesn't display module tab in header and its screens.
	 */
	enableModule: ko.observable(false),
	
	/**
	 * Initializes settings of simple chat module.
	 * 
	 * @param {Object} oAppDataSection Simple chat module section in AppData.
	 */
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.enableModule(!!oAppDataSection.EnableModule);
		}
	},
	
	/**
	 * Updates settings of simple chat module after editing.
	 * 
	 * @param {boolean} bEnableModule New value of setting 'EnableModule'
	 */
	update: function (bEnableModule) {
		this.enableModule(bEnableModule);
	}
};
