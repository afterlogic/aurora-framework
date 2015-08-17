/**
 * @constructor
 * @extends CCommonFileModel
 */
function CHelpdeskAttachmentModel()
{
	CCommonFileModel.call(this);
	
	this.downloadLink = ko.computed(function () {
		var
			iAccountId = !bExtApp && AppData && AppData.Accounts ? AppData.Accounts.currentId() : 0,
			sTenantHash = bExtApp && AppData ? AppData.TenantHash : ''
		;
		return Utils.getDownloadLinkByHash(iAccountId, this.hash(), bExtApp, sTenantHash);
	}, this);
	
	this.viewLink = ko.computed(function () {
		var
			iAccountId = !bExtApp && AppData && AppData.Accounts ? AppData.Accounts.currentId() : 0,
			sTenantHash = bExtApp && AppData ? AppData.TenantHash : ''
		;
		return Utils.File.getViewLinkByHash(iAccountId, this.hash(), bExtApp, sTenantHash);
	}, this);
	
	this.thumbnailLink = ko.computed(function () {
		var
			iAccountId = !bExtApp && AppData && AppData.Accounts ? AppData.Accounts.currentId() : 0,
			sTenantHash = bExtApp && AppData ? AppData.TenantHash : '',
			sLink = this.thumb() ? Utils.getViewThumbnailLinkByHash(iAccountId, this.hash(), bExtApp, sTenantHash) : ''
		;
		return sLink;
	}, this);
}

Utils.extend(CHelpdeskAttachmentModel, CCommonFileModel);

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
