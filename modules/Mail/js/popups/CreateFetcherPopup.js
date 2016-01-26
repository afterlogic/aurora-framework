'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	
	Popups = require('core/js/Popups.js'),
	CAbstractPopup = require('core/js/popups/CAbstractPopup.js'),
	CreateFolderPopup = require('modules/Mail/js/popups/CreateFolderPopup.js'),
	
	Accounts = require('modules/Mail/js/AccountList.js'),
	MailCache = require('modules/Mail/js/Cache.js'),
	Ajax = require('modules/Mail/js/Ajax.js'),
	CServerPropertiesViewModel = require('modules/Mail/js/views/CServerPropertiesViewModel.js')
;

/**
 * @constructor
 */
function CCreateFetcherPopup()
{
	CAbstractPopup.call(this);
	
	this.loading = ko.observable(false);
	this.newFolderCreating = ko.observable(false);

	this.incomingMailLogin = ko.observable('');
	this.incomingMailPassword = ko.observable('');
	this.oIncoming = new CServerPropertiesViewModel(110, 995, 'fectcher_add_incoming', TextUtils.i18n('SETTINGS/ACCOUNT_FETCHER_POP3_SERVER'));

	this.folder = ko.observable('');
	this.options = ko.observableArray([]);
	MailCache.folderList.subscribe(function () {
		this.populateOptions();
	}, this);

	this.addNewFolderCommand = Utils.createCommand(this, this.onAddNewFolderClick);

	this.leaveMessagesOnServer = ko.observable(false);

	this.loginIsSelected = ko.observable(false);
	this.passwordIsSelected = ko.observable(false);

	this.defaultOptionsAfterRender = Utils.defaultOptionsAfterRender;
}

_.extendOwn(CCreateFetcherPopup.prototype, CAbstractPopup.prototype);

CCreateFetcherPopup.prototype.PopupTemplate = 'Mail_Settings_CreateFetcherPopup';

CCreateFetcherPopup.prototype.onShow = function ()
{
	this.bShown = true;
	this.populateOptions();
	
	this.incomingMailLogin('');
	this.incomingMailPassword('');
	this.oIncoming.clear();

	this.folder('');

	this.leaveMessagesOnServer(true);
};

CCreateFetcherPopup.prototype.populateOptions = function ()
{
	if (this.bShown)
	{
		this.options(MailCache.folderList().getOptions('', true, false, false));
	}
};

CCreateFetcherPopup.prototype.onHide = function ()
{
	this.bShown = false;
};

CCreateFetcherPopup.prototype.save = function ()
{
	if (this.isEmptyRequiredFields())
	{
		Screens.showError(TextUtils.i18n('WARNING/FETCHER_CREATE_ERROR'));
	}
	else
	{
		var oParameters = {
			'AccountID': Accounts.defaultId(),
			'Folder': this.folder(),
			'IncomingMailLogin': this.incomingMailLogin(),
			'IncomingMailPassword': (this.incomingMailPassword() === '') ? '******' : this.incomingMailPassword(),
			'IncomingMailServer': this.oIncoming.server(),
			'IncomingMailPort': this.oIncoming.getIntPort(),
			'IncomingMailSsl': this.oIncoming.getIntSsl(),
			'LeaveMessagesOnServer': this.leaveMessagesOnServer() ? 1 : 0
		};

		this.loading(true);

		Ajax.send('CreateFetcher', oParameters, this.onCreateFetcherResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CCreateFetcherPopup.prototype.onCreateFetcherResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (!oResponse.Result)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('WARNING/UNKNOWN_ERROR'));
	}
	else
	{
		Accounts.populateFetchers();

		this.closePopup();
	}
};

CCreateFetcherPopup.prototype.cancelPopup = function ()
{
	if (!this.newFolderCreating())
	{
		this.closePopup();
	}
};

CCreateFetcherPopup.prototype.isEmptyRequiredFields = function ()
{
	switch ('')
	{
		case this.oIncoming.server():
			this.oIncoming.focused(true);
			return true;
		case this.incomingMailLogin():
			this.loginIsSelected(true);
			return true;
		case this.incomingMailPassword():
			this.passwordIsSelected(true);
			return true;
		default: return false;
	}
};

CCreateFetcherPopup.prototype.onAddNewFolderClick = function ()
{
	this.newFolderCreating(true);
	Popups.showPopup(CreateFolderPopup, [_.bind(this.chooseFolderInList, this)]);
};

/**
 * @param {string} sFolderName
 * @param {string} sParentFullName
 */
CCreateFetcherPopup.prototype.chooseFolderInList = function (sFolderName, sParentFullName)
{
	var
		sDelimiter = MailCache.folderList().sDelimiter,
		aFolder = []
	;
	
	if (sFolderName !== '' && sParentFullName !== '')
	{
		this.options(MailCache.folderList().getOptions('', true, false, false));
		
		_.each(this.options(), _.bind(function (oOption) {
			if (sFolderName === oOption.name)
			{
				aFolder = oOption.fullName.split(sDelimiter);
				aFolder.pop();
				if (sParentFullName === aFolder.join(sDelimiter))
				{
					this.folder(oOption.fullName);
				}
			}
		}, this));
	}
	
	this.newFolderCreating(false);
};

module.exports = new CCreateFetcherPopup();
