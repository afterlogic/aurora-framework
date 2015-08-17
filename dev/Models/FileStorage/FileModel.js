/**
 * @constructor
 * @extends CCommonFileModel
 */
function CFileModel()
{
	this.id = ko.observable('');
	this.fileName = ko.observable('');
	this.nameForEdit = ko.observable('');
	this.storageType = ko.observable(Enums.FileStorageType.Personal);
	this.lastModified = ko.observable('');
	
	this.path = ko.observable('');
	this.fullPath = ko.observable('');
	this.publicHash = ko.observable('');
	
	this.selected = ko.observable(false);
	this.checked = ko.observable(false);
	this.isFolder = ko.observable(false);
	this.edited = ko.observable(false);

	this.isExternal = ko.observable(false);
	this.isLink = ko.observable(false);
	this.linkType = ko.observable(0);
	this.linkUrl = ko.observable('');
	this.thumbnailExternalLink = ko.observable('');
	
	this.deleted = ko.observable(false); // temporary removal until it was confirmation from the server to delete
	this.recivedAnim = ko.observable(false).extend({'autoResetToFalse': 500});
	this.shared = ko.observable(false);
	this.owner = ko.observable('');
	
	this.ownerHeaderText = ko.computed(function () {
		return Utils.i18n('FILESTORAGE/OWNER_HEADER_EMAIL', {
			'OWNER': this.owner()
		});
	}, this);
	
	this.lastModifiedHeaderText = ko.computed(function () {
		return Utils.i18n('FILESTORAGE/OWNER_HEADER_LAST_MODIFIED_DATE_TEXT', {
			'LASTMODIFIED': this.lastModified()
		});
	}, this);
	
	CCommonFileModel.call(this);
	
	this.fileName.subscribe(function (value) {
		this.nameForEdit(value);
	}, this);

	this.type = this.storageType;
	this.uploaded = ko.observable(true);

	this.downloadLink = ko.computed(function () {
		return Utils.getFilestorageDownloadLinkByHash(
			(AppData.Accounts) ? AppData.Accounts.currentId() : null, 
			this.hash(), 
			this.publicHash()
		);
	}, this);

	this.viewLink = ko.computed(function () {
		
		if (this.isLink())
		{
			return this.linkUrl();
		}
		else
		{
			var sUrl = Utils.getFilestorageViewLinkByHash(
				(AppData.Accounts) ? AppData.Accounts.currentId() : null, 
				this.hash(), 
				this.publicHash()
			);

			return this.iframedView() ? Utils.getIframeWrappwer(
				(AppData.Accounts) ? AppData.Accounts.currentId() : null, sUrl) : sUrl;
		}
		
	}, this);

	this.isViewable = ko.computed(function () {
		
		var 
			bResult = false,
			aViewableArray = [
				'JPEG', 'JPG', 'PNG', 'GIF', 'HTM', 'HTML', 'TXT', 'CSS', 'ASC', 'JS', 'PDF', 'BMP'
			]
		;
		
		if (_.indexOf(aViewableArray, this.extension().toUpperCase()) >= 0)
		{
			bResult = true;
		}

		return (this.iframedView() || bResult || (this.isLink())) && !this.isPopupItem();

	}, this);
	
	this.visibleViewLink = ko.computed(function () {
		return (this.oembed() !== '' || this.linkUrl() === '') && this.isViewable();
	}, this);

	this.thumbnailLink = ko.computed(function () {
		
		if (this.isExternal() || (this.isLink() && (this.linkType() === Enums.FileStorageLinkType.GoogleDrive || this.linkType() === Enums.FileStorageLinkType.YouTube || this.linkType() === Enums.FileStorageLinkType.Vimeo || this.linkType() === Enums.FileStorageLinkType.SoundCloud)))
		{
			return this.thumbnailExternalLink();
		}
		else
		{
			return this.thumb() ? Utils.getFilestorageViewThumbnailLinkByHash(this.accountId(), this.hash(), this.publicHash()) : '';
		}
	}, this);

	this.edited.subscribe(function (value) {
		if (value === false)
		{
			this.nameForEdit(this.fileName());
		}
	}, this);


	this.canShare = ko.computed(function () {
		return (this.storageType === Enums.FileStorageType.Personal || this.storageType === Enums.FileStorageType.Corporate);
	}, this);
	
	//this.oembedHtml = ko.observable('');
}

Utils.extend(CFileModel, CCommonFileModel);

/**
 * @returns {CFileModel}
 */
CFileModel.prototype.getInstance = function ()
{
	return new CFileModel();
};

CFileModel.prototype.parse = function (oData, sPublicHash)
{
	var oDateModel = new CDateModel();
	
	this.isFolder(!!oData.IsFolder);
	this.isLink(!!oData.IsLink);
	this.fileName(Utils.pString(oData.Name));
	this.id(Utils.pString(oData.Id));
	this.path(Utils.pString(oData.Path));
	this.fullPath(Utils.pString(oData.FullPath));
	this.storageType(Utils.pString(oData.Type));
	this.shared(!!oData.Shared);
	this.isExternal(!!oData.IsExternal);

	this.iframedView(!!oData.Iframed);
	
	if (this.isLink())
	{
		this.linkUrl(Utils.pString(oData.LinkUrl));
		this.linkType(Utils.pInt(oData.LinkType));
	}
	
	if (!this.isFolder())
	{
		this.size(Utils.pInt(oData.Size));
		oDateModel.parse(oData['LastModified']);
		this.lastModified(oDateModel.getShortDate());
		this.owner(Utils.pString(oData.Owner));
		this.thumb(!!oData.Thumb);
		this.thumbnailExternalLink(Utils.pString(oData.ThumbnailLink));
		this.hash(Utils.pString(oData.Hash));
		this.publicHash(sPublicHash);
		this.oembedHtml(oData.OembedHtml ? oData.OembedHtml : '');
	}
	
	if(this.thumb() && this.thumbnailExternalLink() === '')
	{
		Utils.thumbQueue(this.thumbnailSessionUid(), this.thumbnailLink(), this.thumbnailSrc);
	}
	
	this.content(Utils.pString(oData.Content));
};

/**
 * Fills attachment data for upload.
 * 
 * @param {string} sFileUid
 * @param {Object} oFileData
 * @param {string} sFileName
 * @param {string} sOwner
 * @param {string} sPath
 * @param {string} sStorageType
 */
CFileModel.prototype.onUploadSelectOwn = function (sFileUid, oFileData, sFileName, sOwner, sPath, sStorageType)
{
	var
		oDateModel = new CDateModel(),
		oDate = new Date()
	;
	
	this.onUploadSelect(sFileUid, oFileData);
	
	oDateModel.parse(oDate.getTime() /1000);
	this.fileName(sFileName);
	this.lastModified(oDateModel.getShortDate());
	this.owner(sOwner);
	this.path(sPath);
	this.storageType(sStorageType);
};
