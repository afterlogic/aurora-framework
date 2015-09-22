'use strict';

var Enums = require('modules/OpenPgp/js/Enums.js');

/**
 * @todo
 * @constructor
 */
function COpenPgpResult()
{
	this.result = true;
	this.errors = null;
	this.notices = null;
	this.exceptions = null;
}

/**
 * @type {mixed}
 */
COpenPgpResult.prototype.result = false;

/**
 * @type {Array|null}
 */
COpenPgpResult.prototype.errors = null;

/**
 * @type {Array|null}
 */
COpenPgpResult.prototype.notices = null;

/**
 * @param {number} iCode
 * @param {string} sValue
 * @return {COpenPgpResult}
 */
COpenPgpResult.prototype.addError = function (iCode, sValue)
{
	this.result = false;
	this.errors = this.errors || [];
	this.errors.push([iCode || Enums.OpenPgpErrors.UnknownError, sValue || '']);

	return this;
};

/**
 * @param {number} iCode
 * @param {string} sValue
 * @return {COpenPgpResult}
 */
COpenPgpResult.prototype.addNotice = function (iCode, sValue)
{
	this.notices = this.notices || [];
	this.notices.push([iCode || Enums.OpenPgpErrors.UnknownNotice, sValue || '']);

	return this;
};

/**
 * @param {Error} e
 * @param {number=} iErrorCode
 * @param {string=} sErrorMessage
 * @return {COpenPgpResult}
 */
COpenPgpResult.prototype.addExceptionMessage = function (e, iErrorCode, sErrorMessage)
{
	if (e)
	{
		this.result = false;
		this.exceptions = this.exceptions || [];
		this.exceptions.push('' + (e.name || 'unknown') + ': ' + (e.message || ''));
	}

	if (!Utils.isUnd(iErrorCode))
	{
		this.addError(iErrorCode, sErrorMessage);
	}

	return this;
};

/**
 *  @return {boolean}
 */
COpenPgpResult.prototype.hasErrors = function ()
{
	return this.errors && 0 < this.errors.length;
};

/**
 *  @return {boolean}
 */
COpenPgpResult.prototype.hasNotices = function ()
{
	return this.notices && 0 < this.notices.length;
};

module.exports = COpenPgpResult;