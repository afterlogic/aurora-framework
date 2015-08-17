/**
* @constructor
* @param {boolean=} bPopup = false
*/
function CFileStorageViewModel(bPopup)
{
	this.allowSendEmails = ko.computed(function () {
		return !!(AppData.App && AppData.App.AllowWebMail && AppData.Accounts && AppData.Accounts.isCurrentAllowsMail());
	}, this);
	
	this.error = ko.observable(false);
	this.loaded = ko.observable(false);
	this.isPublic = bExtApp;
	this.publicHash = bExtApp ? AppData.FileStoragePubHash : '';
	this.IsCollaborationSupported = AppData.User.IsCollaborationSupported;
	this.AllowFilesSharing = AppData.User.AllowFilesSharing;
	
	this.storages = ko.observableArray();
	this.folders = ko.observableArray();
	this.files = ko.observableArray();
	this.uploadingFiles = ko.observableArray();

	this.selected = ko.observable(false);
	
	this.rootPath = ko.observable(Utils.i18n('FILESTORAGE/TAB_PERSONAL_FILES'));
	this.storageType = ko.observable(Enums.FileStorageType.Personal);
	this.storageType.subscribe(function () {
		var 
			oStorage = null
		;
		if (this.isPublic)
		{
			this.rootPath(AppData.FileStoragePubParams.Name);
		}
		else
		{
			oStorage = this.getStorageByType(this.storageType());
			if (oStorage)
			{
				this.rootPath(oStorage.displayName());
			}
		}
		this.selector.listCheckedAndSelected(false);
	}, this);
	
	this.iPathIndex = ko.observable(-1);
	this.pathItems = ko.observableArray();
	this.dropPath = ko.observable('');
	this.path = ko.computed(function () {
		var aPath = _.map(this.pathItems(), function (oItem) {
			return oItem.id();
		});
		return aPath.join('/');
	}, this);

	this.path.subscribe(function (value) {
		this.dropPath(value);
	}, this);

	this.collection = ko.computed(function () {
		var files = _.union(this.files(), this.getUploadingFiles());

		files.sort(function(left, right) { 
			return left.fileName() === right.fileName() ? 0 : (left.fileName() < right.fileName() ? -1 : 1); 
		});
		
		return _.union(this.folders(), files);
	}, this);
	
	this.columnCount = ko.observable(1);
	
	this.selector = new CSelector(this.collection, null,
		_.bind(this.onItemDelete, this), _.bind(this.onItemDblClick, this), _.bind(this.onEnter, this), this.columnCount, true, true, true);
		
	this.searchPattern = ko.observable('');
	this.isSearchFocused = ko.observable(false);

	this.renameCommand = Utils.createCommand(this, this.executeRename, function () {
		var items = this.selector.listCheckedAndSelected();
		//return (1 === items.length && !items[0].isLink());
		return (1 === items.length);
	});
	this.deleteCommand = Utils.createCommand(this, this.executeDelete, function () {
		var items = this.selector.listCheckedAndSelected();
		return (0 < items.length);
	});
	this.downloadCommand = Utils.createCommand(this, this.executeDownload, function () {
		var items = this.selector.listCheckedAndSelected();
		return (1 === items.length && !items[0].isFolder());
	});
	this.shareCommand = Utils.createCommand(this, this.executeShare, function () {
		var items = this.selector.listCheckedAndSelected();
		return (1 === items.length && !items[0].isLink());
	});
	this.sendCommand = Utils.createCommand(this, this.executeSend, function () {
		var
			aItems = this.selector.listCheckedAndSelected(),
			aFileItems = _.filter(aItems, function (oItem) {
				return !oItem.isFolder();
			}, this)
		;
		return (aFileItems.length > 0);
	});
	
	this.uploaderButton = ko.observable(null);
	this.uploaderArea = ko.observable(null);
	this.bDragActive = ko.observable(false);//.extend({'throttle': 1});
//	this.bDragActive.subscribe(function () {
//		if (this.searchPattern() !== '')
//		{
//			this.bDragActive(false);
//		}
//	}, this);

	this.bDragActiveComp = ko.computed(function () {
		var bDrag = this.bDragActive();
		return bDrag && this.searchPattern() === '';
	}, this);
	
	this.bAllowDragNDrop = false;
	
	this.uploadError = ko.observable(false);
	
	this.quota = ko.observable(0);
	this.used = ko.observable(0);
	this.quotaDesc = ko.observable('');
	this.quotaProc = ko.observable(-1);
	
	ko.computed(function () {
		
		if (!AppData.App || AppData.App && !AppData.App.ShowQuotaBar)
		{
			return true;
		}

		var
			iQuota = this.quota(),
			iUsed = this.used(),
			iProc = 0 < iQuota ? Math.ceil((iUsed / iQuota) * 100) : -1;

		iProc = 100 < iProc ? 100 : iProc;
		
		this.quotaProc(iProc);
		this.quotaDesc(-1 < iProc ?
			Utils.i18n('MAILBOX/QUOTA_TOOLTIP', {
				'PROC': iProc,
				'QUOTA': Utils.friendlySize(iQuota)
			}) : '');

		return true;
		
	}, this);
	
	this.dragover = ko.observable(false);
	
	this.loading = ko.observable(false);
    this.loadedFiles = ko.observable(false);

	this.fileListInfoText = ko.computed(function () {
		var sInfoText = '';
		
		if (this.loading())
		{
            sInfoText = Utils.i18n('FILESTORAGE/INFO_LOADING');
		}
		else if (this.loadedFiles())
		{
			if (this.collection().length === 0)
			{
				if (this.isPublic)
				{
					sInfoText = Utils.i18n('FILESTORAGE/INFO_PUBLIC_FOLDER_NOT_EXIST');
				}
				else
				{
					if (this.searchPattern() !== '' || this.isPublic)
					{
                        sInfoText = Utils.i18n('FILESTORAGE/INFO_NO_ITEMS_FOUND');
					}
					else
					{
						if (this.path() !== '' || this.isPopup)
						{
                            sInfoText = Utils.i18n('FILESTORAGE/INFO_FOLDER_IS_EMPTY');
						}
						else if (this.bAllowDragNDrop)
						{
                            sInfoText = Utils.i18n('FILESTORAGE/INFO_FILESTORAGE_IS_EMPTY');
						}
					}
				}
			}
		}
		else if (this.error())
		{
            sInfoText = Utils.i18n('FILESTORAGE/ERROR_FILESTORAGE');
		}
		
		return sInfoText;
	}, this);
	
	this.dragAndDropHelperBinded = _.bind(this.dragAndDropHelper, this);
	this.isPopup = !!bPopup;
	this.isCurrentStorageExternal = ko.computed(function () {
		var oStorage = this.getStorageByType(this.storageType());
		return (oStorage && oStorage.isExternal());
	}, this);
	this.timerId = null;
}

