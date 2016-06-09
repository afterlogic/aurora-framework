'use strict';

var
	_ = require('underscore'),
	
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
	
	UserSettings = require('modules/CoreClient/js/Settings.js'),
	
	SettingsUtils = {}
;

/**
 * @return Array
 */
SettingsUtils.getDateFormatsForSelector = function ()
{
	return _.map(UserSettings.DateFormatList, function (sDateFormat) {
		switch (sDateFormat)
		{
			case 'MM/DD/YYYY':
				return {name: TextUtils.i18n('CORE/LABEL_DATEFORMAT_MMDDYYYY'), value: sDateFormat};
			case 'DD/MM/YYYY':
				return {name: TextUtils.i18n('CORE/LABEL_DATEFORMAT_DDMMYYYY'), value: sDateFormat};
			case 'DD Month YYYY':
				return {name: TextUtils.i18n('CORE/LABEL_DATEFORMAT_DDMONTHYYYY'), value: sDateFormat};
			default:
				return {name: sDateFormat, value: sDateFormat};
		}
	});
};

module.exports = SettingsUtils;
