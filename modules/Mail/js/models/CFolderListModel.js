'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Types = require('core/js/utils/Types.js'),
	
	Storage = require('core/js/Storage.js'),
	
	Settings = require('modules/Mail/js/Settings.js'),
	AccountList = require('modules/Mail/js/AccountList.js'),
	CFolderModel = require('modules/Mail/js/models/CFolderModel.js')
;

/**
 * @constructor
 */
function CFolderListModel()
{
	this.iAccountId = 0;
	this.initialized = ko.observable(false);

	this.bExpandFolders = false;
	this.expandNames = ko.observableArray([]);
	this.collection = ko.observableArray([]);
	this.options = ko.observableArray([]);
	this.sNamespaceFolder = '';
	this.oStarredFolder = null;

	this.oNamedCollection = {};
	this.aLinedCollection = [];

	var
		self = this,
		fSetSystemType = function (iType) {
			return function (oFolder) {
				if (oFolder)
				{
					oFolder.type(iType);
				}
			};
		},
		fFullNameHelper = function (fFolder) {
			return {
				'read': function () {
					this.collection();
					return fFolder() ? fFolder().fullName() : '';
				},
				'write': function (sValue) {
					fFolder(this.getFolderByFullName(sValue));
				},
				'owner': self
			};
		}
	;

	this.currentFolder = ko.observable(null);

	this.inboxFolder = ko.observable(null);
	this.sentFolder = ko.observable(null);
	this.draftsFolder = ko.observable(null);
	this.spamFolder = ko.observable(null);
	this.trashFolder = ko.observable(null);
	
	this.countsCompletelyFilled = ko.observable(false);

	this.inboxFolder.subscribe(fSetSystemType(Enums.FolderTypes.User), this, 'beforeChange');
	this.sentFolder.subscribe(fSetSystemType(Enums.FolderTypes.User), this, 'beforeChange');
	this.draftsFolder.subscribe(fSetSystemType(Enums.FolderTypes.User), this, 'beforeChange');
	this.spamFolder.subscribe(fSetSystemType(Enums.FolderTypes.User), this, 'beforeChange');
	this.trashFolder.subscribe(fSetSystemType(Enums.FolderTypes.User), this, 'beforeChange');
	
	this.inboxFolder.subscribe(fSetSystemType(Enums.FolderTypes.Inbox));
	this.sentFolder.subscribe(fSetSystemType(Enums.FolderTypes.Sent));
	this.draftsFolder.subscribe(fSetSystemType(Enums.FolderTypes.Drafts));
	this.spamFolder.subscribe(fSetSystemType(Enums.FolderTypes.Spam));
	this.trashFolder.subscribe(fSetSystemType(Enums.FolderTypes.Trash));
	
	this.inboxFolderFullName = ko.computed(fFullNameHelper(this.inboxFolder));
	this.sentFolderFullName = ko.computed(fFullNameHelper(this.sentFolder));
	this.draftsFolderFullName = ko.computed(fFullNameHelper(this.draftsFolder));
	this.spamFolderFullName = ko.computed(fFullNameHelper(this.spamFolder));
	this.trashFolderFullName = ko.computed(fFullNameHelper(this.trashFolder));
	
	this.currentFolderFullName = ko.computed(fFullNameHelper(this.currentFolder));
	this.currentFolderType = ko.computed(function () {
		return this.currentFolder() ? this.currentFolder().type() : Enums.FolderTypes.User;
	}, this);
	
	this.sDelimiter = '';
}

CFolderListModel.prototype.getTotalMessageCount = function ()
{
	var iCount = 0;
	
	_.each(this.oNamedCollection, function (oFolder) {
		iCount += oFolder.messageCount();
	}, this);
	
	return iCount;
};

/**
 * @returns {Array}
 */
CFolderListModel.prototype.getFoldersWithoutCountInfo = function ()
{
	var aFolders = _.compact(_.map(this.oNamedCollection, function(oFolder, sFullName) {
		if (oFolder.bCanBeSelected && !oFolder.hasExtendedInfo())
		{
			return sFullName;
		}
		
		return null;
	}));
	
	return aFolders;
};

/**
 * @param {string} sFolderFullName
 * @param {string} sFilters
 */
CFolderListModel.prototype.setCurrentFolder = function (sFolderFullName, sFilters)
{
	var
		oFolder = this.getFolderByFullName(sFolderFullName)
	;
	
	if (oFolder === null)
	{
		oFolder = this.inboxFolder();
	}
	
	if (oFolder !== null)
	{
		if (this.currentFolder())
		{
			this.currentFolder().selected(false);
			if (this.oStarredFolder)
			{
				this.oStarredFolder.selected(false);
			}
		}
		
		this.currentFolder(oFolder);
		if (sFilters === Enums.FolderFilter.Flagged)
		{
			if (this.oStarredFolder)
			{
				this.oStarredFolder.selected(true);
			}
		}
		else
		{
			this.currentFolder().selected(true);
		}
	}
};

