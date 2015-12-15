'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	FilesUtils = require('core/js/utils/Files.js'),
	CAbstractFileModel = require('core/js/models/CAbstractFileModel.js')
;

/**
 * @constructor
 * @extends CCommonFileModel
 */
function CHelpdeskAttachmentModel()
{
	CAbstractFileModel.call(this);
	
	this.downloadLink = ko.computed(function () {
		return FilesUtils.getDownloadLink('HelpDesk', this.hash());
	}, this);
	
	this.viewLink = ko.computed(function () {
		return FilesUtils.getViewLink('HelpDesk', this.hash());
	}, this);
	
	this.thumbnailLink = ko.computed(function () {
		return FilesUtils.getThumbnailLink('HelpDesk', this.hash());
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