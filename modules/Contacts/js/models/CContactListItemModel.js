'use strict';

var
	ko = require('knockout'),
	Utils = require('core/js/utils/Common.js'),
	AddressUtils = require('core/js/utils/Address.js')
;

/**
 * @constructor
 */
function CContactListItemModel()
{
	this.bIsGroup = false;
	this.bIsOrganization = false;
	this.bReadOnly = false;
	this.bItsMe = false;
	this.bGlobal = false;
	this.sId = '';
	this.sName = '';
	this.sEmail = '';
	this.bSharedToAll = false;

	this.deleted = ko.observable(false);
	this.checked = ko.observable(false);
	this.selected = ko.observable(false);
	this.recivedAnim = ko.observable(false).extend({'autoResetToFalse': 500});
	this.groupType = ko.observable([]);
}

/**
 *
 * @param {Object} oData
 */
CContactListItemModel.prototype.parse = function (oData)
{
	this.sId = Utils.pString(oData.Id);
	this.sName = Utils.pString(oData.Name);
	this.sEmail = Utils.pString(oData.Email);

	this.bIsGroup = !!oData.IsGroup;
	this.bIsOrganization = !!oData.IsOrganization;
	this.bReadOnly = !!oData.ReadOnly;
	this.bItsMe = !!oData.ItsMe;
	this.bGlobal = !!oData.Global;
	this.bSharedToAll =  !!oData.SharedToAll;
	this.groupType(this.getGroupType(oData));
};

/**
 * @return {boolean}
 */
CContactListItemModel.prototype.IsGroup = function ()
{
	return this.bIsGroup;
};

/**
 * @return {boolean}
 */
CContactListItemModel.prototype.Global = function ()
{
	return this.bGlobal;
};

/**
 * @return {boolean}
 */
CContactListItemModel.prototype.ReadOnly = function ()
{
	return this.bReadOnly;
};

/**
 * @return {boolean}
 */
CContactListItemModel.prototype.ItsMe = function ()
{
	return this.bItsMe;
};

/**
 * @return {string}
 */
CContactListItemModel.prototype.Id = function ()
{
	return this.sId;
};

/**
 * @return {string}
 */
CContactListItemModel.prototype.Name = function ()
{
	return this.sName;
};

/**
 * @return {string}
 */
CContactListItemModel.prototype.Email = function ()
{
	return this.sEmail;
};

/**
 * @return {string}
 */
CContactListItemModel.prototype.getFullEmail = function ()
{
	return AddressUtils.getFullEmail(this.sName, this.sEmail);
};

/**
 * @return {boolean}
 */
CContactListItemModel.prototype.IsSharedToAll = function ()
{
	return this.bSharedToAll;
};

/**
 * @return {boolean}
 */
CContactListItemModel.prototype.IsOrganization = function ()
{
	return this.bIsOrganization;
};

CContactListItemModel.prototype.getGroupType = function (oData)
{
	if (oData.SharedToAll)
	{
		return Enums.ContactsGroupListType.SharedToAll;
	}
	else if (oData.Global)
	{
		return Enums.ContactsGroupListType.Global;
	}
	else if (!oData.Global)
	{
		return Enums.ContactsGroupListType.Personal;
	}

	return null;
};

module.exports = CContactListItemModel;