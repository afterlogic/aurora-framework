'use strict';

require('modules/Auth/js/enums.js');

module.exports = function () {
	return {
		screens: {
			'main': {
				'Model': require('modules/Auth/js/views/CWrapLoginView.js'),
				'TemplateName': 'Login_WrapLoginViewModel'
			}
		}
	};
};