CFileStorageViewModel.prototype.__name = 'CFileStorageViewModel';

/**
 * @param {Object} $viewModel
 */
CFileStorageViewModel.prototype.onApplyBindings = function ($viewModel)
{
	this.selector.initOnApplyBindings(
		'.items_sub_list .item',
		'.items_sub_list .selected.item',
		'.items_sub_list .item .custom_checkbox',
		$('.panel.files .items_list', $viewModel),
		$('.panel.files .items_list .files_scroll.scroll-inner', $viewModel)
	);
		
	this.initUploader();

	this.hotKeysBind();
};

CFileStorageViewModel.prototype.hotKeysBind = function ()
{
	var bIsFileStorageScreen = App.Screens.currentScreen() === Enums.Screens.FileStorage;

	$(document).on('keydown', _.bind(function(ev) {
		if (bIsFileStorageScreen && ev && ev.keyCode === Enums.Key.s && this.selector.useKeyboardKeys() && !Utils.isTextFieldFocused()) {
			ev.preventDefault();
			this.isSearchFocused(true);
		}
	}, this));
};

/**
 * Initializes file uploader.
 */
CFileStorageViewModel.prototype.initUploader = function ()
{
	var self = this;
	
	if (this.uploaderButton() && this.uploaderArea())
	{
		this.oJua = new Jua({
			'action': '?/Upload/File/',
			'name': 'jua-uploader',
			'queueSize': 2,
			'clickElement': this.uploaderButton(),
			'hiddenElementsPosition': Utils.isRTL() ? 'right' : 'left',
			'dragAndDropElement': this.uploaderArea(),
			'disableAjaxUpload': this.isPublic ? true : false,
			'disableFolderDragAndDrop': this.isPublic ? true : false,
			'disableDragAndDrop': this.isPublic ? true : false,
			'hidden': {
				'Token': function () {
					return AppData.Token;
				},
				'AccountID': function () {
					return AppData.Accounts.currentId();
				},
				'AdditionalData':  function (oFile) {
					return JSON.stringify({
						'Type': self.storageType(),
						'SubPath': oFile && !Utils.isUnd(oFile['Folder']) ? oFile['Folder'] : '',
						'Path': self.dropPath()
					});
				}
			}
		});

		this.oJua
			.on('onProgress', _.bind(this.onFileUploadProgress, this))
			.on('onSelect', _.bind(this.onFileUploadSelect, this))
			.on('onStart', _.bind(this.onFileUploadStart, this))
			.on('onDrop', _.bind(this.onDrop, this))
			.on('onComplete', _.bind(this.onFileUploadComplete, this))
			.on('onBodyDragEnter', _.bind(this.bDragActive, this, true))
			.on('onBodyDragLeave', _.bind(this.bDragActive, this, false))
		;
		
        this.bAllowDragNDrop = this.oJua.isDragAndDropSupported();
	}
};

