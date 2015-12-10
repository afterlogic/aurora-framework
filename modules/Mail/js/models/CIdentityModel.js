'use strict';

var
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js'),
	AddressUtils = require('core/js/utils/Address.js')
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
		this.email(Utils.pString(oData.Email));
		this.friendlyName(Utils.pString(oData.FriendlyName));
		this.accountId(Utils.pInt(oData.IdAccount));
		this.id(Utils.pInt(oData.IdIdentity));
		this.signature(Utils.pString(oData.Signature));
		this.useSignature(!!oData.UseSignature);
	}
};

module.exports = CIdentityModel;