/**
 * Returns a folder, found by the full name.
 * 
 * @param {string} sFolderFullName
 * @returns {CFolderModel|null}
 */
CFolderListModel.prototype.getFolderByFullName = function (sFolderFullName)
{
	var oFolder = this.oNamedCollection[sFolderFullName];
	
	return oFolder ? oFolder : null;
};

/**
 * Calls a recursive parsing of the folder tree.
 * 
 * @param {number} iAccountId
 * @param {Object} oData
 * @param {Object} oNamedFolderListOld
 */
CFolderListModel.prototype.parse = function (iAccountId, oData, oNamedFolderListOld)
{
	var
		sNamespace = Types.pString(oData.Namespace),
		aCollection = oData.Folders['@Collection']
	;
	if (sNamespace.length > 0)
	{
		this.sNamespaceFolder = sNamespace.substring(0, sNamespace.length - 1);
	}
	
	this.iAccountId = iAccountId;
	this.initialized(true);

	this.bExpandFolders = Settings.AllowExpandFolders && !Storage.hasData('folderAccordion');
	if (!Storage.hasData('folderAccordion'))
	{
		Storage.setData('folderAccordion', []);
	}
	
	this.oNamedCollection = {};
	this.aLinedCollection = [];
	this.collection(this.parseRecursively(aCollection, oNamedFolderListOld));
};

/**
 * Recursively parses the folder tree.
 * 
 * @param {Array} aRawCollection
 * @param {Object} oNamedFolderListOld
 * @param {number=} iLevel
 * @param {string=} sParentFullName
 * @returns {Array}
 */
CFolderListModel.prototype.parseRecursively = function (aRawCollection, oNamedFolderListOld, iLevel, sParentFullName)
{
	var
		self = this,
		aParsedCollection = [],
		iIndex = 0,
		iLen = 0,
		oFolder = null,
		oFolderOld = null,
		sFolderFullName = '',
		oSubFolders = null,
		aSubfolders = [],
		oAccount = AccountList.getAccount(this.iAccountId),
		bDisableManageSubscribe = oAccount.extensionExists('DisableManageSubscribe'),
		fDetectSpamFolder = function () {
			var oSpamFolder = self.spamFolder();
			if (!oAccount || !oAccount.extensionExists('AllowSpamFolderExtension'))
			{
				oSpamFolder.type(Enums.FolderTypes.User);
				self.spamFolder(null);
			}
		},
		fAccountExtensionsRequestedSubscribe = function () {
			if (oAccount && oAccount.extensionsRequested())
			{
				fDetectSpamFolder();
				oAccount.extensionsRequestedSubscription.dispose();
				oAccount.extensionsRequestedSubscription = undefined;
			}
		}
	;

	sParentFullName = sParentFullName || '';
	
	if (iLevel === undefined)
	{
		iLevel = -1;
	}

	iLevel++;
	if (_.isArray(aRawCollection))
	{
		for (iLen = aRawCollection.length; iIndex < iLen; iIndex++)
		{
			sFolderFullName = Types.pString(aRawCollection[iIndex].FullNameRaw);
			oFolderOld = oNamedFolderListOld[sFolderFullName];
			oFolder = new CFolderModel(this.iAccountId);
			oSubFolders = oFolder.parse(aRawCollection[iIndex], sParentFullName, bDisableManageSubscribe, this.sNamespaceFolder);
			if (oFolderOld && oFolderOld.hasExtendedInfo() && !oFolder.hasExtendedInfo())
			{
				oFolder.setRelevantInformation(oFolderOld.sUidNext, oFolderOld.sHash, 
					oFolderOld.messageCount(), oFolderOld.unseenMessageCount(), false);
			}

			if (this.bExpandFolders && oSubFolders !== null)
			{
				oFolder.expanded(true);
				this.expandNames().push(Types.pString(aRawCollection[iIndex].Name));
			}

			oFolder.setLevel(iLevel);

			switch (oFolder.type())
			{
				case Enums.FolderTypes.Inbox:
					this.inboxFolder(oFolder);
					this.sDelimiter = oFolder.sDelimiter;
					break;
				case Enums.FolderTypes.Sent:
					this.sentFolder(oFolder);
					break;
				case Enums.FolderTypes.Drafts:
					this.draftsFolder(oFolder);
					break;
				case Enums.FolderTypes.Trash:
					this.trashFolder(oFolder);
					break;
				case Enums.FolderTypes.Spam:
					this.spamFolder(oFolder);
					if (oAccount.extensionsRequested())
					{
						fDetectSpamFolder();
					}
					else
					{
						oAccount.extensionsRequestedSubscription = oAccount.extensionsRequested.subscribe(fAccountExtensionsRequestedSubscribe);
					}
					break;
			}

			this.oNamedCollection[oFolder.fullName()] = oFolder;
			this.aLinedCollection.push(oFolder);
			aParsedCollection.push(oFolder);
			
			if (oSubFolders === null && oFolder.type() === Enums.FolderTypes.Inbox)
			{
				this.createStarredFolder(oFolder.fullName(), iLevel);
				if (this.oStarredFolder)
				{
					aParsedCollection.push(this.oStarredFolder);
				}
			}
			else if (oSubFolders !== null)
			{
				aSubfolders = this.parseRecursively(oSubFolders['@Collection'], oNamedFolderListOld, iLevel, oFolder.fullName());
				if(oFolder.type() === Enums.FolderTypes.Inbox)
				{
					if (oFolder.bNamespace)
					{
						this.createStarredFolder(oFolder.fullName(), iLevel + 1);
						if (this.oStarredFolder)
						{
							aSubfolders.unshift(this.oStarredFolder);
						}
					}
					else
					{
						this.createStarredFolder(oFolder.fullName(), iLevel);
						if (this.oStarredFolder)
						{
							aParsedCollection.push(this.oStarredFolder);
						}
					}
				}
				oFolder.subfolders(aSubfolders);
			}
		}

		if (this.bExpandFolders)
		{
			Storage.setData('folderAccordion', this.expandNames());
		}
	}

	return aParsedCollection;
};

