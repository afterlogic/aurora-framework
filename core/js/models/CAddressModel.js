'use strict';

var
	ko = require('knockout'),
	
	AddressUtils = require('core/js/utils/Address.js'),
	Types = require('core/js/utils/Types.js')
;

/**
 * @constructor
 */
function CAddressModel()
{
	this.sName = '';
	/** @type {string} */
	this.sEmail = '';
	
	this.sDisplay = '';
	this.sFull = '';
	
	this.loaded = ko.observable(false);
	this.found = ko.observable(false);
}

/**
 * @param {Object} oData
 */
CAddressModel.prototype.parse = function (oData)
{
	if (oData !== null)
	{
		this.sName = Types.pString(oData.DisplayName);
		this.sEmail = Types.pString(oData.Email);
		this.sDisplay = (this.sName.length > 0) ? this.sName : this.sEmail;
		this.sFull = AddressUtils.getFullEmail(this.sName, this.sEmail);
	}
};

/**
 * @return {string}
 */
CAddressModel.prototype.getEmail = function ()
{
	return this.sEmail;
};

/**
 * @return {string}
 */
CAddressModel.prototype.getName = function ()
{
	return this.sName;
};

/**
 * @return {string}
 */
CAddressModel.prototype.getDisplay = function ()
{
	return this.sDisplay;
};

/**
 * @return {string}
 */
CAddressModel.prototype.getFull = function ()
{
	return this.sFull;
};

module.exports = CAddressModel;
