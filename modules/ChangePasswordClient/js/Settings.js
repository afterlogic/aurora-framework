'use strict';

var
	Types = require('modules/CoreClient/js/utils/Types.js'),
	UrlUtils = require('modules/CoreClient/js/utils/Url.js')
;

module.exports = {
	PasswordMinLength: 0,
	PasswordMustBeComplex: false,
	ResetPassHash: UrlUtils.getRequestParam('reset-pass') || '',
	
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.PasswordMinLength = Types.pInt(oAppDataSection.PasswordMinLength);
			this.PasswordMustBeComplex = !!oAppDataSection.PasswordMustBeComplex;
		}
	}
};