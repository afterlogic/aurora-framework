'use strict';

var
	TextUtils = require('core/js/utils/Text.js'),
			
	DateUtils = {}
;

DateUtils.getMonthNamesArray = function ()
{
	var
		aMonthes = TextUtils.i18n('CORE/MONTH_NAMES').split(' '),
		iLen = 12,
		iIndex = aMonthes.length
	;
	
	for (; iIndex < iLen; iIndex++)
	{
		aMonthes[iIndex] = '';
	}
	
	return aMonthes;
};

/**
 * @param {number} iMonth
 * @param {number} iYear
 * 
 * @return {number}
 */
DateUtils.daysInMonth = function (iMonth, iYear)
{
	if (0 < iMonth && 13 > iMonth && 0 < iYear)
	{
		return new Date(iYear, iMonth, 0).getDate();
	}

	return 31;
};

module.exports = DateUtils;
