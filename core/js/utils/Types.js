'use strict';

var
	_ = require('underscore'),
	
	Types = {}
;

/**
 * @param {*} mValue
 * 
 * @return {boolean}
 */
Types.isString = function (mValue)
{
	return typeof mValue === 'string';
};

/**
 * @param {*} mValue
 * 
 * @return {boolean}
 */
Types.isNonEmptyString = function (mValue)
{
	return Types.isString(mValue) && mValue !== '';
};

/**
 * @param {*} mValue
 * 
 * @return {string}
 */
Types.pString = function (mValue)
{
	return (mValue !== undefined && mValue !== null) ? mValue.toString() : '';
};

/**
 * @param {*} mValue
 * 
 * @return {boolean}
 */
Types.isNumber = function (mValue)
{
	return typeof mValue === 'number';
};

/**
 * @param {*} mValue
 * 
 * @return {boolean}
 */
Types.isPositiveNumber = function (mValue)
{
	return Types.isNumber(mValue) && mValue > 0;
};

/**
 * @param {*} mValue
 * @param {number} iDefault
 * 
 * @return {number}
 */
Types.pInt = function (mValue, iDefault)
{
	var iValue = window.parseInt(mValue, 10);
	if (isNaN(iValue))
	{
		iValue = !isNaN(iDefault) ? iDefault : 0;
	}
	return iValue;
};

/**
 * @param {number} iNum
 * @param {number} iDec
 * 
 * @return {number}
 */
Types.roundNumber = function (iNum, iDec)
{
	return Math.round(iNum * Math.pow(10, iDec)) / Math.pow(10, iDec);
};

/**
 * @param {*} aValue
 * @param {number=} iArrayLen
 * 
 * @return {boolean}
 */
Types.isNonEmptyArray = function (aValue, iArrayLen)
{
	iArrayLen = iArrayLen || 1;
	
	return _.isArray(aValue) && iArrayLen <= aValue.length;
};

module.exports = Types;
