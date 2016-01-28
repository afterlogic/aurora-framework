'use strict';

var
	ko = require('knockout'),
	
	AddressUtils = require('core/js/utils/Address.js'),
	Types = require('core/js/utils/Types.js')
;

/**
 * @constructor
 */
function CIdentityModel()
{
	this.loyal = ko.observable(false);
	this.isDefault = ko.observable(false);
	this.enabled = ko.observable(true);
	this.email = ko.observable('');
	this.friendlyName = ko.observable('');
	this.fullEmail = ko.computed(function () {
		return AddressUtils.getFullEmail(this.friendlyName(), this.email());
	}, this);
	this.accountId = ko.observable(-1);
	this.id = ko.observable(-1);
	this.signature = ko.observable('');
	this.useSignature = ko.observable(false);
}

/**
 * @param {Object} oData
 */
CIdentityModel.prototype.parse = function (oData)
{
	if (oData['@Object'] === 'Object/CIdentity')
	{
		this.loyal(!!oData.Loyal);
		this.isDefault(!!oData.Default);
		this.enabled(!!oData.Enabled);
		this.email(Types.pString(oData.Email));
		this.friendlyName(Types.pString(oData.FriendlyName));
		this.accountId(Types.pInt(oData.IdAccount));
		this.id(Types.pInt(oData.IdIdentity));
		this.signature(Types.pString(oData.Signature));
		this.useSignature(!!oData.UseSignature);
	}
};

module.exports = CIdentityModel;
