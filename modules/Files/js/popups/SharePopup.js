'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	App = require('modules/Core/js/App.js'),
	CAbstractPopup = require('modules/Core/js/popups/CAbstractPopup.js'),
	
	Ajax = require('modules/%ModuleName%/js/Ajax.js'),
	CFolderModel = require('modules/%ModuleName%/js/models/CFolderModel.js')
;

/**
 * @constructor
 */
function CSharePopup()
{
	CAbstractPopup.call(this);
	
	this.item = null;
	this.pub = ko.observable('');
	this.pubFocus = ko.observable(false);
}

_.extendOwn(CSharePopup.prototype, CAbstractPopup.prototype);

CSharePopup.prototype.PopupTemplate = '%ModuleName%_SharePopup';

/**
 * @param {Object} oItem
 */
CSharePopup.prototype.onShow = function (oItem)
{
	this.item = oItem;
	
	this.pub('');
		
	Ajax.send('CreatePublicLink', {
			'Account': App.defaultAccountId(),
			'Type': oItem.storageType(),
			'Path': oItem.path(),
			'Name': oItem.fileName(),
			'Size': oItem instanceof CFolderModel ? 0 : oItem.size(),
			'IsFolder': oItem instanceof CFolderModel ? '1' : '0'
		}, this.onCreatePublicLinkResponse, this
	);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CSharePopup.prototype.onCreatePublicLinkResponse = function (oResponse, oRequest)
{
	if (oResponse.Result)
	{
		this.pub(oResponse.Result);
		this.pubFocus(true);
		this.item.shared(true);
	}
};

CSharePopup.prototype.onCancelSharingClick = function ()
{
	if (this.item)
	{
		Ajax.send('DeletePublicLink', {
				'Account': App.defaultAccountId(),
				'Type': this.item.storageType(),
				'Path': this.item.path(),
				'Name': this.item.fileName()
			}, this.closePopup, this);
		this.item.shared(false);
	}
};

module.exports = new CSharePopup();