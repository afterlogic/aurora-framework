'use strict';

var
	ko = require('knockout'),
	
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
	Types = require('modules/CoreClient/js/utils/Types.js'),
	
	CDateModel = require('modules/CoreClient/js/models/CDateModel.js'),
	
	CHelpdeskAttachmentModel = require('modules/%ModuleName%/js/models/CHelpdeskAttachmentModel.js')
;

/**
 * @constructor
 */
function CPostModel()
{
	this.Id = null;
	this.IdThread = null;
	this.IdOwner = null;
	this.sFrom = '';
	this.sDate = '';
	this.iType = null;
	this.bSysType = false;
	this.bThreadOwner = null;
	this.sText = '';
	this.collapsed = ko.observable(false);
	
	this.attachments = ko.observableArray([]);
	
	this.allowDownloadAttachmentsLink = true;

	this.itsMe = ko.observable(false);
	this.canBeDeleted = this.itsMe;
}

/**
 * @param {AjaxPostResponse} oData
 */
CPostModel.prototype.parse = function (oData)
{
	this.Id = oData.IdHelpdeskPost;
	this.IdThread = oData.IdHelpdeskThread;
	this.IdOwner = oData.IdOwner;
	this.bThreadOwner = oData.IsThreadOwner;
	this.sFrom = Types.isNonEmptyArray(oData.Owner) ? oData.Owner[1] || oData.Owner[0] || '' : TextUtils.i18n('%MODULENAME%/LABEL_DELETED_USER');
	this.sDate = CDateModel.prototype.convertDate(oData.Created);
	this.iType = oData.Type;
	this.bSysType = oData.SystemType;
	this.sText = Types.pString(oData.Text);

	this.itsMe(oData.ItsMe);

	if (oData.Attachments)
	{
		var 
			iIndex = 0,
			iLen = 0,
			oObject = null,
			aList = [],
			sThumbSessionUid = Date.now().toString()
		;

		for (iLen = oData.Attachments.length; iIndex < iLen; iIndex++)
		{
			if (oData.Attachments[iIndex] && 'Object/CHelpdeskAttachment' === oData.Attachments[iIndex]['@Object'])
			{
				oObject = new CHelpdeskAttachmentModel();
				oObject.parse(oData.Attachments[iIndex]);
				oObject.getInThumbQueue(sThumbSessionUid);

				aList.push(oObject);

			}
		}
		
		this.attachments(aList);
	}
};

module.exports = CPostModel;
