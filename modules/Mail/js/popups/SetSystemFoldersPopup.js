'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	CAbstractPopup = require('core/js/popups/CAbstractPopup.js'),
	
	Accounts = require('modules/Mail/js/AccountList.js'),
	MailCache = require('modules/Mail/js/Cache.js'),
	Ajax = require('modules/Mail/js/Ajax.js')
;

/**
 * @constructor
 */
function CSetSystemFoldersPopup()
{
	CAbstractPopup.call(this);
	
	this.folders = MailCache.editedFolderList;
	
	this.sentFolderFullName = ko.observable('');
	this.draftsFolderFullName = ko.observable('');
	this.spamFolderFullName = ko.observable('');
	this.trashFolderFullName = ko.observable('');
	
	this.options = ko.observableArray([]);
	
	this.defaultOptionsAfterRender = Utils.defaultOptionsAfterRender;
	
	this.allowSpamFolderEditing = ko.computed(function () {
		var
			oAccount = Accounts.getEdited(),
			bAllowSpamFolderExtension = oAccount.extensionExists('AllowSpamFolderExtension')
		;
		return bAllowSpamFolderExtension;// && !AppData.IsMailsuite;
	}, this);
}

_.extendOwn(CSetSystemFoldersPopup.prototype, CAbstractPopup.prototype);

CSetSystemFoldersPopup.prototype.PopupTemplate = 'Mail_Settings_SetSystemFoldersPopup';

CSetSystemFoldersPopup.prototype.onShow = function ()
{
	var oFolderList = MailCache.editedFolderList();
	
	this.options(oFolderList.getOptions(TextUtils.i18n('SETTINGS/ACCOUNT_FOLDERS_NO_USAGE_ASSIGNED'), false, false, false));

	this.sentFolderFullName(oFolderList.sentFolderFullName());
	this.draftsFolderFullName(oFolderList.draftsFolderFullName());
	if (this.allowSpamFolderEditing())
	{
		this.spamFolderFullName(oFolderList.spamFolderFullName());
	}
	this.trashFolderFullName(oFolderList.trashFolderFullName());
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CSetSystemFoldersPopup.prototype.onResponseFoldersSetupSystem = function (oResponse, oRequest)
{
	if (oResponse.Result === false)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ACCOUNT_FOLDERS_ERROR_SETUP_SPECIAL_FOLDERS'));
		MailCache.getFolderList(Accounts.editedId());
	}
};

CSetSystemFoldersPopup.prototype.apply = function ()
{
	var
		oFolderList = MailCache.editedFolderList(),
		bHasChanges = false,
		oParameters = null
	;
	
	if (this.sentFolderFullName() !== oFolderList.sentFolderFullName())
	{
		oFolderList.sentFolderFullName(this.sentFolderFullName());
		bHasChanges = true;
	}
	if (this.draftsFolderFullName() !== oFolderList.draftsFolderFullName())
	{
		oFolderList.draftsFolderFullName(this.draftsFolderFullName());
		bHasChanges = true;
	}
	if (this.allowSpamFolderEditing() && this.spamFolderFullName() !== oFolderList.spamFolderFullName())
	{
		oFolderList.spamFolderFullName(this.spamFolderFullName());
		bHasChanges = true;
	}
	if (this.trashFolderFullName() !== oFolderList.trashFolderFullName())
	{
		oFolderList.trashFolderFullName(this.trashFolderFullName());
		bHasChanges = true;
	}
	
	if (bHasChanges)
	{
		oParameters = {
			'AccountID': Accounts.editedId(),
			'Sent': oFolderList.sentFolderFullName(),
			'Drafts': oFolderList.draftsFolderFullName(),
			'Trash': oFolderList.trashFolderFullName(),
			'Spam': oFolderList.spamFolderFullName()
		};
		Ajax.send('SetupSystemFolders', oParameters, this.onResponseFoldersSetupSystem, this);
	}
	
	this.closePopup();
};

module.exports = new CSetSystemFoldersPopup();