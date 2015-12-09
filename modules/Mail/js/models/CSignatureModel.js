'use strict';

var ko = require('knockout');

/**
 * @constructor
 */
function CSignatureModel()
{
	this.iAccountId = 0;

	this.type = ko.observable(true);
	this.options = ko.observable(0);
	this.signature = ko.observable('');
	
}

/**
 * Calls a recursive parsing of the folder tree.
 * 
 * @param {number} iAccountId
 * @param {Object} oData
 */
CSignatureModel.prototype.parse = function (iAccountId, oData)
{
	this.iAccountId = iAccountId;
	
	this.options(parseInt(oData.Options, 10));
	this.signature(oData.Signature);
};

module.exports = CSignatureModel;