/**
 * Creates new attachment for upload.
 *
 * @param {string} sFileUid
 * @param {Object} oFileData
 */
CFileStorageViewModel.prototype.onFileUploadSelect = function (sFileUid, oFileData)
{
	if (AppData.App.FileSizeLimit > 0 && oFileData.Size/(1024*1024) > AppData.App.FileSizeLimit)
	{
		App.Screens.showPopup(AlertPopup, [
			Utils.i18n('FILESTORAGE/ERROR_SIZE_LIMIT', {'SIZE': AppData.App.FileSizeLimit})
		]);
		return false;
	}	
	
	if (this.searchPattern() === '')
	{
		var 
			oFile = new CFileModel(),
			sFileName = oFileData.FileName,
			sFileNameExt = Utils.getFileExtension(sFileName),
			sFileNameWoExt = Utils.getFileNameWithoutExtension(sFileName),
			iIndex = 0,
			oAccount = AppData.Accounts.getDefault()
		;
		
		if (sFileNameExt !== '')
		{
			sFileNameExt = '.' + sFileNameExt;
		}

		while (!Utils.isUnd(this.getFileByName(sFileName)))
		{
			sFileName = sFileNameWoExt + '_' + iIndex + sFileNameExt;
			iIndex++;
		}
		
		oFile.onUploadSelectOwn(sFileUid, oFileData, sFileName, oAccount.email(), this.path(), this.storageType());
		
		this.uploadingFiles.push(oFile);
	}
};

/**
 * Finds attachment by uid. Calls it's function to start upload.
 *
 * @param {string} sFileUid
 */
CFileStorageViewModel.prototype.onFileUploadStart = function (sFileUid)
{
	var oFile = this.getUploadFileByUid(sFileUid);

	if (oFile)
	{
		oFile.onUploadStart();
	}
};

/**
 * Finds attachment by uid. Calls it's function to progress upload.
 *
 * @param {string} sFileUid
 * @param {number} iUploadedSize
 * @param {number} iTotalSize
 */
CFileStorageViewModel.prototype.onFileUploadProgress = function (sFileUid, iUploadedSize, iTotalSize)
{
	if (this.searchPattern() === '')
	{
		var oFile = this.getUploadFileByUid(sFileUid);

		if (oFile)
		{
			oFile.onUploadProgress(iUploadedSize, iTotalSize);
		}
	}
};

/**
 * Finds attachment by uid. Calls it's function to complete upload.
 *
 * @param {string} sFileUid
 * @param {boolean} bResponseReceived
 * @param {Object} oResult
 */
CFileStorageViewModel.prototype.onFileUploadComplete = function (sFileUid, bResponseReceived, oResult)
{
	if (this.searchPattern() === '')
	{
		var
			oFile = this.getUploadFileByUid(sFileUid)
		;
		
		if (oFile)
		{
			oFile.onUploadComplete(sFileUid, bResponseReceived, oResult);
			
			this.deleteUploadFileByUid(sFileUid);
			
			if (oFile.uploadError())
			{
				this.uploadError(true);
				App.Api.showError(oFile.statusText());
			}
			else
			{
				this.files.push(oFile);
				if (this.uploadingFiles().length === 0)
				{
					App.Api.showReport(Utils.i18n('COMPOSE/UPLOAD_COMPLETE'));
				}
			}
		}

		this.getFiles(this.storageType(), this.getPathItemByIndex(this.iPathIndex()), this.searchPattern(), false);
	}
};

/**
 * @param {Object} oFile
 * @param {Object} oEvent
 */
