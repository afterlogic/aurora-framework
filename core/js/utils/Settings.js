'use strict';

var
	_ = require('underscore'),
	
	TextUtils = require('core/js/utils/Text.js'),
	UserSettings = require('core/js/Settings.js'),
	
	SettingsUtils = {}
;

/**
 * @return Array
 */
SettingsUtils.getDateFormatsForSelector = function ()
{
	return _.map(UserSettings.DateFormats, function (sDateFormat) {
		switch (sDateFormat)
		{
			case 'MM/DD/YYYY':
				return {name: TextUtils.i18n('DATETIME/DATEFORMAT_MMDDYYYY'), value: sDateFormat};
			case 'DD/MM/YYYY':
				return {name: TextUtils.i18n('DATETIME/DATEFORMAT_DDMMYYYY'), value: sDateFormat};
			case 'DD Month YYYY':
				return {name: TextUtils.i18n('DATETIME/DATEFORMAT_DDMONTHYYYY'), value: sDateFormat};
			default:
				return {name: sDateFormat, value: sDateFormat};
		}
	});
};

module.exports = SettingsUtils;