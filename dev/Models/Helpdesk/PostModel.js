
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
	this.sFrom = Utils.isNonEmptyArray(oData.Owner) ? oData.Owner[1] || oData.Owner[0] || '' : Utils.i18n('HELPDESK/THREAD_DELETED_USER');
	this.sDate = CDateModel.prototype.convertDate(oData.Created);
	this.iType = oData.Type;
	this.bSysType = oData.SystemType;
	this.sText = Utils.pString(oData.Text);

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
			if (oData.Attachments[iIndex] && 'Object/CHelpdeskAttachment' === Utils.pExport(oData.Attachments[iIndex], '@Object', ''))
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
