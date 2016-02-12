'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	
	CAbstractPopup = require('core/js/popups/CAbstractPopup.js'),
	
	AccountList = require('modules/Mail/js/AccountList.js'),
	Ajax = require('modules/Mail/js/Ajax.js'),
	MailCache = require('modules/Mail/js/Cache.js')
;

/**
 * @constructor
 */
function CCreateFolderPopup()
{
	CAbstractPopup.call(this);
	
	this.isCreating = ko.observable(false);
	MailCache.folderListLoading.subscribe(function () {
		var bListLoading = MailCache.folderListLoading.indexOf(MailCache.editedFolderList().iAccountId) !== -1;
		if (!bListLoading && this.isCreating())
		{
			if ($.isFunction(this.fCallback))
			{
				this.fCallback(this.folderName(), this.parentFolder());
			}
			this.isCreating(false);
			this.closePopup();
		}
	}, this);

	this.options = ko.observableArray([]);

	this.parentFolder = ko.observable('');
	this.folderName = ko.observable('');
	this.folderNameFocus = ko.observable(false);
	
	this.fCallback = null;

	this.defaultOptionsAfterRender = Utils.defaultOptionsAfterRender;
}

_.extendOwn(CCreateFolderPopup.prototype, CAbstractPopup.prototype);

CCreateFolderPopup.prototype.PopupTemplate = 'Mail_Settings_CreateFolderPopup';

/**
 * @param {Function} fCallback
 */
CCreateFolderPopup.prototype.onShow = function (fCallback)
{
	this.options(MailCache.editedFolderList().getOptions(TextUtils.i18n('MAIL/OPTION_NO_PARENT_FOLDER'), true, false, true));
	
	this.fCallback = fCallback;
	this.folderName('');
	this.folderNameFocus(true);
};

CCreateFolderPopup.prototype.create = function ()
{
	var
		sParentFolder = (this.parentFolder() === '' ? MailCache.editedFolderList().sNamespaceFolder : this.parentFolder()),
		oParameters = {
			'AccountID': AccountList.editedId(),
			'FolderNameInUtf8': this.folderName(),
			'FolderParentFullNameRaw': sParentFolder,
			'Delimiter': MailCache.editedFolderList().sDelimiter
		}
	;

	this.folderNameFocus(false);
	this.isCreating(true);

	Ajax.send('CreateFolder', oParameters, this.onCreateFolderResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CCreateFolderPopup.prototype.onCreateFolderResponse = function (oResponse, oRequest)
{
	if (!oResponse.Result)
	{
		this.isCreating(false);
		Api.showErrorByCode(oResponse, TextUtils.i18n('MAIL/ERROR_CREATE_FOLDER'));
	}
	else
	{
		MailCache.getFolderList(AccountList.editedId());
	}
};

CCreateFolderPopup.prototype.cancelPopup = function ()
{
	if (!this.isCreating())
	{
		if ($.isFunction(this.fCallback))
		{
			this.fCallback('', '');
		}
		this.closePopup();
	}
};

module.exports = new CCreateFolderPopup();