CFileStorageViewModel.prototype.onDrop = function (oFile, oEvent)
{
	if (this.isPublic)
	{
		return;
	}
		
	if (oEvent && oEvent.target && this.searchPattern() === '')
	{
		var oFolder = ko.dataFor(oEvent.target);
		if (oFolder && oFolder instanceof CFileModel && oFolder.isFolder())
		{
			this.dropPath(oFolder.fullPath());
		}
	}
	else
	{
		App.Api.showReport(Utils.i18n('FILESTORAGE/INFO_CANNOT_UPLOAD_SEARCH_RESULT'));
	}
};

/**
 * @param {Object} oFolder
 * @param {Object} oEvent
 * @param {Object} oUi
 */
CFileStorageViewModel.prototype.filesDrop = function (oFolder, oEvent, oUi)
{
	if (this.isPublic)
	{
		return;
	}

	if (oFolder && oEvent)
	{
		var
			self = this,
			sFromPath = '',
			bFolderIntoItself = false,
			sToPath = oFolder.fullPath(),
			aChecked = [],
			aItems = []
		;
		
		if (this.path() !== sToPath && this.storageType() === oFolder.storageType() || this.storageType() !== oFolder.storageType())
		{
			oFolder.recivedAnim(true);
			Utils.uiDropHelperAnim(oEvent, oUi);

			aChecked = this.selector.listCheckedAndSelected();
			_.each(aChecked, function (oItem) {
				sFromPath = oItem.path();
				bFolderIntoItself = oItem.isFolder() && sToPath === sFromPath + '/' + oItem.id();
				if (!bFolderIntoItself)
				{
					if (!oEvent.ctrlKey)
					{
						if (!oItem.isFolder())
						{
							self.deleteFileByName(oItem.id());
						}
						else
						{
							self.deleteFolderByName(oItem.fileName());
						}
					}
					aItems.push({
						'Name':  oItem.id(),
						'IsFolder': oItem.isFolder()
					});
				}
			});
			
			if (aItems.length > 0)
			{
				App.Ajax.send({
						'Action': oEvent.ctrlKey ? 'FilesCopy' : 'FilesMove',
						'FromType': this.storageType(),
						'ToType': oFolder.storageType(),
						'FromPath': sFromPath,
						'ToPath': sToPath,
						'Files': JSON.stringify(aItems)
					},
					this.onFilesMoveResponse,
					this
				);
			}
		}
	}
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CFileStorageViewModel.prototype.onFilesMoveResponse = function (oData, oParameters)
{
	this.getQuota(this.storageType());
};

/**
 * @param {Object} oFile
 */
CFileStorageViewModel.prototype.dragAndDropHelper = function (oFile)
{
	if (oFile)
	{
		oFile.checked(true);
	}

	var
		oHelper = Utils.draggableMessages(),
		aItems = this.selector.listCheckedAndSelected(),
		nCount = aItems.length,
		nFilesCount = 0,
		nFoldersCount = 0,
		sText = '';
	
	_.each(aItems, function (oItem) {
		if (oItem.isFolder())
		{
			nFoldersCount++;
		}
		else
		{
			nFilesCount++;
		}

	}, this);
	
	if (nFilesCount !== 0 && nFoldersCount !== 0)
	{
		sText = Utils.i18n('FILESTORAGE/DRAG_ITEMS_TEXT_PLURAL', {'COUNT': nCount}, null, nCount);
	}
	else if (nFilesCount === 0)
	{
		sText = Utils.i18n('FILESTORAGE/DRAG_FOLDERS_TEXT_PLURAL', {'COUNT': nFoldersCount}, null, nFoldersCount);
	}
	else if (nFoldersCount === 0)
	{
		sText = Utils.i18n('FILESTORAGE/DRAG_TEXT_PLURAL', {'COUNT': nFilesCount}, null, nFilesCount);
	}
	
	$('.count-text', oHelper).text(sText);

	return oHelper;
};

CFileStorageViewModel.prototype.onItemDelete = function ()
{
	this.executeDelete();
};

/**
 * @param {{isFolder:Function,path:Function,name:Function,isViewable:Function,viewFile:Function,downloadFile:Function}} oItem
 */
CFileStorageViewModel.prototype.onEnter = function (oItem)
{
	this.onItemDblClick(oItem);
};

/**
 * @param {{isFolder:Function,path:Function,name:Function,isViewable:Function,viewFile:Function,downloadFile:Function}} oItem
 */
CFileStorageViewModel.prototype.onItemDblClick = function (oItem)
{
	if (oItem)
	{
		if (oItem.isFolder())
		{
			this.getFiles(this.storageType(), oItem);
		}
		else
		{
			if (oItem.isViewable())
			{
				oItem.viewFile();
			}
			else
			{
				if (this.isPopup)
				{
					if (this.onSelectClickPopupBinded)
					{
						this.onSelectClickPopupBinded();
					}
				}
				else
				{
					oItem.downloadFile();
				}
			}
		}
	}
};

/**
 * @param {AjaxDefaultResponse} oData
 * @param {Object} oParameters
 */
CFileStorageViewModel.prototype.onFilesResponse = function (oData, oParameters)
{
	if (oData.Result)
	{
		var 
			aFolderList = [],
			aFileList = [],
			sThumbSessionUid = Date.now().toString()
		;

		if (oData.Result.Quota)
		{
			this.quota(oData.Result.Quota[0] + oData.Result.Quota[1]);
			this.used(oData.Result.Quota[0]);
		}
		
		_.each(oData.Result.Items, function (oValue) {
			var oItem = new CFileModel()
				.allowDrag(true)
				.allowSelect(true)
				.allowCheck(true)
				.allowDelete(true)
				.allowUpload(true)
				.allowSharing(true)
				.allowHeader(true)
				.allowDownload(true)
				.isPopupItem(this.isPopup);
				
			oItem.parse(oValue, this.publicHash);
			
			oItem.getInThumbQueue(sThumbSessionUid);
			if (oItem.isFolder())
			{
				aFolderList.push(oItem);
			}
			else
			{
				aFileList.push(oItem);
			}
		}, this);
		
		if (this.isPublic || oParameters.Type === this.storageType())
		{
			this.folders(aFolderList);
			this.files(aFileList);
		}
		
		this.loading(false);
        this.loadedFiles(true);
		clearTimeout(this.timerId);
	}
	else
	{
		this.loading(false);
		this.error(true);
	}
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CFileStorageViewModel.prototype.onQuotaResponse = function (oData, oParameters)
{
	if (oData.Result && oData.Result.Quota)
	{
		this.quota(oData.Result.Quota[0] + oData.Result.Quota[1]);
		this.used(oData.Result.Quota[0]);
	}
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CFileStorageViewModel.prototype.onFilesDeleteResponse = function (oData, oParameters)
{
	if (oData.Result)
	{
		this.expungeFileItems();
	}
	else
	{
		this.getFiles(this.storageType(), this.getPathItemByIndex(this.iPathIndex()), this.searchPattern());
	}
};

CFileStorageViewModel.prototype.executeRename = function ()
{
	var
		aChecked = this.selector.listCheckedAndSelected()
	;
	if (!this.isPublic && aChecked[0])
	{
		App.Screens.showPopup(FileStorageRenamePopup, [aChecked[0], _.bind(this.renameItem, this)]);
	}
};

CFileStorageViewModel.prototype.executeDownload = function ()
{
	var 
		aChecked = this.selector.listCheckedAndSelected()
	;
	if (aChecked[0] && !aChecked[0].isFolder())
	{
		aChecked[0].downloadFile();
	}
};

CFileStorageViewModel.prototype.executeShare = function ()
{
	var 
		aChecked = this.selector.listCheckedAndSelected()
	;
	if (!this.isPublic &&  aChecked[0])
	{
		App.Screens.showPopup(FileStorageSharePopup, [aChecked[0]]);
	}
};

CFileStorageViewModel.prototype.executeSend = function ()
{
	var
		aItems = this.selector.listCheckedAndSelected(),
		aFileItems = _.filter(aItems, function (oItem) {
			return !oItem.isFolder();
		}, this)
	;
	
	if (aFileItems.length > 0)
	{
		App.Api.composeMessageWithFiles(aFileItems);
	}
};

/**
 * @param {Object} oItem
 */
CFileStorageViewModel.prototype.onShareIconClick = function (oItem)
{
	if (oItem)
	{
		App.Screens.showPopup(FileStorageSharePopup, [oItem]);
	}
};

/**
 * @param {Object} oItem
 * @return {string}
 */
CFileStorageViewModel.prototype.renameItem = function (oItem)
{
	var sName = Utils.trim(oItem.nameForEdit());
	if (!Utils.validateFileOrFolderName(sName))
	{
		return oItem.isFolder() ?
			Utils.i18n('FILESTORAGE/INVALID_FOLDER_NAME') : Utils.i18n('FILESTORAGE/INVALID_FILE_NAME');
	}
	else
	{
		App.Ajax.send({
				'Action': 'FilesRename',
				'Type': this.storageType(),
				'Path': oItem.path(),
				'Name': oItem.id(),
				'NewName': sName,
				'IsLink': oItem.isLink() ? 1 : 0
			}, this.onFilesRenameResponse, this
		);
	}

	return '';
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CFileStorageViewModel.prototype.onFilesRenameResponse = function (oData, oParameters)
{
	this.getFiles(this.storageType(), this.getPathItemByIndex(this.iPathIndex()), this.searchPattern());
};


CFileStorageViewModel.prototype.executeDelete = function ()
{
	var
		aChecked = this.selector.listCheckedAndSelected()
	;
	if (!this.isPublic && aChecked && aChecked.length > 0)
	{
		App.Screens.showPopup(ConfirmPopup, [Utils.i18n('FILESTORAGE/CONFIRMATION_DELETE'), _.bind(this.deleteItems, this, aChecked)]);
	}
};

CFileStorageViewModel.prototype.onShow = function ()
{
//	if (!this.loaded() || this.isPopup)
//	{
		this.loaded(true);
		this.getStorages();
//	}

	this.selector.useKeyboardKeys(true);

	if (this.oJua)
	{
		this.oJua.setDragAndDropEnabledStatus(true);
	}
};

CFileStorageViewModel.prototype.onHide = function ()
{
	this.selector.useKeyboardKeys(false);
	if (this.oJua)
	{
		this.oJua.setDragAndDropEnabledStatus(false);
	}
};

/**
 * @param {number} iType
 */
CFileStorageViewModel.prototype.getQuota = function (iType)
{
	App.Ajax.send({
			'Action': 'FilesQuota',
			'Type': iType
		}, this.onQuotaResponse, this
	);
};

CFileStorageViewModel.prototype.getStorageByType = function (storageType)
{
	return _.find(this.storages(), function(oStorageItem){ 
		return oStorageItem.storageType() === storageType; 
	});	
};

CFileStorageViewModel.prototype.getStorages = function ()
{
//	this.storages.removeAll();
	
	if (!this.isPublic)
	{
		if (!this.getStorageByType('personal'))
		{
			this.storages.push(
				new CFileModel()
					.isFolder(true)
					.storageType('personal')
					.displayName(Utils.i18n('FILESTORAGE/TAB_PERSONAL_FILES'))
			);
		}
		if (this.IsCollaborationSupported)
		{
			if (!this.getStorageByType('corporate'))
			{
				this.storages.push(
					new CFileModel()
						.isFolder(true)
						.storageType('corporate')
						.displayName(Utils.i18n('FILESTORAGE/TAB_CORPORATE_FILES'))
				);
			}
			if (this.AllowFilesSharing)
			{
				if (!this.getStorageByType('shared'))
				{
					this.storages.push(
						new CFileModel()
							.isFolder(true)
							.storageType('shared')
							.displayName(Utils.i18n('FILESTORAGE/TAB_SHARED_FILES'))
					);
				}
			}
		}
		if (!this.isPopup)
		{
			this.getExternalFileStorages();
		}
		else
		{
			this.getFiles(this.storageType(), this.getPathItemByIndex(this.iPathIndex()));
		}
	}
	else
	{
		this.getFiles(this.storageType(), this.getPathItemByIndex(this.iPathIndex()));
	}
};

CFileStorageViewModel.prototype.getExternalFileStorages = function ()
{
	App.Ajax.send({
			'Action': 'FileStoragesExternal'
		}, this.onExternalStoragesResponse, this
	);
};

CFileStorageViewModel.prototype.onExternalStoragesResponse = function (oData, oParameters)
{
	if (oData.Result)
	{
		_.each(oData.Result, function(oStorage){
			if (!this.getStorageByType(oStorage.Type))
			{
				this.storages.push(
					new CFileModel()
						.isExternal(true)
						.isFolder(true)
						.storageType(oStorage.Type)
						.displayName(oStorage.DisplayName)
				);
			}
		}, this);
		
		this.expungeExternalStorages(_.map(oData.Result, function(oStorage){
			return oStorage.Type;
		}, this));
	}
	if (!this.getStorageByType(this.storageType()))
	{
		this.storageType('personal');
		this.pathItems([]);
		this.iPathIndex(-1);
	}
	
	this.getFiles(this.storageType(), this.getPathItemByIndex(this.iPathIndex()));
};

/**
 * @param {string} sType
 * @param {object=} oPath = ''
 * @param {string=} sPattern = ''
 * @param {boolean=} bLoading = true
 */
CFileStorageViewModel.prototype.getFiles = function (sType, oPath, sPattern, bLoading)
{
    var 
        self = this,
		sTypePrev = this.storageType(),
		iPathIndex = this.iPathIndex(),
		oFolder = new CFileModel()
			.isFolder(true)
			.storageType(sType)
	;
	if (this.isPublic)
	{
		return this.getFilesPub(oPath);
	}
	this.error(false);
	this.storageType(sType);
    self.loadedFiles(false);
	if (Utils.isUnd(bLoading) || !Utils.isUnd(bLoading) && bLoading)
	{
		this.timerId = setTimeout(function() {
			if (!self.loadedFiles() && !self.error())
			{
				self.folders([]);
				self.files([]);
				self.loading(true);
			}
		}, 1500);				
	}
	
	this.searchPattern(Utils.isUnd(sPattern) ? '' : Utils.pString(sPattern));
	if (Utils.isUnd(oPath) || oPath.id() === '')
	{
		this.pathItems.removeAll();
		oFolder.displayName(this.rootPath());
	}
	else
	{
		oFolder = oPath;
	}

	this.pathItems.push(oFolder);
	this.iPathIndex(this.pathItems().length - 1);
	
	if (iPathIndex !== this.iPathIndex() || sTypePrev !== this.storageType())
	{
		this.folders([]);
		this.files([]);
	}
	
	App.Ajax.sendExt({
			'Action': 'Files',
			'Type': sType,
			'Path': this.path(),
			'Pattern': this.searchPattern()
		}, this.onFilesResponse, this
	);
};

/**
 * @param {string} sHash
 */
CFileStorageViewModel.prototype.getFilesPub = function (oPath)
{
	var 
		iPathIndex = this.iPathIndex(),
		oFolder = new CFileModel()
			.isFolder(true)
	;
	if (Utils.isUnd(oPath) || oPath.id() === '')
	{
		this.pathItems.removeAll();
		oFolder.displayName(this.rootPath());
	}
	else
	{
		oFolder = oPath;
	}
	
	this.pathItems.push(oFolder);
	
	this.iPathIndex(this.pathItems().length - 1);
	
	if (iPathIndex !== this.iPathIndex())
	{
		this.folders([]);
		this.files([]);
	}
	
	App.Ajax.sendExt({
			'Action': 'FilesPub',
			'Hash': AppData.FileStoragePubHash,
			'Path': this.path()
		}, this.onFilesResponse, this
	);
};

/**
 * @param {Array} aChecked
 * @param {boolean} bOkAnswer
 */
CFileStorageViewModel.prototype.deleteItems = function (aChecked, bOkAnswer)
{
	if (bOkAnswer && 0 < aChecked.length)
	{
		var
			aItems = _.map(aChecked, function (oItem) {
				oItem.deleted(true);
				return {
					'Path': oItem.path(),  
					'Name': oItem.id()
				};
			});
		
		App.Ajax.send({
				'Action': 'FilesDelete',
				'Type': this.storageType(),
				'Path': this.path(),
				'Items': JSON.stringify(aItems)		
			}, this.onFilesDeleteResponse, this
		);
	}		
};

/**
 * @param {number} iIndex
 * 
 * @return {string}
 */
CFileStorageViewModel.prototype.getPathItemByIndex = function (iIndex)
{
	var 
		oItem = this.pathItems()[iIndex],
		oResult = new CFileModel().fileName(this.rootPath()).id('')
	;
	
	this.pathItems(this.pathItems().slice(0, iIndex));
	if (oItem && !this.isPublic)
	{
		oResult = oItem;
	}
	return oResult;
};

/**
 * @param {number} iIndex
 * 
 * @return {string}
 */
CFileStorageViewModel.prototype.getFullPathByIndex = function (iIndex)
{
	var 
		aPath = _.map(this.pathItems().slice(0, iIndex), function (oItem){
			return oItem.fileName();
		});
	
    return aPath.join('/');
};

/**
 * @param {string} sName
 * 
 * @return {?}
 */
CFileStorageViewModel.prototype.getFileByName = function (sName)
{
	return _.find(this.files(), function(oItem){
		return oItem.id() === sName;
	});	
};

/**
 * @param {string} sName
 */
CFileStorageViewModel.prototype.deleteFileByName = function (sName)
{
	this.files(_.filter(this.files(), function (oItem) {
		return oItem.id() !== sName;
	}));
};

/**
 * @param {string} sName
 */
CFileStorageViewModel.prototype.deleteFolderByName = function (sName)
{
	this.folders(_.filter(this.folders(), function (oItem) {
		return oItem.fileName() !== sName;
	}));
};

/**
 * @param {string} sName
 */
CFileStorageViewModel.prototype.expungeFileItems = function ()
{
	this.folders(_.filter(this.folders(), function(oFolder){
		return !oFolder.deleted();
	}, this));
	this.files(_.filter(this.files(), function(oFile){
		return !oFile.deleted();
	}, this));
};

/**
 * @param {array} aStorageTypes
 */
CFileStorageViewModel.prototype.expungeExternalStorages = function (aStorageTypes)
{
	this.storages(_.filter(this.storages(), function(oStorage){
		return !oStorage.isExternal() || _.include(aStorageTypes, oStorage.storageType());
	},this));
};

/**
 * @param {int} iType
 */
CFileStorageViewModel.prototype.deleteStorageByType = function (iType)
{
	this.storages(_.filter(this.storages(), function (oItem) {
		return oItem.storageType() !== iType;
	}));
};


/**
 * @param {string} sFileUid
 * 
 * @return {?}
 */
CFileStorageViewModel.prototype.getUploadFileByUid = function (sFileUid)
{
	return _.find(this.uploadingFiles(), function(oItem){
		return oItem.uploadUid() === sFileUid;
	});	
};

/**
 * @param {string} sFileUid
 */
CFileStorageViewModel.prototype.deleteUploadFileByUid = function (sFileUid)
{
	this.uploadingFiles(_.filter(this.uploadingFiles(), function (oItem) {
		return oItem.uploadUid() !== sFileUid;
	}));
};

/**
 * @return {Array}
 */
CFileStorageViewModel.prototype.getUploadingFiles = function ()
{
	var 
		aResult = [],
		uploadingFiles = this.uploadingFiles(),
		self = this;
        
	if (!Utils.isUnd(uploadingFiles))
	{
		aResult = _.filter(uploadingFiles, function(oItem){
			return oItem.path() === self.path() && oItem.storageType() === self.storageType();
		});	
	}
	return aResult;
};

/**
 * @param {string} sFileUid
 */
CFileStorageViewModel.prototype.onCancelUpload = function (sFileUid)
{
	if (this.oJua)
	{
		this.oJua.cancel(sFileUid);
	}
	this.deleteUploadFileByUid(sFileUid);
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CFileStorageViewModel.prototype.onCreateFolderResponse = function (oData, oParameters)
{
	this.getFiles(this.storageType(), this.getPathItemByIndex(this.iPathIndex()));
};

/**
 * @param {string} sFolderName
 */
CFileStorageViewModel.prototype.createFolder = function (sFolderName)
{
	sFolderName = Utils.trim(sFolderName);
	if (!Utils.validateFileOrFolderName(sFolderName))
	{
		return Utils.i18n('FILESTORAGE/INVALID_FOLDER_NAME');
	}
	else
	{
		App.Ajax.send({
				'Action': 'FilesFolderCreate',
				'Type': this.storageType(),
				'Path': this.path(),
				'FolderName': sFolderName
			}, this.onCreateFolderResponse, this
		);
	}

	return '';
};

CFileStorageViewModel.prototype.onCreateFolderClick = function ()
{
	App.Screens.showPopup(FileStorageFolderCreatePopup, [_.bind(this.createFolder, this)]);
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CFileStorageViewModel.prototype.onCreateLinkResponse = function (oData, oParameters)
{
	this.getFiles(this.storageType(), this.getPathItemByIndex(this.iPathIndex()));
};

/**
 * @param {string} sFolderName
 */
CFileStorageViewModel.prototype.createLink = function (oFileItem)
{
        App.Ajax.send({
			'Action': 'FilesLinkCreate',
			'Type': this.storageType(),
			'Path': this.path(),
			'Link': oFileItem.linkUrl(),
			'Name': oFileItem.fileName()
                        
		}, this.onCreateLinkResponse, this
	);
		
};

CFileStorageViewModel.prototype.onCreateLinkClick = function ()
{
	var fCallBack = _.bind(this.createLink, this);

	App.Screens.showPopup(FileStorageLinkCreatePopup, [fCallBack]);
	
};


CFileStorageViewModel.prototype.onSearch = function ()
{
	this.getFiles(this.storageType(), this.getPathItemByIndex(this.iPathIndex()), this.searchPattern());
};

CFileStorageViewModel.prototype.clearSearch = function ()
{
	this.getFiles(this.storageType(), this.getPathItemByIndex(this.iPathIndex()));
};
