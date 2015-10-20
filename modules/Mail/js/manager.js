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
		screens: {
			'main': function () {
				return require('modules/Mail/js/views/CMailView.js');
			}
		},
		getHeaderItem: function () {
			return require('modules/Mail/js/views/HeaderItemView.js');
		},
		prefetcher: require('modules/Mail/js/Prefetcher.js')
	};
};