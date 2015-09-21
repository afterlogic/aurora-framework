'use strict';

var Settings = require('modules/Auth/js/Settings.js');

require('modules/Auth/js/enums.js');

module.exports = function (oSettings) {
	Settings.init(oSettings);
	
	return {
		screens: {
			'main': {
				'Model': require('modules/Auth/js/views/CWrapLoginView.js'),
				'TemplateName': 'Login_WrapLoginViewModel'
			}
		}
	};
};