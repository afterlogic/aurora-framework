'use strict';

var
	_ = require('underscore'),
	
	CAbstractFileModel = require('modules/CoreClient/js/models/CAbstractFileModel.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * @constructor
 * @extends CCommonFileModel
 */
function CHelpdeskAttachmentModel()
{
	CAbstractFileModel.call(this, Settings.ServerModuleName);
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