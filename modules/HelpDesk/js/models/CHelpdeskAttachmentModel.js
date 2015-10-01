'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	
	Utils = require('core/js/utils/Common.js'),
	App = require('core/js/App.js'),
	UserSettings = require('core/js/Settings.js'),
	CAbstractFileModel = require('core/js/models/CAbstractFileModel.js'),
	
	bExtApp = false
;

/**
 * @constructor
 * @extends CCommonFileModel
 */
function CHelpdeskAttachmentModel()
{
	CAbstractFileModel.call(this);
	
	this.downloadLink = ko.computed(function () {
		var
			iAccountId = !bExtApp && App.currentAccountId ? App.currentAccountId() : 0,
			sTenantHash = bExtApp && UserSettings.TenantHash
		;
		return Utils.getDownloadLinkByHash(iAccountId, this.hash(), bExtApp, sTenantHash);
	}, this);
	
	this.viewLink = ko.computed(function () {
		var
			iAccountId = !bExtApp && App.currentAccountId ? App.currentAccountId() : 0,
			sTenantHash = bExtApp && UserSettings.TenantHash
		;
		return Utils.getViewLinkByHash(iAccountId, this.hash(), bExtApp, sTenantHash);
	}, this);
	
	this.thumbnailLink = ko.computed(function () {
		var
			iAccountId = !bExtApp && App.currentAccountId ? App.currentAccountId() : 0,
			sTenantHash = bExtApp && UserSettings.TenantHash,
			sLink = this.thumb() ? Utils.getViewThumbnailLinkByHash(iAccountId, this.hash(), bExtApp, sTenantHash) : ''
		;
		return sLink;
	}, this);
}

_.extend(CHelpdeskAttachmentModel.prototype, CAbstractFileModel.prototype);

CHelpdeskAttachmentModel.prototype.dataObjectName = 'Object/CHelpdeskAttachment';

/**
 * @returns {CHelpdeskAttachmentModel}
 */
CHelpdeskAttachmentModel.prototype.getInstance = function ()
{
	return new CHelpdeskAttachmentModel();
};

/**
 * @param {Object} oResult
 */
CHelpdeskAttachmentModel.prototype.fillDataAfterUploadComplete = function (oResult)
{
	this.tempName(oResult.Result.HelpdeskFile.TempName);
	this.type(oResult.Result.HelpdeskFile.MimeType);
	this.hash(oResult.Result.HelpdeskFile.Hash);
};

module.exports = CHelpdeskAttachmentModel;