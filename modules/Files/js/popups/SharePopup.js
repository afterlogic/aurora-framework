'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	
	App = require('core/js/App.js'),
	Ajax = require('core/js/Ajax.js'),
	CAbstractPopup = require('core/js/popups/CAbstractPopup.js')
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

CSharePopup.prototype.PopupTemplate = 'Files_SharePopup';

/**
 * @param {Object} oItem
 */
CSharePopup.prototype.onShow = function (oItem)
{
	this.item = oItem;
	
	this.pub('');
		
	Ajax.send({
			'Action': 'FilesCreatePublicLink',
			'Account': App.defaultAccountId(),
			'Type': oItem.storageType(),
			'Path': oItem.path(),
			'Name': oItem.fileName(),
			'Size': oItem.size(),
			'IsFolder': oItem.isFolder() ? '1' : '0'
		}, this.onFilesCreatePublicLinkResponse, this
	);
};

/**
 * @param {Object} oResult
 * @param {Object} oRequest
 */
CSharePopup.prototype.onFilesCreatePublicLinkResponse = function (oResult, oRequest)
{
	if (oResult.Result)
	{
		this.pub(oResult.Result);
		this.pubFocus(true);
		this.item.shared(true);
	}
};

CSharePopup.prototype.onCancelSharingClick = function ()
{
	if (this.item)
	{
		Ajax.send({
				'Action': 'FilesPublicLinkDelete',
				'Account': App.defaultAccountId(),
				'Type': this.item.storageType(),
				'Path': this.item.path(),
				'Name': this.item.fileName()
			}, this.closePopup, this);
		this.item.shared(false);
	}
};

module.exports = new CSharePopup();