/**
 * @param {string} sFullName
 * @param {number} iLevel
 */
CFolderListModel.prototype.createStarredFolder = function (sFullName, iLevel)
{
	this.oStarredFolder = new CFolderModel(this.iAccountId);
	this.oStarredFolder.initStarredFolder(iLevel, sFullName);
};

CFolderListModel.prototype.repopulateLinedCollection = function ()
{
	var self = this;
	
	function fPopuplateLinedCollection(aFolders)
	{
		_.each(aFolders, function (oFolder) {
			self.aLinedCollection.push(oFolder);
			if (oFolder.subfolders().length > 0)
			{
				fPopuplateLinedCollection(oFolder.subfolders());
			}
		});
	}
	
	this.aLinedCollection = [];
	
	fPopuplateLinedCollection(this.collection());
	
	return this.aLinedCollection;
};

/**
 * @param {string} sFirstItem
 * @param {boolean=} bEnableSystem = false
 * @param {boolean=} bHideInbox = false
 * @param {boolean=} bIgnoreCanBeSelected = false
 * @param {boolean=} bIgnoreUnsubscribed = false
 * @returns {Array}
 */
CFolderListModel.prototype.getOptions = function (sFirstItem, bEnableSystem, bHideInbox, bIgnoreCanBeSelected, bIgnoreUnsubscribed)
{
	bEnableSystem = !!bEnableSystem;
	bHideInbox = !!bHideInbox;
	bIgnoreCanBeSelected = !!bIgnoreCanBeSelected;
	bIgnoreUnsubscribed = !!bIgnoreUnsubscribed;
	
	var
		sDeepPrefix = '\u00A0\u00A0\u00A0\u00A0',
		aCollection = []
	;
	
	_.each(this.aLinedCollection, function (oFolder) {
		if (oFolder && !oFolder.bVirtual && (!bHideInbox || Enums.FolderTypes.Inbox !== oFolder.type()) && (!bIgnoreUnsubscribed || oFolder.subscribed()))
		{
			var sPrefix = (new Array(oFolder.iLevel + 1)).join(sDeepPrefix);
			aCollection.push({
				'name': oFolder.name(),
				'fullName': oFolder.fullName(),
				'displayName': sPrefix + oFolder.name(),
				'translatedDisplayName': sPrefix + oFolder.displayName(),
				'disable': !bEnableSystem && oFolder.isSystem() || !bIgnoreCanBeSelected && !oFolder.bCanBeSelected
			});
		}
	});
	
	if (sFirstItem !== '')
	{
		aCollection.unshift({
			'name': sFirstItem,
			'fullName': '',
			'displayName': sFirstItem,
			'translatedDisplayName': sFirstItem,
			'disable': false
		});
	}

	return aCollection;
};

/**
 * @param {Object} oFolderToDelete
 */
CFolderListModel.prototype.deleteFolder = function (oFolderToDelete)
{
	var
		fRemoveFolder = function (oFolder) {
			if (oFolderToDelete && oFolderToDelete.fullName() === oFolder.fullName())
			{
				return true;
			}
			oFolder.subfolders.remove(fRemoveFolder);
			return false;
		}
	;

	this.collection.remove(fRemoveFolder);
};

module.exports = CFolderListModel;
