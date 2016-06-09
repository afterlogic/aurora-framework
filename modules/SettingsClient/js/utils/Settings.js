'use strict';

var
	_ = require('underscore'),
	
	SettingsUtils = {},
	
	aStandardPerPageList = [10, 20, 30, 50, 75, 100, 150, 200]
;

/**
 * @param {number} iValue
 * 
 * @returns {Array}
 */
SettingsUtils.getAdaptedPerPageList = function (iValue)
{
	if (-1 === _.indexOf(aStandardPerPageList, iValue))
	{
		return _.sortBy(_.union(aStandardPerPageList, [iValue]), function (iItem) { return iItem; });
	}
	
	return aStandardPerPageList;
};

module.exports = SettingsUtils;
