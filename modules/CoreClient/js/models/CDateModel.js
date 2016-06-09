'use strict';

var
	moment = require('moment'),
			
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
	Utils = require('modules/CoreClient/js/utils/Common.js'),
	
	UserSettings = require('modules/CoreClient/js/Settings.js')
;

/**
 * @constructor
 */
function CDateModel()
{
	this.iTimeStampInUTC = 0;
	this.oMoment = null;
}

/**
 * @param {number} iTimeStampInUTC
 */
CDateModel.prototype.parse = function (iTimeStampInUTC)
{
	this.iTimeStampInUTC = iTimeStampInUTC;
	this.oMoment = moment.unix(this.iTimeStampInUTC);
};

/**
 * @param {number} iYear
 * @param {number} iMonth
 * @param {number} iDay
 */
CDateModel.prototype.setDate = function (iYear, iMonth, iDay)
{
	this.oMoment = moment([iYear, iMonth, iDay]);
};

/**
 * @return {string}
 */
CDateModel.prototype.getTimeFormat = function ()
{
	return (UserSettings.timeFormat() === Enums.TimeFormat.F24) ?
		'HH:mm' : 'hh:mm A';
};

/**
 * @return {string}
 */
CDateModel.prototype.getFullDate = function ()
{
	return (this.oMoment) ? this.oMoment.format('ddd, MMM D, YYYY, ' + this.getTimeFormat()) : '';
};

/**
 * @return {string}
 */
CDateModel.prototype.getMidDate = function ()
{
	return this.getShortDate(true);
};

/**
 * @param {boolean=} bTime = false
 * 
 * @return {string}
 */
CDateModel.prototype.getShortDate = function (bTime)
{
	var
		sResult = '',
		oMomentNow = null
	;

	if (this.oMoment)
	{
		oMomentNow = moment();

		if (oMomentNow.format('L') === this.oMoment.format('L'))
		{
			sResult = this.oMoment.format(this.getTimeFormat());
		}
		else
		{
			if (oMomentNow.clone().subtract(1, 'days').format('L') === this.oMoment.format('L'))
			{
				sResult = TextUtils.i18n('CORE/LABEL_YESTERDAY');
			}
			else if (oMomentNow.year() === this.oMoment.year())
			{
				sResult = this.oMoment.format('MMM D');
			}
			else
			{
				sResult = this.oMoment.format('MMM D, YYYY');
			}

			if (!!bTime)
			{
				sResult += ', ' + this.oMoment.format(this.getTimeFormat());
			}
		}
	}

	return sResult;
};

/**
 * @return {string}
 */
CDateModel.prototype.getDate = function ()
{
	return (this.oMoment) ? this.oMoment.format('ddd, MMM D, YYYY') : '';
};

/**
 * @return {string}
 */
CDateModel.prototype.getTime = function ()
{
	return (this.oMoment) ? this.oMoment.format(this.getTimeFormat()): '';
};

/**
 * @param {string} iDate
 * 
 * @return {string}
 */
CDateModel.prototype.convertDate = function (iDate)
{
	var sFormat = Utils.getDateFormatForMoment(UserSettings.DateFormat) + ' ' + this.getTimeFormat();
	
	return moment(iDate * 1000).format(sFormat);
};

/**
 * @return {number}
 */
CDateModel.prototype.getTimeStampInUTC = function ()
{
	return this.iTimeStampInUTC;
};

module.exports = CDateModel;
