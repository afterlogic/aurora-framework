
/*!
 * Copyright 2004-2015, AfterLogic Corp.
 * Licensed under AGPLv3 license or AfterLogic license
 * if commerical version of the product was purchased.
 * See the LICENSE file for a full license statement.
 */

(function ($, window, ko, crossroads, hasher) {

'use strict';

var
	/**
	 * @type {Object}
	 */
	Consts = {},

	/**
	 * @type {Object}
	 */
	Enums = {},

	/**
	 * @type {Object.<Function>}
	 */
	Utils = {},

	/**
	 * @type {Object}
	 */
	I18n = window.pSevenI18N || {},

	/**
	 * @type {CApp|Object}
	 */
	App = {},
	
	/**
	 * @type {Object.<Function>}
	 */
	AfterLogicApi = {},

	/**
	 * @type {AjaxAppDataResponse|Object}
	 */
	AppData = window.pSevenAppData || {},

	/**
	 * @type {boolean}
	 */
	bExtApp = false,
			
	/**
	 * @type {boolean}
	 */
	bMobileApp = false,

	$html = $('html'),

	/**
	 * @type {boolean}
	 */
	bIsWindowsPhone = -1 < navigator.userAgent.indexOf('Windows Phone'),
	
	/**
	 * @type {boolean}
	 */
	bIsIosDevice = !bIsWindowsPhone && (-1 < navigator.userAgent.indexOf('iPhone') ||
		-1 < navigator.userAgent.indexOf('iPod') ||
		-1 < navigator.userAgent.indexOf('iPad')),

	/**
	 * @type {boolean}
	 */
	bIsAndroidDevice = !bIsWindowsPhone && (-1 < navigator.userAgent.toLowerCase().indexOf('android')),

	/**
	 * @type {boolean}
	 */
	bMobileDevice = bIsWindowsPhone || bIsIosDevice || bIsAndroidDevice,

	aViewMimeTypes = [
		'image/jpeg', 'image/png', 'image/gif',
		'text/html', 'text/plain', 'text/css',
		'text/rfc822-headers', 'message/delivery-status',
		'application/x-httpd-php', 'application/javascript',
		'application/pdf'
	]
;

if (window.Modernizr && navigator)
{
	// v = 15;
	window.Modernizr.addTest('pdf', function() {
		var aMimes = navigator.mimeTypes, iIndex = 0, iLen = aMimes.length;
		for (; iIndex < iLen; iIndex++)
		{
			if ('application/pdf' === aMimes[iIndex].type)
			{
				return true;
			}
		}
		
		return false;
	});
}

if (!Date.now)
{
	Date.now = function () {
		return (new Date()).getTime();
	};
}
bExtApp = true;


/**
 * @constructor
 */
function CBrowser()
{
	this.ie11 = !!navigator.userAgent.match(/Trident.*rv[ :]*11\./);
	this.ie = (/msie/.test(navigator.userAgent.toLowerCase()) && !window.opera) || this.ie11;
	this.ieVersion = this.getIeVersion();
	this.ie8AndBelow = this.ie && this.ieVersion <= 8;
	this.ie9AndBelow = this.ie && this.ieVersion <= 9;
	this.ie10AndAbove = this.ie && this.ieVersion >= 10;
	this.opera = !!window.opera || /opr/.test(navigator.userAgent.toLowerCase());
	this.firefox = /firefox/.test(navigator.userAgent.toLowerCase());
	this.chrome = /chrome/.test(navigator.userAgent.toLowerCase()) && !/opr/.test(navigator.userAgent.toLowerCase());
	this.chromeIos = /crios/.test(navigator.userAgent.toLowerCase());
	this.safari = /safari/.test(navigator.userAgent.toLowerCase()) && !this.chromeIos;
}

CBrowser.prototype.getIeVersion = function ()
{
	var
		sUa = navigator.userAgent.toLowerCase(),
		iVersion = Utils.pInt(sUa.slice(sUa.indexOf('msie') + 4, sUa.indexOf(';', sUa.indexOf('msie') + 4)))
	;
	
	if (this.ie11)
	{
		iVersion = 11;
	}
	
	return iVersion;
};


/**
 * @constructor
 */
function CAjax()
{
	this.sUrl = '?/Ajax/';
	this.requests = ko.observableArray([]);
	// not "computed", because "reguests" is frequently updated
	this.openedRequestsCount = ko.observable(0);
	this.requests.subscribe(function () {
		this.openedRequestsCount(this.requests().length);
	}, this);
	
	this.aActionsWithoutAuthForSend = ['SystemLogin', 'SystemUpdateLanguageOnLogin', 'SystemLogout', 'AccountCreate', 
		'SystemSetMobile', 'AccountRegister', 'AccountGetForgotQuestion', 'AccountValidateForgotQuestion', 'AccountChangeForgotPassword'];
	
	this.aActionsWithoutAuthForSendExt = ['SocialRegister', 'HelpdeskRegister', 'HelpdeskForgot', 
			'HelpdeskLogin', 'HelpdeskForgotChangePassword', 'SystemLogout', 'CalendarList',
			'CalendarEventList', 'FilesPub'];
}

/**
 * @param {string=} sAction = ''
 */
CAjax.prototype.AddActionsWithoutAuthForSendExt = function (sAction)
{
	sAction = Utils.isUnd(sAction) ? '' : sAction;
	if (sAction !== '')
	{
		if (_.indexOf(this.aActionsWithoutAuthForSendExt, sAction) === -1)
		{
			this.aActionsWithoutAuthForSendExt.push(sAction);
		}
	}
};

/**
 * @param {string=} sAction = ''
 */
CAjax.prototype.AddActionsWithoutAuthForSend = function (sAction)
{
	sAction = Utils.isUnd(sAction) ? '' : sAction;
	if (sAction !== '')
	{
		if (_.indexOf(this.aActionsWithoutAuthForSend, sAction) === -1)
		{
			this.aActionsWithoutAuthForSend.push(sAction);
		}
	}
};

/**
 * @param {string=} sAction = ''
 * @returns {Boolean}
 */
CAjax.prototype.hasOpenedRequests = function (sAction)
{
	sAction = Utils.isUnd(sAction) ? '' : sAction;
	
	this.requests(_.filter(this.requests(), function (oReq) {
		var
			bComplete = oReq && oReq.Xhr.readyState === 4,
			bAbort = !oReq || oReq.Xhr.readyState === 0 && oReq.Xhr.statusText === 'abort',
			bSameAction = (sAction === '') || oReq && (oReq.Parameters.Action === sAction)
		;
		
		return oReq && !bComplete && !bAbort && bSameAction;
	}));
	
	return this.requests().length > 0;
};

/**
 * @return {boolean}
 */
CAjax.prototype.isSearchMessages = function ()
{
	var bSearchMessages = false;
	
	_.each(this.requests(), function (oReq) {
		if (oReq && oReq.Parameters && oReq.Parameters.Action === 'MessagesGetList' && oReq.Parameters.Search !== '')
		{
			bSearchMessages = true;
		}
	}, this);
	
	return bSearchMessages;
};

/**
 * @param {string} sAction
 */
CAjax.prototype.isAllowedActionWithoutAuth = function (sAction)
{
	return _.indexOf(this.aActionsWithoutAuthForSend, sAction) !== -1;
};

CAjax.prototype.isAllowedExtAction = function (sAction)
{
	return sAction === 'SocialRegister' || sAction === 'HelpdeskRegister' || sAction === 'HelpdeskForgot' || sAction === 'HelpdeskLogin' || sAction === 'SystemLogout';
};

/**
 * @param {Object} oParameters
 * @param {Function=} fResponseHandler
 * @param {Object=} oContext
 * @param {Function=} fDone
 */
CAjax.prototype.doSend = function (oParameters, fResponseHandler, oContext, fDone)
{
	var
		doneFunc = _.bind((fDone || null), this, oParameters, fResponseHandler, oContext),
		failFunc = _.bind(this.fail, this, oParameters, fResponseHandler, oContext),
		alwaysFunc = _.bind(this.always, this, oParameters),
		oXhr = null
	;
	
	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('ajax-default-request', [oParameters.Action, oParameters]);
	}
	
	if (AppData.Token)
	{
		oParameters.Token = AppData.Token;
	}

	this.abortRequests(oParameters);
	
	Utils.log('Ajax request send', oParameters.Action, oParameters);
	
	oXhr = $.ajax({
		url: this.sUrl,
		type: 'POST',
		async: true,
		dataType: 'json',
		data: oParameters,
		success: doneFunc,
		error: failFunc,
		complete: alwaysFunc,
		timeout: oParameters.Action === 'MessagesGetBodies' ? 100000 : 50000
	});
	
	this.requests().push({Parameters: oParameters, Xhr: oXhr});
};

/**
 * @param {Object} oParameters
 * @param {Function=} fResponseHandler
 * @param {Object=} oContext
 */
CAjax.prototype.send = function (oParameters, fResponseHandler, oContext)
{
	var
		bCurrentAccountId = oParameters.AccountID === undefined,
		bAccountExists = bCurrentAccountId || AppData.Accounts.hasAccountWithId(oParameters.AccountID)
	;
	
	if (oParameters && (AppData.Auth && bAccountExists || this.isAllowedActionWithoutAuth(oParameters.Action)))
	{
		if (bCurrentAccountId && oParameters.Action !== 'Login')
		{
			oParameters.AccountID = AppData.Accounts.currentId();
		}
		
		this.doSend(oParameters, fResponseHandler, oContext, this.done);
	}
};

/**
 * @param {Object} oParameters
 * @param {Function=} fResponseHandler
 * @param {Object=} oContext
 */
CAjax.prototype.sendExt = function (oParameters, fResponseHandler, oContext)
{	
	var
		bAllowWithoutAuth = _.indexOf(this.aActionsWithoutAuthForSendExt, oParameters.Action) !== -1
	;
	
	if (oParameters && (AppData.Auth || bAllowWithoutAuth))
	{
		if (AppData.TenantHash)
		{
			oParameters.TenantHash = AppData.TenantHash;
		}
		
		this.doSend(oParameters, fResponseHandler, oContext, this.doneExt);
	}
};

/**
 * @param {Object} oParameters
 */
CAjax.prototype.abortRequests = function (oParameters)
{
	switch (oParameters.Action)
	{
		case 'MessageMove':
		case 'MessageDelete':
			this.abortRequestByActionName('MessagesGetList', {'Folder': oParameters.Folder});
			this.abortRequestByActionName('MessageGet');
			break;
		case 'MessagesGetList':
		case 'MessageSetSeen':
		case 'MessageSetFlagged':
			this.abortRequestByActionName('MessagesGetList', {'Folder': oParameters.Folder});
			break;
		case 'MessagesSetAllSeen':
			this.abortRequestByActionName('MessagesGetList', {'Folder': oParameters.Folder});
			this.abortRequestByActionName('MessagesGetListByUids', {'Folder': oParameters.Folder});
			break;
		case 'FolderClear':
			this.abortRequestByActionName('MessagesGetList', {'Folder': oParameters.Folder});
			
			// FoldersGetRelevantInformation-request aborted during folder cleaning, not to get the wrong information.
			this.abortRequestByActionName('FoldersGetRelevantInformation');
			break;
		case 'FoldersGetRelevantInformation':
			this.abortRequestByActionName('FoldersGetRelevantInformation');
			break;
		case 'MessagesGetFlags':
			this.abortRequestByActionName('MessagesGetFlags');
			break;
		case 'ContactList':
		case 'ContactGlobalList':
			this.abortRequestByActionName('ContactList');
			this.abortRequestByActionName('ContactGlobalList');
			break;
		case 'ContactGet':
		case 'ContactGlobal':
			this.abortRequestByActionName('ContactGet');
			this.abortRequestByActionName('ContactGlobal');
			break;
		case 'CalendarEventUpdate':
			this.abortRequestByActionName('CalendarEventUpdate', {'calendarId': oParameters.calendarId, 'uid': oParameters.uid});
			break;
		case 'CalendarList':
			this.abortRequestByActionName('CalendarList');
			break;
		case 'CalendarEventList':
			this.abortRequestByActionName('CalendarEventList');
			break;
	}
};

/**
 * @param {string} sAction
 * @param {Object=} oParameters
 */
CAjax.prototype.abortRequestByActionName = function (sAction, oParameters)
{
	var bDoAbort;
	
	_.each(this.requests(), function (oReq, iIndex) {
		bDoAbort = false;
		
		if (oReq && oReq.Parameters.Action === sAction)
		{
			switch (sAction)
			{
				case 'MessagesGetList':
					if (oParameters.Folder === oReq.Parameters.Folder)
					{
						bDoAbort = true;
					}
					break;
				case 'CalendarEventUpdate':
					if (oParameters.calendarId === oReq.Parameters.calendarId && 
							oParameters.uid === oReq.Parameters.uid)
					{
						bDoAbort = true;
					}
					break;
				default:
					bDoAbort = true;
					break;
			}
		}
		if (bDoAbort)
		{
			oReq.Xhr.abort();
			this.requests()[iIndex] = undefined;
		}
	}, this);
	
	this.requests(_.compact(this.requests()));
};

CAjax.prototype.abortAllRequests = function ()
{
	_.each(this.requests(), function (oReq) {
		if (oReq)
		{
			oReq.Xhr.abort();
		}
	}, this);
	
	this.requests([]);
};

/**
 * @param {Object} oParameters
 * @param {Function} fResponseHandler
 * @param {Object} oContext
 * @param {{Result:boolean}} oData
 * @param {string} sType
 * @param {Object} oXhr
 */
CAjax.prototype.done = function (oParameters, fResponseHandler, oContext, oData, sType, oXhr)
{
	var
		bAllowedActionWithoutAuth = this.isAllowedActionWithoutAuth(oParameters.Action),
		bAccountExists = AppData.Accounts.hasAccountWithId(oParameters.AccountID),
		bDefaultAccount = (oParameters.AccountID === AppData.Accounts.defaultId())
	;
	
	Utils.log('Ajax request done', oParameters.Action, sType, Utils.getAjaxDataForLog(oParameters.Action, oData), oParameters);
	
	if (bAllowedActionWithoutAuth || bAccountExists)
	{
		if (oData && !oData.Result)
		{
			switch (oData.ErrorCode)
			{
				case Enums.Errors.InvalidToken:
					if (!bAllowedActionWithoutAuth)
					{
						App.tokenProblem();
					}
					break;
				case Enums.Errors.AuthError:
					if (bDefaultAccount && !bAllowedActionWithoutAuth)
					{
						this.abortAllRequests();
						App.authProblem();
					}
					break;
			}
		}

		this.executeResponseHandler(fResponseHandler, oContext, oData, oParameters);
	}
};

/**
 * @param {Object} oParameters
 * @param {Function} fResponseHandler
 * @param {Object} oContext
 * @param {{Result:boolean}} oData
 * @param {string} sType
 * @param {Object} oXhr
 */
CAjax.prototype.doneExt = function (oParameters, fResponseHandler, oContext, oData, sType, oXhr)
{
	this.executeResponseHandler(fResponseHandler, oContext, oData, oParameters);
};

/**
 * @param {Object} oParameters
 * @param {Function} fResponseHandler
 * @param {Object} oContext
 * @param {Object} oXhr
 * @param {string} sType
 * @param {string} sErrorText
 */
CAjax.prototype.fail = function (oParameters, fResponseHandler, oContext, oXhr, sType, sErrorText)
{
	var oData = {'Result': false, 'ErrorCode': 0};
	
	Utils.log('Ajax request fail', oParameters.Action, sType, oParameters);
	
	switch (sType)
	{
		case 'abort':
			oData = {'Result': false, 'ErrorCode': Enums.Errors.NotDisplayedError};
			break;
		default:
		case 'error':
		case 'parseerror':
			if (sErrorText === '')
			{
				oData = {'Result': false, 'ErrorCode': Enums.Errors.NotDisplayedError};
			}
			else
			{
				oData = {'Result': false, 'ErrorCode': Enums.Errors.DataTransferFailed};
			}
			break;
	}
	
	this.executeResponseHandler(fResponseHandler, oContext, oData, oParameters);
};

/**
 * @param {Function} fResponseHandler
 * @param {Object} oContext
 * @param {Object} oData
 * @param {Object} oParameters
 */
CAjax.prototype.executeResponseHandler = function (fResponseHandler, oContext, oData, oParameters)
{
	if (!oData)
	{
		oData = {'Result': false, 'ErrorCode': 0};
	}
	
	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('ajax-default-response', [oParameters.Action, oData]);
	}
	
	if (typeof fResponseHandler === 'function' && !oData['StopExecuteResponse'])
	{
		fResponseHandler.apply(oContext, [oData, oParameters]);
	}
};

/**
 * @param {Object} oXhr
 * @param {string} sType
 * @param {{Action:string}} oParameters
 */
CAjax.prototype.always = function (oParameters, oXhr, sType)
{
	if (sType !== 'abort')
	{
		_.each(this.requests(), function (oReq, iIndex) {
			if (oReq && _.isEqual(oReq.Parameters, oParameters))
			{
				this.requests()[iIndex] = undefined;
			}
		}, this);

		this.requests(_.compact(this.requests()));

		Utils.checkConnection(oParameters.Action, sType);

		if (App.Prefetcher && sType !== 'parsererror' && !this.hasOpenedRequests())
		{
			App.Prefetcher.start();
		}
	}
};



/**
 * @enum {string}
 */
Enums.Screens = {
	'Login': 'login',
	'Information': 'information',
	'Header': 'header',
	'Mailbox': 'mailbox',
	'SingleMessageView': 'single-message-view',
	'Compose': 'compose',
	'SingleCompose': 'single-compose',
	'Settings': 'settings',
	'Contacts': 'contacts',
	'Calendar': 'calendar',
	'FileStorage': 'files',
	'Helpdesk': 'helpdesk',
	'SingleHelpdesk': 'single-helpdesk'
};

/**
 * @enum {number}
 */
Enums.CalendarDefaultTab = {
	'Day': 1,
	'Week': 2,
	'Month': 3
};

/**
 * @enum {number}
 */
Enums.TimeFormat = {
	'F24': '0',
	'F12': '1'
};

/**
 * @enum {number}
 */
Enums.Errors = {
	'InvalidToken': 101,
	'AuthError': 102,
	'DataBaseError': 104,
	'LicenseProblem': 105,
	'DemoLimitations': 106,
	'Captcha': 107,
	'AccessDenied': 108,
	'CanNotGetMessage': 202,
	'ImapQuota': 205,
	'NotSavedInSentItems': 304,
	'NoRequestedMailbox': 305,
	'CanNotChangePassword': 502,
	'AccountOldPasswordNotCorrect': 503,
	'FetcherIncServerNotAvailable': 702,
	'FetcherLoginNotCorrect': 703,
	'HelpdeskThrowInWebmail': 805,
	'HelpdeskUserNotExists': 807,
	'HelpdeskUserNotActivated': 808,
	'IncorrectFileExtension': 811,
	'MailServerError': 901,
	'DataTransferFailed': 1100,
	'NotDisplayedError': 1155
};

/**
 * @enum {number}
 */
Enums.FolderTypes = {
	'Inbox': 1,
	'Sent': 2,
	'Drafts': 3,
	'Spam': 4,
	'Trash': 5,
	'Virus': 6,
	'Starred': 7,
	'System': 9,
	'User': 10
};

/**
 * @enum {string}
 */
Enums.FolderFilter = {
	'Flagged': 'flagged',
	'Unseen': 'unseen'
};

/**
 * @enum {number}
 */
Enums.LoginFormType = {
	'Email': 0,
	'Login': 3,
	'Both': 4
};

/**
 * @enum {number}
 */
Enums.LoginSignMeType = {
	'DefaultOff': 0,
	'DefaultOn': 1,
	'Unuse': 2
};

/**
 * @enum {string}
 */
Enums.ReplyType = {
	'Reply': 'reply',
	'ReplyAll': 'reply-all',
	'Resend': 'resend',
	'Forward': 'forward'
};

/**
 * @enum {number}
 */
Enums.Importance = {
	'Low': 5,
	'Normal': 3,
	'High': 1
};

/**
 * @enum {number}
 */
Enums.Sensitivity = {
	'Nothing': 0,
	'Confidential': 1,
	'Private': 2,
	'Personal': 3
};

/**
 * @enum {string}
 */
Enums.ContactEmailType = {
	'Personal': 'Personal',
	'Business': 'Business',
	'Other': 'Other'
};

/**
 * @enum {string}
 */
Enums.ContactPhoneType = {
	'Mobile': 'Mobile',
	'Personal': 'Personal',
	'Business': 'Business'
};

/**
 * @enum {string}
 */
Enums.ContactAddressType = {
	'Personal': 'Personal',
	'Business': 'Business'
};

/**
 * @enum {string}
 */
Enums.ContactSortType = {
	'Email': 'Email',
	'Name': 'Name',
	'Frequency': 'Frequency'
};

/**
 * @enum {number}
 */
Enums.SaveMail = {
	'Hidden': 0,
	'Checked': 1,
	'Unchecked': 2
};

/**
 * @enum {string}
 */
Enums.SettingsTab = {
	'Common': 'common',
	'EmailAccounts': 'accounts',
	'Calendar': 'calendar',
	'MobileSync': 'mobile_sync',
	'OutLookSync': 'outlook_sync',
	'Helpdesk': 'helpdesk',
	'Pgp': 'pgp',
	'Services': 'services',
	'CloudStorage': 'cloud-storage'
};

/**
 * @enum {string}
 */
Enums.AccountSettingsTab = {
	'Properties': 'properties',
	'Signature': 'signature',
	'Filters': 'filters',
	'Autoresponder': 'autoresponder',
	'Forward': 'forward',
	'Folders': 'folders',
	'FetcherInc': 'fetcher-inc',
	'FetcherOut': 'fetcher-out',
	'FetcherSig': 'fetcher-sig',
	'IdentityProperties': 'identity-properties',
	'IdentitySignature': 'identity-signature'
};

/**
 * @enum {number}
 */
Enums.AccountCreationPopupType = {
	'OneStep': 1,
	'TwoSteps': 2,
	'ConnectToMail': 3
};

/**
 * @enum {number}
 */
Enums.ContactsGroupListType = {
	'Personal': 0,
	'SubGroup': 1,
	'Global': 2,
	'SharedToAll': 3,
	'All': 4
};

/**
 * @enum {string}
 */
Enums.IcalType = {
	Request: 'REQUEST',
	Reply: 'REPLY',
	Cancel: 'CANCEL',
	Save: 'SAVE'
};

/**
 * @enum {string}
 */
Enums.IcalConfig = {
	Accepted: 'ACCEPTED',
	Declined: 'DECLINED',
	Tentative: 'TENTATIVE',
	NeedsAction: 'NEEDS-ACTION'
};

/**
 * @enum {number}
 */
Enums.IcalConfigInt = {
	Accepted: 1,
	Declined: 2,
	Tentative: 3,
	NeedsAction: 0
};

/**
 * @enum {number}
 */
Enums.Key = {
	'Tab': 9,
	'Enter': 13,
	'Shift': 16,
	'Ctrl': 17,
	'Esc': 27,
	'Space': 32,
	'PageUp': 33,
	'PageDown': 34,
	'End': 35,
	'Home': 36,
	'Up': 38,
	'Down': 40,
	'Left': 37,
	'Right': 39,
	'Del': 46,
	'Six': 54,
	'a': 65,
	'b': 66,
	'c': 67,
	'f': 70,
	'i': 73,
	'k': 75,
	'n': 78,
	'p': 80,
	'q': 81,
	'r': 82,
	's': 83,
	'u': 85,
	'v': 86,
	'y': 89,
	'z': 90,
	'F5': 116,
	'Comma': 188,
	'Dot': 190,
	'Dash': 192,
	'Apostrophe': 222
};

Enums.MouseKey = {
	'Left': 0,
	'Middle': 1,
	'Right': 2
};

/**
 * @enum {number}
 */
Enums.FileStorageType = {
	'Personal': 'personal',
	'Corporate': 'corporate',
	'Shared': 'shared',
	'GoogleDrive': 'google',
	'Dropbox': 'dropbox'
};

/**
 * @enum {number}
 */
Enums.FileStorageLinkType = {
	'Unknown': 0,
	'GoogleDrive': 1,
	'Dropbox': 2,
	'YouTube': 3,
	'Vimeo': 4,
	'SoundCloud': 5
};

/**
 * @enum {number}
 */
Enums.HelpdeskThreadStates = {
	'None': 0,
	'Pending': 1,
	'Waiting': 2,
	'Answered': 3,
	'Resolved': 4,
	'Deferred': 5
};

/**
 * @enum {number}
 */
Enums.HelpdeskPostType = {
	'Normal': 0,
	'Internal': 1,
	'System': 2
};

/**
 * @enum {number}
 */
Enums.HelpdeskFilters = {
	'All': 0,
	'Pending': 1,
	'Resolved': 2,
	'InWork': 3,
	'Open': 4,
	'Archived': 9
};

/**
 * @enum {number}
 */
Enums.CalendarAccess = {
	'Full': 0,
	'Write': 1,
	'Read': 2
};

/**
 * @enum {number}
 */
Enums.CalendarEditRecurrenceEvent = {
	'None': 0,
	'OnlyThisInstance': 1,
	'AllEvents': 2
};

/**
 * @enum {number}
 */
Enums.CalendarRepeatPeriod = {
	'None': 0,
	'Daily': 1,
	'Weekly': 2,
	'Monthly': 3,
	'Yearly': 4
};

/**
 * @enum {number}
 */
Enums.CalendarAlways = {
    'Disable': 0,
    'Enable': 1
};

Enums.PhoneAction = {
	'Offline': 'offline',
	'OfflineError': 'offline_error',
	'OfflineInit': 'offline_init',
	'OfflineActive': 'offline_active',
	'Online': 'online',
	'OnlineActive': 'online_active',
	'Incoming': 'incoming',
	'IncomingConnect': 'incoming_connect',
	'Outgoing': 'outgoing',
	'OutgoingConnect': 'outgoing_connect',
	'Settings': 'settings'
};

Enums.HtmlEditorImageSizes = {
	'Small': 'small',
	'Medium': 'medium',
	'Large': 'large',
	'Original': 'original'
};

Enums.MobilePanel = {
	'Groups': 1,
	'Items': 2,
	'View': 3
};

Enums.PgpAction = {
	'Import': 'import',
	'Generate': 'generate',
	'Encrypt': 'encrypt',
	'Sign': 'sign',
	'EncryptSign': 'encrypt-sign',
	'Verify': 'ferify',
	'DecryptVerify': 'decrypt-ferify'
};

Enums.SocialType = {
	'Unknown': 0,
	'Google': 1,
	'Dropbox': 2,
	'Facebook': 3,
	'Twitter': 4,
	'Vkontakte': 5
};

Enums.notificationPermission = {
	'Granted': 'granted',
	'Denied': 'denied',
	'Default': 'default'
};

Enums.AnotherMessageComposedAnswer = {
	'Discard': 'Discard',
	'SaveAsDraft': 'SaveAsDraft',
	'Cancel': 'Cancel'
};


ko.bindingHandlers.command = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		var
			jqElement = $(oElement),
			oCommand = fValueAccessor()
		;

		if (!oCommand || !oCommand.enabled || !oCommand.canExecute)
		{
			throw new Error('You are not using command function');
		}

		jqElement.addClass('command');
		ko.bindingHandlers[jqElement.is('form') ? 'submit' : 'click'].init.apply(oViewModel, arguments);
	},

	'update': function (oElement, fValueAccessor) {

		var
			bResult = true,
			jqElement = $(oElement),
			oCommand = fValueAccessor()
		;

		bResult = oCommand.enabled();
		jqElement.toggleClass('command-not-enabled', !bResult);

		if (bResult)
		{
			bResult = oCommand.canExecute();
			jqElement.toggleClass('unavailable', !bResult);
		}

		jqElement.toggleClass('command-disabled disable disabled', !bResult);
		jqElement.toggleClass('command-disabled', !bResult);

//		if (jqElement.is('input') || jqElement.is('button'))
//		{
//			jqElement.prop('disabled', !bResult);
//		}
	}
};

ko.bindingHandlers.simpleTemplate = {
	'init': function (oElement, fValueAccessor) {
		var oEl = $(oElement);
		
		if (oEl.length > 0 && oEl.data('replaced') !== 'replaced')
		{
			oEl.html(oEl.html().replace(/&lt;script(.*?)&gt;/i, '<script$1>').replace(/&lt;\/script(.*?)&gt;/i, '</script>'));
			oEl.data('replaced', 'replaced');
		}
	}
};

ko.bindingHandlers.findFocused = {
	'init': function (oElement) {

		var
			$oEl = $(oElement),
			$oInp = null
		;

		$oInp = $oEl.find('.catch-focus');
		if ($oInp && 1 === $oInp.length && $oInp[0])
		{
			$oInp.on('blur', function () {
				$oEl.removeClass('focused');
			}).on('focus', function () {
				$oEl.addClass('focused');
			});
		}
	}
};

ko.bindingHandlers.findFilled = {
	'init': function (oElement) {

		var
			$oEl = $(oElement),
			$oInp = null,
			fFunc = null
		;

		$oInp = $oEl.find('.catch-filled');
		if ($oInp && 1 === $oInp.length && $oInp[0])
		{
			fFunc = function () {
				$oEl.toggleClass('filled', '' !== $oInp.val());
			};

			fFunc();
			_.delay(fFunc, 200);
			$oInp.on('change', fFunc);
		}
	}
};

ko.bindingHandlers.alert = {
	'init': function (oElement, fValueAccessor) {
		window.alert(ko.utils.unwrapObservable(fValueAccessor()));
	},
	'update': function (oElement, fValueAccessor) {
		window.alert(ko.utils.unwrapObservable(fValueAccessor()));
	}
};

ko.bindingHandlers.onEnter = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		ko.bindingHandlers.event.init(oElement, function () {
			return {
				'keyup': function (oData, oEvent) {
					if (oEvent && 13 === window.parseInt(oEvent.keyCode, 10))
					{
						$(oElement).trigger('change');
						fValueAccessor().call(this, oData);
					}
				}
			};
		}, fAllBindingsAccessor, oViewModel);
	}
};

ko.bindingHandlers.onCtrlEnter = {
	'init': bMobileApp ? null : function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		ko.bindingHandlers.event.init(oElement, function () {
			return {
				'keydown': function (oData, oEvent) {
					if (oEvent && 13 === window.parseInt(oEvent.keyCode, 10) && oEvent.ctrlKey)
					{
						$(oElement).trigger('change');
						fValueAccessor().call(this, oData);

						return false;
					}

					return true;
				}
			};
		}, fAllBindingsAccessor, oViewModel);
	}
};

ko.bindingHandlers.onEsc = {
	'init': bMobileApp ? null : function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		ko.bindingHandlers.event.init(oElement, function () {
			return {
				'keyup': function (oData, oEvent) {
					if (oEvent && 27 === window.parseInt(oEvent.keyCode, 10))
					{
						$(oElement).trigger('change');
						fValueAccessor().call(this, oData);
					}
				}
			};
		}, fAllBindingsAccessor, oViewModel);
	}
};

ko.bindingHandlers.onFocusSelect = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		ko.bindingHandlers.event.init(oElement, function () {
			return {
				'focus': function () {
					oElement.select();
				}
			};
		}, fAllBindingsAccessor, oViewModel);
	}
};

ko.bindingHandlers.onEnterChange = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		ko.bindingHandlers.event.init(oElement, function () {
			return {
				'keyup': function (oData, oEvent) {
					if (oEvent && 13 === window.parseInt(oEvent.keyCode, 10))
					{
						$(oElement).trigger('change');
					}
				}
			};
		}, fAllBindingsAccessor, oViewModel);
	}
};

ko.bindingHandlers.fadeIn = {
	'update': function (oElement, fValueAccessor) {
		if (ko.utils.unwrapObservable(fValueAccessor()))
		{
			$(oElement).hide().fadeIn('fast');
		}
	}
};

ko.bindingHandlers.fadeOut = {
	'update': function (oElement, fValueAccessor) {
		if (ko.utils.unwrapObservable(fValueAccessor()))
		{
			$(oElement).fadeOut();
		}
	}
};

ko.bindingHandlers.csstext = {
	'init': function (oElement, fValueAccessor) {
		if (oElement && oElement.styleSheet && !Utils.isUnd(oElement.styleSheet.cssText))
		{
			oElement.styleSheet.cssText = ko.utils.unwrapObservable(fValueAccessor());
		}
		else
		{
			$(oElement).text(ko.utils.unwrapObservable(fValueAccessor()));
		}
	},
	'update': function (oElement, fValueAccessor) {
		if (oElement && oElement.styleSheet && !Utils.isUnd(oElement.styleSheet.cssText))
		{
			oElement.styleSheet.cssText = ko.utils.unwrapObservable(fValueAccessor());
		}
		else
		{
			$(oElement).text(ko.utils.unwrapObservable(fValueAccessor()));
		}
	}
};

ko.bindingHandlers.i18n = {
	'init': function (oElement, fValueAccessor) {

		var
			sKey = $(oElement).data('i18n'),
			sValue = sKey ? Utils.i18n(sKey) : sKey
		;

		if ('' !== sValue)
		{
			switch (fValueAccessor()) {
			case 'value':
				$(oElement).val(sValue);
				break;
			case 'text':
				$(oElement).text(sValue);
				break;
			case 'html':
				$(oElement).html(sValue);
				break;
			case 'title':
				$(oElement).attr('title', sValue);
				break;
			case 'placeholder':
				$(oElement).attr({'placeholder': sValue});
				break;
			}
		}
	}
};

ko.bindingHandlers.link = {
	'init': function (oElement, fValueAccessor) {
		$(oElement).attr('href', ko.utils.unwrapObservable(fValueAccessor()));
	}
};

ko.bindingHandlers.title = {
	'init': function (oElement, fValueAccessor) {
		$(oElement).attr('title', ko.utils.unwrapObservable(fValueAccessor()));
	},
	'update': function (oElement, fValueAccessor) {
		$(oElement).attr('title', ko.utils.unwrapObservable(fValueAccessor()));
	}
};

ko.bindingHandlers.initDom = {
	'init': function (oElement, fValueAccessor) {
		if (fValueAccessor()) {
			if (_.isArray(fValueAccessor()))
			{
				var
					aList = fValueAccessor(),
					iIndex = aList.length - 1
				;

				for (; 0 <= iIndex; iIndex--)
				{
					aList[iIndex]($(oElement));
				}
			}
			else
			{
				fValueAccessor()($(oElement));
			}
		}
	}
};

ko.bindingHandlers.customScrollbar = {
	'init': bMobileApp ? null : function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		if (bMobileDevice)
		{
			return;
		}

		var
			jqElement = $(oElement),
			oCommand = _.defaults(fValueAccessor(), {
				'oScroll' : null,
				'scrollToTopTrigger': null,
				'scrollToBottomTrigger': null,
				'scrollTo': null

			}),
			oScroll = null
		;

		/*_.delay(_.bind(function () {
			var jqCustomScrollbar = jqElement.find('.customscroll-scrollbar-vertical');

			jqCustomScrollbar.on('click', function (oEv) {
				oEv.stopPropagation();
			});
		}, this), 1000);*/



		oCommand = /** @type {{scrollToTopTrigger:{subscribe:Function},scrollToBottomTrigger:{subscribe:Function},scrollTo:{subscribe:Function},reset:Function}}*/ oCommand;

		jqElement.addClass('scroll-wrap').customscroll(oCommand);
		oScroll = jqElement.data('customscroll');

		if (oCommand['oScroll'] && Utils.isFunc(oCommand['oScroll'].subscribe)) {		
			oCommand['oScroll'](oScroll);
		} else {
			oCommand['oScroll'] = oScroll;
		}

		if (!Utils.isUnd(oCommand.reset)) {
			oElement._customscroll_reset = _.throttle(function () {
				oScroll.reset();
			}, 100);
		}
		
		if (oCommand['scrollToTopTrigger'] && Utils.isFunc(oCommand.scrollToTopTrigger.subscribe)) {
			oCommand.scrollToTopTrigger.subscribe(function () {
				if (oScroll) {
					oScroll['scrollToTop']();
				}
			});
		}
		
		if (oCommand['scrollToBottomTrigger'] && Utils.isFunc(oCommand.scrollToBottomTrigger.subscribe)) {
			oCommand.scrollToBottomTrigger.subscribe(function () {
				if (oScroll) {
					oScroll['scrollToBottom']();
				}
			});
		}

		if (oCommand['scrollTo'] && Utils.isFunc(oCommand.scrollTo.subscribe)) {
			oCommand.scrollTo.subscribe(function () {
				if (oScroll) {
					oScroll['scrollTo'](oCommand.scrollTo());
				}
			});
		}
	},
	
	'update': bMobileApp ? null : function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {
		if (bMobileDevice)
		{
			return;
		}
		if (oElement._customscroll_reset) {
			oElement._customscroll_reset();
		}
		if (!Utils.isUnd(fValueAccessor().top)) {

			$(oElement).data('customscroll')['vertical'].set(fValueAccessor().top);
		}
	}
};

/*jslint vars: true*/
ko.bindingHandlers.customOptions = {
	'init': function () {
		return {
			'controlsDescendantBindings': true
		};
	},

	'update': function (element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
		var i = 0, j = 0;
		var previousSelectedValues = ko.utils.arrayMap(ko.utils.arrayFilter(element.childNodes, function (node) {
			return node.tagName && node.tagName === 'OPTION' && node.selected;
		}), function (node) {
			return ko.selectExtensions.readValue(node) || node.innerText || node.textContent;
		});
		var previousScrollTop = element.scrollTop;
		var value = ko.utils.unwrapObservable(valueAccessor());

		// Remove all existing <option>s.
		while (element.length > 0)
		{
			ko.cleanNode(element.options[0]);
			element.remove(0);
		}

		if (value)
		{
			if (typeof value.length !== 'number')
			{
				value = [value];
			}

			var optionsBind = allBindingsAccessor()['optionsBind'];
			for (i = 0, j = value.length; i < j; i++)
			{
				var option = document.createElement('OPTION');
				var optionValue = ko.utils.unwrapObservable(value[i]);
				ko.selectExtensions.writeValue(option, optionValue);
				option.appendChild(document.createTextNode(optionValue));
				element.appendChild(option);
				if (optionsBind)
				{
					option.setAttribute('data-bind', optionsBind);
					ko.applyBindings(bindingContext['createChildContext'](optionValue), option);
				}
			}

			var newOptions = element.getElementsByTagName('OPTION');
			var countSelectionsRetained = 0;
			var isIe = navigator.userAgent.indexOf("MSIE 6") >= 0;
			for (i = 0, j = newOptions.length; i < j; i++)
			{
				if (ko.utils.arrayIndexOf(previousSelectedValues, ko.selectExtensions.readValue(newOptions[i])) >= 0)
				{
					if (isIe) {
						newOptions[i].setAttribute("selected", true);
					} else {
						newOptions[i].selected = true;
					}

					countSelectionsRetained++;
				}
			}

			element.scrollTop = previousScrollTop;

			if (countSelectionsRetained < previousSelectedValues.length)
			{
				ko.utils.triggerEvent(element, 'change');
			}
		}
	}
};
/*jslint vars: false*/

ko.bindingHandlers.splitter = {
	'init': bMobileApp ? null : function (oElement, fValueAccessor) {
		setTimeout(function() {
			$(oElement).splitter(fValueAccessor());
		}, 1);
	}
};

ko.bindingHandlers.dropdown = {
	'update': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		var
			jqElement = $(oElement),
			oCommand = _.defaults(
				fValueAccessor(), {
					'disabled': 'disabled',
					'expand': 'expand',
					'control': true,
					'container': '.dropdown_content',
					'scrollToTopContainer': '.scroll-inner',
					'passClick': true,
					'trueValue': true
				}
			),
			bControl = typeof oCommand['control'] === 'function' ? oCommand['control']() : oCommand['control'],
			jqControl = jqElement.find('.control'),
			jqDrop = jqElement.find('.dropdown'),
			jqDropHelper = jqElement.find('.dropdown_helper'),
			jqDropArrow = jqElement.find('.dropdown_arrow'),
			jqDropBottomArrow = jqElement.find('.dropdown_arrow.bottom'),
			oDocument = $(document),
			bScrollBar = false,
			oOffset,
			iLeft,
			iFitToScreenOffset,
			fCallback = function () {
				if (!Utils.isUnd(oCommand['callback']))
				{
					oCommand['callback'].call(
						oViewModel,
						jqElement.hasClass(oCommand['expand']) ? oCommand['trueValue'] : false,
						jqElement
					);
				}
			},
			fStop = function (event) {
				event.stopPropagation();
			},
			fScrollToTop = function () {
				if (oCommand['scrollToTopContainer'])
				{
					jqElement.find(oCommand['scrollToTopContainer']).scrollTop(0);
				}
			},
			fToggleExpand = function (bValue) {
				if (Utils.isUnd(bValue))
				{
					bValue = !jqElement.hasClass(oCommand['expand']);
				}

				if (!bValue && jqElement.hasClass(oCommand['expand']))
				{
					fScrollToTop();
				}

				jqElement.toggleClass(oCommand['expand'], bValue);
				
				if (jqDropBottomArrow.length > 0 && jqElement.hasClass(oCommand['expand']))
				{
					jqDrop.css({
						'top': (jqElement.position().top - jqDropHelper.height()) + 'px',
						'left': jqElement.position().left + 'px',
						'width': 'auto'
					});
				}
			},
			fFitToScreen = function (iOffsetLeft) {
				oOffset = jqDropHelper.offset();
				if (!Utils.isUnd(oOffset))
				{
					iLeft = oOffset.left + 10;
					iFitToScreenOffset = $(window).width() - (iLeft + jqDropHelper.outerWidth(true));

					if (iFitToScreenOffset > 0)
					{
						iFitToScreenOffset = 0;
					}

					jqDropHelper.css('left', iOffsetLeft || iFitToScreenOffset + 'px');
					jqDropArrow.css('left', iOffsetLeft || Math.abs(iFitToScreenOffset ? iFitToScreenOffset + parseInt(jqDropArrow.css('margin-left')) : 0) + 'px');
				}
			},
			fControlClick = function (oEv) {
				var
					jqDropdownParent = $(oEv.originalEvent.originalTarget).parents('.dropdown'),
					bHasDropdownParent = jqDropdownParent.length > 0
				;
				
				if (!bHasDropdownParent && !jqElement.hasClass(oCommand['disabled']) && !bScrollBar)
				{

					fToggleExpand();

					_.defer(function(){
						fCallback();
					});

					if (jqElement.hasClass(oCommand['expand']))
					{

						if (oCommand['close'] && oCommand['close']['subscribe'])
						{
							oCommand['close'](true);
						}
						
						_.defer(function () {
							oDocument.on('click.dropdown', function (ev) {
								if((oCommand['passClick'] || ev.button !== Enums.MouseKey.Right) && !bScrollBar)
								{
									oDocument.unbind('click.dropdown');
									if (oCommand['close'] && oCommand['close']['subscribe'])
									{
										oCommand['close'](false);
									}

									fToggleExpand(false);

									fCallback();
									fFitToScreen(0);
								}
								bScrollBar = false;
							});
						});

						fFitToScreen();
					}
				}
			}
		;
		
		jqElement.off();
		jqControl.off();
		
		if (!oCommand['passClick'])
		{
			jqElement.find(oCommand['container']).on('click', fStop);
			jqElement.on('click', fStop);
			jqControl.on('click', fStop);
		}

		fToggleExpand(false);
		
		if (oCommand['close'] && oCommand['close']['subscribe'])
		{
			oCommand['close'].subscribe(function (bValue) {
				if (!bValue)
				{
					oDocument.unbind('click.dropdown');
					fToggleExpand(false);
				}

				fCallback();
			});
		}

		jqElement.on('mousedown', function (oEv, oEl) {
			bScrollBar = ($(oEv.target).hasClass('customscroll-scrollbar') || $(oEv.target.parentElement).hasClass('customscroll-scrollbar'));
		});

		jqElement.on('click', function (oEv) {
			if (!bControl)
			{
				fControlClick(oEv);
			}
		});
		jqControl.on('click', function (oEv) {
			if (bControl)
			{
				fControlClick(oEv);
			}
		});
	}
};

ko.bindingHandlers.customSelect = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		var
			jqElement = $(oElement),
			oCommand = _.defaults(
				fValueAccessor(), {
					'disabled': 'disabled',
					'selected': 'selected',
					'expand': 'expand',
					'control': true,
					'input': false,
					'expandState': function () {}
				}
			),
			aOptions = [],
			oControl = oCommand['control'] ? jqElement.find('.control') : jqElement,
			oContainer = jqElement.find('.dropdown_content'),
			oText = jqElement.find('.link'),

			updateField = function (value) {
				_.each(aOptions, function (item) {
					item.removeClass(oCommand['selected']);
				});
				var item = _.find(oCommand['options'], function (item) {
					return item[oCommand['optionsValue']] === value;
				});
				if (Utils.isUnd(item)) {
					item = oCommand['options'][0];
				}
				else
				{
					aOptions[_.indexOf(oCommand['options'], item)].addClass(oCommand['selected']);
					oText.text($.trim(item[oCommand['optionsText']]));
				}

//				aOptions[_.indexOf(oCommand['options'], item)].addClass(oCommand['selected']);
//				oText.text($.trim(item[oCommand['optionsText']]));

				return item[oCommand['optionsValue']];
			},
			updateList = function (aList) {
				oContainer.empty();
				aOptions = [];

				_.each(aList ? aList : oCommand['options'], function (item) {
					var
						oOption = $('<span class="item"></span>')
							.text(item[oCommand['optionsText']])
							.data('value', item[oCommand['optionsValue']]),
						isDisabled = item['isDisabled']
						;

					if (isDisabled)
					{
						oOption.data('isDisabled', isDisabled).addClass('disabled');
					}
					else
					{
						oOption.data('isDisabled', isDisabled).removeClass('disabled');
					}

					aOptions.push(oOption);
					oContainer.append(oOption);
				}, this);
			}
		;

		updateList();

		oContainer.on('click', '.item', function () {
			var jqItem = $(this);

			if(!jqItem.data('isDisabled'))
			{
				oCommand.value(jqItem.data('value'));
			}
		});

		if (!oCommand.input && oCommand['value'] && oCommand['value'].subscribe)
		{
			oCommand['value'].subscribe(function () {
				var mValue = updateField(oCommand['value']());
				if (oCommand['value']() !== mValue)
				{
					oCommand['value'](mValue);
				}
			}, oViewModel);

			oCommand['value'].valueHasMutated();
		}

		if (oCommand.input && oCommand['value'] && oCommand['value'].subscribe)
		{
			oCommand['value'].subscribe(function () {
				updateField(oCommand['value']());
			}, oViewModel);

			oCommand['value'].valueHasMutated();
		}
		
		if (oCommand.input && oCommand['value'] && oCommand['value'].subscribe)
		{
			oCommand['value'].subscribe(function () {
				updateField(oCommand['value']());
			}, oViewModel);

			oCommand['value'].valueHasMutated();
		}

		if(oCommand.alarmOptions)
		{
			oCommand.alarmOptions.subscribe(function () {
				updateList();
			}, oViewModel);
		}
		if(oCommand.timeOptions)
		{
			oCommand.timeOptions.subscribe(function (aList) {
				updateList(aList);
			}, oViewModel);
		}

		//TODO fix data-bind click
		jqElement.removeClass(oCommand['expand']);
		oControl.click(function(ev){
			if (!jqElement.hasClass(oCommand['disabled'])) {
				jqElement.toggleClass(oCommand['expand']);
				oCommand['expandState'](jqElement.hasClass(oCommand['expand']));

				if (jqElement.hasClass(oCommand['expand'])) {
					var	jqContent = jqElement.find('.dropdown_content'),
						jqSelected = jqContent.find('.selected');

					if (jqSelected.position()) {
						jqContent.scrollTop(0);// need for proper calculation position().top
						jqContent.scrollTop(jqSelected.position().top - 100);// 100 - hardcoded indent to the element in pixels
					}

					_.defer(function(){
						$(document).one('click', function () {
							jqElement.removeClass(oCommand['expand']);
							oCommand['expandState'](false);
						});
					});
				}
				/*else
				{
					jqElement.addClass(oCommand['expand']);
				}*/
			}
		});
	}
};

ko.bindingHandlers.moveToFolderFilter = {

	'init': function (oElement, fValueAccessor, allBindingsAccessor, viewModel, bindingContext) {
		var
			jqElement = $(oElement),
			oCommand = fValueAccessor(),
			jqContainer = $(oElement).find(oCommand['container']),
			aOptions = _.isArray(oCommand['options']) ? oCommand['options'] : oCommand['options'](),
			sFolderName = oCommand['value'] ? oCommand['value']() : '',
			oFolderOption = _.find(aOptions, function (oOption) {
				return oOption[oCommand['optionsValue']] === sFolderName;
			})
		;

		if (!oFolderOption)
		{
			sFolderName = '';
			oCommand['value']('');
		}

		jqElement.removeClass('expand');
		
		jqContainer.empty();

		_.each(aOptions, function (oOption) {
			var jqOption = $('<span class="item"></span>')
				.text(oOption[oCommand['optionsText']])
				.data('value', oOption[oCommand['optionsValue']]);

			if (sFolderName === oOption[oCommand['optionsValue']])
			{
				jqOption.addClass('selected');
			}
			
			oOption['jq'] = jqOption;
			
			jqContainer.append(jqOption);
		});
		
		jqContainer.on('click', '.item', function () {
			var sFolderName = $(this).data('value');
			oCommand['value'](sFolderName);
		});

		jqElement.click(function () {
			jqElement.toggleClass('expand');

			if (jqElement.hasClass('expand'))
			{
				_.defer(function () {
					$(document).one('click', function () {
						jqElement.removeClass('expand');
					});
				});
			}
		});
	},
	'update': function (oElement, fValueAccessor) {
		var
			jqElement = $(oElement),
			oCommand = fValueAccessor(),
			aOptions = _.isArray(oCommand['options']) ? oCommand['options'] : oCommand['options'](),
			sFolderName = oCommand['value'] ? oCommand['value']() : '',
			oFolderOption = _.find(aOptions, function (oOption) {
				return oOption[oCommand['optionsValue']] === sFolderName;
			}),
			jqText = jqElement.find('.link')
		;
		
		_.each(aOptions, function (oOption) {
			if (oOption['jq'])
			{
				oOption['jq'].toggleClass('selected', sFolderName === oOption[oCommand['optionsValue']]);
			}
		});
		
		if (oFolderOption)
		{
			jqText.text($.trim(oFolderOption[oCommand['optionsText']]));
		}
	}
};

ko.bindingHandlers.contactCardInMessage = {
	'update': (bMobileApp || bMobileDevice) ? null : function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		var
			jqElement = $(oElement),
			oCommand = fValueAccessor(),
			sAddress = oCommand.address,
			jqPopup = $('div.item_viewer[data-email=\'' + sAddress + '\']'),
			bPopupOpened = false,
			iCloseTimeoutId = 0,
			fOpenPopup = function () {
				if (jqPopup && jqElement)
				{
					bPopupOpened = true;
					clearTimeout(iCloseTimeoutId);
					setTimeout(function () {
						var	oOffset = jqElement.offset(),
							iLeft, iTop, iFitToScreenOffset;
						if (bPopupOpened && oOffset.left + oOffset.top !== 0)
						{
							iLeft = oOffset.left + 10;
							iTop = oOffset.top + jqElement.height() + 6;
							iFitToScreenOffset = $(window).width() - (iLeft + 396); //396 - popup outer width

							if (iFitToScreenOffset > 0) {
								iFitToScreenOffset = 0;
							}
							jqPopup.addClass('expand').offset({'top': iTop, 'left': iLeft + iFitToScreenOffset});
						}
					}, 180);
				}
			},
			fClosePopup = function () {
				if (bPopupOpened && jqPopup && jqElement)
				{
					bPopupOpened = false;
					iCloseTimeoutId = setTimeout(function () {
						if (!bPopupOpened)
						{
							jqPopup.removeClass('expand');
						}
					}, 200);
				}
			}
		;
		
		if (jqPopup.length > 0)
		{
			jqElement
				.off()
				.on('mouseover', function () {
					jqPopup
						.off()
						.on('mouseenter', fOpenPopup)
						.on('mouseleave', fClosePopup)
						.find('.link, .button')
						.off('.links')
						.on('click.links', function () {
							bPopupOpened = false;
							jqPopup.removeClass('expand');
						})
					;

					setTimeout(function () {
						jqPopup
							.find('.link, .button')
							.off('click.links')
							.on('click.links', function () {
								bPopupOpened = false;
								jqPopup.removeClass('expand');
							});
					}.bind(this), 100);

					fOpenPopup();
				})
				.on('mouseout', fClosePopup)
			;

			bPopupOpened = false;
			jqPopup.removeClass('expand');
		}
		else
		{
			jqElement.off();
		}
	}
};

ko.bindingHandlers.contactcard = {
	'init': (bMobileApp || bMobileDevice) ? null : function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel) {
		var
			jqElement = $(oElement),
			bShown = false,
			oCommand = _.defaults(
				fValueAccessor(), {
					'disabled': 'disabled',
					'expand': 'expand',
					'control': true
				}
			),
			element = oCommand['control'] ? jqElement.find('.control') : jqElement
		;

		if (oCommand['trigger'] !== undefined && oCommand['trigger'].subscribe !== undefined) {
			
			jqElement.removeClass(oCommand['expand']);
			
			element.bind({
				'mouseover': function() {
					if (!jqElement.hasClass(oCommand['disabled']) && oCommand['trigger']()) {
						bShown = true;
						_.delay(function () {
							if (bShown) {
								if (oCommand['controlWidth'] !== undefined && oCommand['controlWidth'].subscribe !== undefined) {
									oCommand['controlWidth'](element.width());
								}
								jqElement.addClass(oCommand['expand']);
							}
						}, 200);
					}
				},
				'mouseout': function() {
					if (oCommand['trigger']()) {
						bShown = false;
						_.delay(function () {
							if (!bShown) {
								jqElement.removeClass(oCommand['expand']);
							}
						}, 200);
					}
				}
			});
		}
	}
};

ko.bindingHandlers.checkmail = {
	'update': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {
			
		var
			oOptions = oElement.oOptions || null,
			jqElement = oElement.jqElement || null,
			oIconIE = oElement.oIconIE || null,
			values = fValueAccessor(),
			state = values.state
		;

		if (values.state !== undefined) {
			if (!jqElement)
			{
				oElement.jqElement = jqElement = $(oElement);
			}

			if (!oOptions)
			{
				oElement.oOptions = oOptions = _.defaults(
					values, {
						'activeClass': 'process',
						'duration': 800
					}
				);
			}

			Utils.deferredUpdate(jqElement, state, oOptions['duration'], function(element, state){
				if (App.browser.ie9AndBelow)
				{
					if (!oIconIE)
					{
						oElement.oIconIE = oIconIE = jqElement.find('.icon');
					}

					if (!oIconIE.__intervalIE && !!state)
					{
						var
							i = 0,
							style = ''
						;

						oIconIE.__intervalIE = setInterval(function() {
							style = '0px -' + (20 * i) + 'px';
							i = i < 7 ? i + 1 : 0;
							oIconIE.css({'background-position': style});
						} , 1000/12);
					}
					else
					{
						oIconIE.css({'background-position': '0px 0px'});
						clearInterval(oIconIE.__intervalIE);
						oIconIE.__intervalIE = null;
					}
				}
				else
				{
					element.toggleClass(oOptions['activeClass'], state);
				}
			});
		}
	}
};

ko.bindingHandlers.heightAdjust = {
	'update': function (oElement, fValueAccessor, fAllBindingsAccessor) {
		
		var 
			jqElement = oElement.jqElement || null,
			height = 0,
			sLocation = fValueAccessor().location,
			sDelay = fValueAccessor().delay || 400
		;

		if (!jqElement) {
			oElement.jqElement = jqElement = $(oElement);
		}
		_.delay(function () {
			_.each(fValueAccessor().elements, function (mItem) {
				
				var element = mItem();
				if (element) {
					height += element.is(':visible') ? element.outerHeight() : 0;
				}
			});
			
			if (sLocation === 'top' || sLocation === undefined) {
				jqElement.css({
					'padding-top': height,
					'margin-top': -height
				});
			} else if (sLocation === 'bottom') {
				jqElement.css({
					'padding-bottom': height,
					'margin-bottom': -height
				});
			}
		}, sDelay);
	}
};

ko.bindingHandlers.minHeightAdjust = {
	'update': function (oElement, fValueAccessor, fAllBindingsAccessor) {

		var
			jqEl = $(oElement),
			oOptions = fValueAccessor(),
			jqAdjustEl = oOptions.adjustElement || $('body'),
			iMinHeight = oOptions.minHeight || 0
		;
		
		if (oOptions.removeTrigger)
		{
			jqAdjustEl.css('min-height', 'inherit');
		}
		
		if (oOptions.trigger)
		{
			_.delay(function () {
				jqAdjustEl.css({'min-height': jqEl.outerHeight(true) + iMinHeight});
			}, 100);
		}
	}
};

ko.bindingHandlers.watchWidth = {
	'init': function (oElement, fValueAccessor) {
		var isTriggered = false;

		if (!isTriggered) {
			fValueAccessor().subscribe(function () {
				fValueAccessor()($(oElement).outerWidth());
				isTriggered = true;
			}, this);
		}
	}
};

ko.bindingHandlers.columnCalc = {
	'init': function (oElement, fValueAccessor) {

		var
			$oElement = $(oElement),
			oProp = fValueAccessor()['prop'],
			$oItem = null,
			iWidth = 0
		;
			
		$oItem = $oElement.find(fValueAccessor()['itemSelector']);

		if ($oItem[0] === undefined) {
			return;
		}
		
		iWidth = $oItem.outerWidth(true);
		iWidth = 1 >= iWidth ? 1 : iWidth;
		
		if (oProp)
		{
			$(window).bind('resize', function () {
				var iW = $oElement.width();
				oProp(0 < iW ? Math.floor(iW / iWidth) : 1);
			});
		}
	}
};

ko.bindingHandlers.listWithMoreButton = {
	'init': function (oElement, fValueAccessor) {

		var
			$Element = $(oElement),
			skipOneResize = false //for some flicker at slow resize (does not solve the problem completely TODO)
		;

		$Element.closest('div.panel.left_panel').resize(function () {
			
			var
				$ItemsVisible = $Element.find('span.hotkey'),
				$ItemsHidden = $Element.find('span.item'),
				$MoreHints = $Element.find('span.more_hints').show(),
				iElementWidth = $Element.width(),
				iMoreWidth = $MoreHints.width(),
				bHideMoreHints = true
			;

			if (!skipOneResize) {
				_.each($ItemsVisible, function (oItem, index) {

					var
						$Item = $(oItem),
						iItemWidth = $Item.width()
					;

					if (bHideMoreHints && iMoreWidth + iItemWidth < iElementWidth) {
						skipOneResize = false;
						$Item.show();
						$($ItemsHidden[index]).hide();
						iMoreWidth += iItemWidth;
					}
					else
					{
						skipOneResize = true;
						bHideMoreHints = false;
						$Item.hide();
						$($ItemsHidden[index]).show();
					}
				});

				if (bHideMoreHints)
				{
					$MoreHints.hide();
				}
			}
			else
			{
				skipOneResize = false;
			}
		});
	}
};

ko.bindingHandlers.quickReplyAnim = {
	'update': bMobileApp ? null : function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {

		var
			jqTextarea = oElement.jqTextarea || null,
			jqStatus = oElement.jqStatus || null,
			jqButtons = oElement.jqButtons || null,
			jqElement = oElement.jqElement || null,
			oPrevActions = oElement.oPrevActions || null,
			values = fValueAccessor(),
			oActions = null
		;

		oActions = _.defaults(
			values, {
				'saveAction': false,
				'sendAction': false,
				'activeAction': false
			}
		);

		if (!jqElement)
		{
			oElement.jqElement = jqElement = $(oElement);
			oElement.jqTextarea = jqTextarea = jqElement.find('textarea');
			oElement.jqStatus = jqStatus = jqElement.find('.status');
			oElement.jqButtons = jqButtons = jqElement.find('.buttons');
			
			oElement.oPrevActions = oPrevActions = {
				'saveAction': null,
				'sendAction': null,
				'activeAction': null
			};
		}

		if (true || jqElement.is(':visible'))
		{
			if (App.browser.ie9AndBelow)
			{
				if (jqTextarea && !jqElement.defualtHeight && !jqTextarea.defualtHeight)
				{
					jqElement.defualtHeight = jqElement.outerHeight();
					jqTextarea.defualtHeight = jqTextarea.outerHeight();
					jqStatus.defualtHeight = jqButtons.outerHeight();
					jqButtons.defualtHeight = jqButtons.outerHeight();
				}

				_.defer(function () {
					var 
						activeChanged = oPrevActions.activeAction !== oActions['activeAction'],
						sendChanged = oPrevActions.sendAction !== oActions['sendAction'],
						saveChanged = oPrevActions.saveAction !== oActions['saveAction']
					;

					if (activeChanged)
					{
						if (oActions['activeAction'])
						{
							jqTextarea.animate({
								'height': jqTextarea.defualtHeight + 50
							}, 300);
							jqElement.animate({
								'max-height': jqElement.defualtHeight + jqButtons.defualtHeight + 50
							}, 300);
						}
						else
						{
							jqTextarea.animate({
								'height': jqTextarea.defualtHeight
							}, 300);
							jqElement.animate({
								'max-height': jqElement.defualtHeight
							}, 300);
						}
					}

					if (sendChanged || saveChanged)
					{
						if (oActions['sendAction'])
						{
							jqElement.animate({
								'max-height': '30px'
							}, 300);
							jqStatus.animate({
								'max-height': '30px',
								'opacity': 1
							}, 300);
						}
						else if (oActions['saveAction'])
						{
							jqElement.animate({
								'max-height': 0
							}, 300);
						}
						else
						{
							jqElement.animate({
								'max-height': jqElement.defualtHeight + jqButtons.defualtHeight + 50
							}, 300);
							jqStatus.animate({
								'max-height': 0,
								'opacity': 0
							}, 300);
						}
					}
				});
			}
			else
			{
				jqElement.toggleClass('saving', oActions['saveAction']);
				jqElement.toggleClass('sending', oActions['sendAction']);
				jqElement.toggleClass('active', oActions['activeAction']);
			}
		}

		_.defer(function () {
			oPrevActions = oActions;
		});
	}
};

ko.extenders.reversible = function (oTarget)
{
	var mValue = oTarget();

	oTarget.commit = function ()
	{
		mValue = oTarget();
	};

	oTarget.revert = function ()
	{
		oTarget(mValue);
	};

	oTarget.commitedValue = function ()
	{
		return mValue;
	};

	oTarget.changed = function ()
	{
		return mValue !== oTarget();
	};
	
	return oTarget;
};

ko.extenders.autoResetToFalse = function (oTarget, iOption)
{
	oTarget.iTimeout = 0;
	oTarget.subscribe(function (bValue) {
		if (bValue)
		{
			window.clearTimeout(oTarget.iTimeout);
			oTarget.iTimeout = window.setTimeout(function () {
				oTarget.iTimeout = 0;
				oTarget(false);
			}, Utils.pInt(iOption));
		}
	});

	return oTarget;
};

/**
 * @param {(Object|null|undefined)} oContext
 * @param {Function} fExecute
 * @param {(Function|boolean|null)=} fCanExecute
 * @return {Function}
 */
Utils.createCommand = function (oContext, fExecute, fCanExecute)
{
	var
		fResult = fExecute ? function () {
			if (fResult.canExecute && fResult.canExecute())
			{
				return fExecute.apply(oContext, Array.prototype.slice.call(arguments));
			}
			return false;
		} : function () {}
	;

	fResult.enabled = ko.observable(true);

	fCanExecute = Utils.isUnd(fCanExecute) ? true : fCanExecute;
	if (Utils.isFunc(fCanExecute))
	{
		fResult.canExecute = ko.computed(function () {
			return fResult.enabled() && fCanExecute.call(oContext);
		});
	}
	else
	{
		fResult.canExecute = ko.computed(function () {
			return fResult.enabled() && !!fCanExecute;
		});
	}

	return fResult;
};

ko.bindingHandlers.autocomplete = {
	'init': function (oElement, fValueAccessor) {

		function split(val)
		{
			return val.split(/,\s*/);
		}

		function extractLast(term)
		{
			return split(term).pop();
		}

		var 
			fCallback = fValueAccessor(),
			jqEl = $(oElement)
		;

		if (fCallback && jqEl && jqEl[0])
		{
			jqEl.autocomplete({
				'minLength': 0,
				'autoFocus': true,
				'source': function (request, response) {
					fCallback(extractLast(request['term']), response);
				},
				'search': function () {
					return extractLast(this.value).length > 0;
				},
				'focus': function () {
					return false;
				},
				'select': function (event, ui) {
					var terms = split(this.value), moveCursorToEnd = null;

					terms.pop();
					terms.push(ui['item']['value']);
					terms.push('');

					this.value = terms.join(', ').slice(0, -2);

					jqEl.trigger('change');

					// Move to the end of the input string
					moveCursorToEnd = function(el) {
						var endIndex = el.value.length;

						//Chrome
						el.blur();
						el.focus();
						//IE, firefox and Opera
						if (el.setSelectionRange) {
							el.setSelectionRange(endIndex, endIndex);
						}
					};
					moveCursorToEnd(jqEl[0]);

					return false;
				}
			}).on('click', function() {
				if (jqEl.val() === '')
				{
					if (!$(jqEl.autocomplete('widget')).is(':visible'))
					{
						jqEl.autocomplete("option", "minLength", 0); //for triggering search on empty field
						jqEl.autocomplete("search");
						jqEl.autocomplete("option", "minLength", 1);
					}
					else
					{
						jqEl.autocomplete("close");
					}
				}
			});
		}
	}
};

ko.bindingHandlers.autocompleteSimple = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {

		var
			jqEl = $(oElement),
			oOptions = fValueAccessor(),
			fCallback = oOptions['callback'],
			fDataAccessor = oOptions.dataAccessor ? oOptions.dataAccessor : Utils.emptyFunction(),
			fDeleteAccessor = oOptions.deleteAccessor ? oOptions.deleteAccessor : Utils.emptyFunction(),
			fSourceResponse = Utils.emptyFunction(),
			fDelete = function () {
				fDeleteAccessor(oSelectedItem);
				$.ui.autocomplete.prototype.__response.call(jqEl.data('autocomplete'), _.filter(aSourceResponseItems, function(oItem){ return oItem.value !== oSelectedItem.value; }));
			},
			aSourceResponseItems = null,
			oSelectedItem = null
		;

		if (fCallback && jqEl && jqEl[0])
		{
			jqEl.autocomplete({
				'minLength': 1,
				'autoFocus': true,
				'position': {
					collision: "flip" //prevents the escape off the screen
				},
				'source': function (request, response) {
					fSourceResponse = response;
					fCallback(request['term'], function (oItems) { //additional layer for story oItems
						aSourceResponseItems = oItems;
						fSourceResponse(oItems);
					});
				},
				'focus': function (oEvent, oItem) {
					oSelectedItem = oItem.item;
				},
				'open': function (oEvent, oItem) {
					$(jqEl.autocomplete('widget')).find('span.del').on('click', function(oEvent, oItem) {
						Utils.calmEvent(oEvent);
						fDelete();
					});
				},
				'select': function (oEvent, oItem) {
					_.delay(function () {
						jqEl.trigger('change');
					}, 5);
					fDataAccessor(oItem.item);

					return true;
				}
			}).on('click', function(oEvent, oItem) {
				if (jqEl.val() === '')
				{
					if (!$(jqEl.autocomplete('widget')).is(':visible'))
					{
						jqEl.autocomplete("option", "minLength", 0); //for triggering search on empty field
						jqEl.autocomplete("search");
						jqEl.autocomplete("option", "minLength", 1);
					}
					else
					{
						jqEl.autocomplete("close");
					}
				}
			}).on('keydown', function(oEvent, oItem) {
				if (aSourceResponseItems && oSelectedItem && !oSelectedItem.global && oEvent.keyCode === Enums.Key.Del && oEvent.shiftKey) //shift+del on suggestions list
				{
					Utils.calmEvent(oEvent);
					fDelete();
				}
			});
		}
	}
};


ko.bindingHandlers.draggablePlace = {
	'init': bMobileApp ? null : function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {
		if (fValueAccessor() === null)
		{
			return null;
		}

		var oAllBindingsAccessor = fAllBindingsAccessor ? fAllBindingsAccessor() : null;
		$(oElement).draggable({
			'distance': 20,
			'handle': '.dragHandle',
			'cursorAt': {'top': 0, 'left': 0},
			'helper': function (oEvent) {
				//return fValueAccessor().call(oViewModel, oEvent && oEvent.target ? ko.dataFor(oEvent.target) : null);
				return fValueAccessor().apply(oViewModel, oEvent && oEvent.target ? [ko.dataFor(oEvent.target), oEvent.ctrlKey] : null);
			},
			'start': (oAllBindingsAccessor && oAllBindingsAccessor['draggableDragStartCallback']) ? oAllBindingsAccessor['draggableDragStartCallback'] : Utils.emptyFunction,
			'stop': (oAllBindingsAccessor && oAllBindingsAccessor['draggableDragStopCallback']) ? oAllBindingsAccessor['draggableDragStopCallback'] : Utils.emptyFunction
		}).on('mousedown', function () {
			Utils.removeActiveFocus();
		});
	}
};

ko.bindingHandlers.droppable = {
	'init': bMobileApp ? null : function (oElement, fValueAccessor) {
		var oOptions = fValueAccessor(),
			fValueFunc = oOptions.valueFunc,
			fSwitchObserv = oOptions.switchObserv
		;
		if (false !== fValueFunc)
		{
			$(oElement).droppable({
				'hoverClass': 'droppableHover',
				'drop': function (oEvent, oUi) {
					fValueFunc(oEvent, oUi);
				}
			});
		}
		if(fSwitchObserv && fValueFunc !== false)
		{
			fSwitchObserv.subscribe(function (bIsSelected) {
				if($(oElement).data().droppable)
				{
					if(bIsSelected)
					{
						$(oElement).droppable('disable');
					}
					else
					{
						$(oElement).droppable('enable');
					}
				}
			}, this);
			fSwitchObserv.valueHasMutated();
		}
	}
};

ko.bindingHandlers.draggable = {
	'init': bMobileApp ? null : function (oElement, fValueAccessor) {
		$(oElement).attr('draggable', ko.utils.unwrapObservable(fValueAccessor()));
	}
};

ko.bindingHandlers.autosize = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {

		var
			jqEl = $(oElement),
			oOptions = fValueAccessor(),
			iHeight = jqEl.height(),
			iOuterHeight = jqEl.outerHeight(),
			iInnerHeight = jqEl.innerHeight(),
			iBorder = iOuterHeight - iInnerHeight,
			iPaddingTB = iInnerHeight - iHeight,
			iMinHeight = oOptions.minHeight ? oOptions.minHeight : 0,
			iMaxHeight = oOptions.maxHeight ? oOptions.maxHeight : 0,
			iScrollableHeight = oOptions.scrollableHeight ? oOptions.scrollableHeight : 1000,// max-height of .scrollable_field
			oAutosizeTrigger = oOptions.autosizeTrigger ? oOptions.autosizeTrigger : null,
				
			/**
			 * @param {boolean=} bIgnoreScrollableHeight
			 */
			fResize = function (bIgnoreScrollableHeight) {
				var iPadding = 0;

				if (App.browser.firefox)
				{
					iPadding = parseInt(jqEl.css('padding-top'), 10) * 2;
				}

				if (iMaxHeight)
				{
					/* 0-timeout to get the already changed text */
					setTimeout(function () {
						if (jqEl.prop('scrollHeight') < iMaxHeight)
						{
							jqEl.height(iMinHeight - iPaddingTB - iBorder);
							jqEl.height(jqEl.prop('scrollHeight') + iPadding - iPaddingTB);
						}
						else
						{
							jqEl.height(iMaxHeight - iPaddingTB - iBorder);
						}
					}, 100);
				}
				else if (bIgnoreScrollableHeight || jqEl.prop('scrollHeight') < iScrollableHeight)
				{
					setTimeout(function () {
						jqEl.height(iMinHeight - iPaddingTB - iBorder);
						jqEl.height(jqEl.prop('scrollHeight') + iPadding - iPaddingTB);
						//$('.calendar_event .scrollable_field').scrollTop(jqEl.height('scrollHeight'))
					}, 100);
				}
			}
		;

		jqEl.on('keydown', function(oEvent, oData) {
			fResize();
		});
		jqEl.on('paste', function(oEvent, oData) {
			fResize();
		});
		/*jqEl.on('input', function(oEvent, oData) {
			fResize();
		});
		ko.bindingHandlers.event.init(oElement, function () {
			return {
				'keydown': function (oData, oEvent) {
					fResize();
					return true;
				}
			};
		}, fAllBindingsAccessor, oViewModel);*/

		if (oAutosizeTrigger)
		{
			oAutosizeTrigger.subscribe(function (arg) {
				fResize(arg);
			}, this);
		}

		fResize();
	}
};

ko.bindingHandlers.customBind = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {

		var
			oOptions = fValueAccessor(),
			oKeydown = oOptions.onKeydown ? oOptions.onKeydown : null,
			oKeyup = oOptions.onKeyup ? oOptions.onKeyup : null,
			oPaste = oOptions.onPaste ? oOptions.onPaste : null,
			oInput = oOptions.onInput ? oOptions.onInput : null,
			oValueObserver = oOptions.valueObserver ? oOptions.valueObserver : null
		;

		ko.bindingHandlers.event.init(oElement, function () {
			return {
				'keydown': function (oData, oEvent) {
					if(oKeydown)
					{
						oKeydown.call(this, oElement, oEvent, oValueObserver);
					}
					return true;
				},
				'keyup': function (oData, oEvent) {
					if(oKeyup)
					{
						oKeyup.call(this, oElement, oEvent, oValueObserver);
					}
					return true;
				},
				'paste': function (oData, oEvent) {
					if(oPaste)
					{
						oPaste.call(this, oElement, oEvent, oValueObserver);
					}
					return true;
				},
				'input': function (oData, oEvent) {
					if(oInput)
					{
						oInput.call(this, oElement, oEvent, oValueObserver);
					}
					return true;
				}
			};
		}, fAllBindingsAccessor, oViewModel);
	}
};

ko.bindingHandlers.fade = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {

		var jqEl = $(oElement),
			jqElFaded = $('<span class="faded"></span>'),
			oOptions = _.defaults(
				fValueAccessor(), {
					'color': null,
					'css': 'fadeout'
				}
			),
			oColor = oOptions.color,
			sCss = oOptions.css,
			updateColor = function (sColor)
			{
				if (sColor === '') {
					return;
				}

				var
					oHex2Rgb = hex2Rgb(sColor),
					sRGBColor = "rgba(" + oHex2Rgb.r + "," + oHex2Rgb.g + "," + oHex2Rgb.b
				;

				colorIt(sColor, sRGBColor);
			},
			hex2Rgb = function (sHex) {
				// Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
				var
					shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i,
					result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(sHex)
				;
				sHex = sHex.replace(shorthandRegex, function(m, r, g, b) {
					return r + r + g + g + b + b;
				});

				return result ? {
					r: parseInt(result[1], 16),
					g: parseInt(result[2], 16),
					b: parseInt(result[3], 16)
				} : null;
			},
			colorIt = function (hex, rgb) {
				if (Utils.isRTL())
				{
					jqElFaded
						.css("filter", "progid:DXImageTransform.Microsoft.gradient(startColorstr='" + hex + "', endColorstr='" + hex + "',GradientType=1 )")
						.css("background-image", "-webkit-gradient(linear, left top, right top, color-stop(0%," + rgb + ",1)" + "), color-stop(100%," + rgb + ",0)" + "))")
						.css("background-image", "-moz-linear-gradient(left, " + rgb + ",1)" + "0%, " + rgb + ",0)" + "100%)")
						.css("background-image", "-webkit-linear-gradient(left, " + rgb + "1)" + "0%," + rgb + ",0)" + "100%)")
						.css("background-image", "-o-linear-gradient(left, " + rgb + ",1)" + "0%," + rgb + ",0)" + "100%)")
						.css("background-image", "-ms-linear-gradient(left, " + rgb + ",1)" + "0%," + rgb + ",0)" + "100%)")
						.css("background-image", "linear-gradient(left, " + rgb + ",1)" + "0%," + rgb + ",0)" + "100%)");
				}
				else
				{
					jqElFaded
						.css("filter", "progid:DXImageTransform.Microsoft.gradient(startColorstr='" + hex + "', endColorstr='" + hex + "',GradientType=1 )")
						.css("background-image", "-webkit-gradient(linear, left top, right top, color-stop(0%," + rgb + ",0)" + "), color-stop(100%," + rgb + ",1)" + "))")
						.css("background-image", "-moz-linear-gradient(left, " + rgb + ",0)" + "0%, " + rgb + ",1)" + "100%)")
						.css("background-image", "-webkit-linear-gradient(left, " + rgb + ",0)" + "0%," + rgb + ",1)" + "100%)")
						.css("background-image", "-o-linear-gradient(left, " + rgb + ",0)" + "0%," + rgb + ",1)" + "100%)")
						.css("background-image", "-ms-linear-gradient(left, " + rgb + ",0)" + "0%," + rgb + ",1)" + "100%)")
						.css("background-image", "linear-gradient(left, " + rgb + ",0)" + "0%," + rgb + ",1)" + "100%)");
				}
			}
		;

		jqEl.parent().addClass(sCss);
		jqEl.after(jqElFaded);

		if (oOptions.color.subscribe !== undefined)
		{
			updateColor(oColor());
			oColor.subscribe(function (sColor) {
				updateColor(sColor);
			}, this);
		}
	}
};

ko.bindingHandlers.highlighter = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {

		var
			jqEl = $(oElement),
			oOptions = fValueAccessor(),
			oValueObserver = oOptions.valueObserver ? oOptions.valueObserver : null,
			oHighlighterValueObserver = oOptions.highlighterValueObserver ? oOptions.highlighterValueObserver : null,
			oHighlightTrigger = oOptions.highlightTrigger ? oOptions.highlightTrigger : null,
			aHighlightWords = ['from:', 'to:', 'subject:', 'text:', 'email:', 'has:', 'date:', 'text:', 'body:'],
			rPattern = (function () {
				var sPatt = '';
				$.each(aHighlightWords, function(i, oEl) {
					sPatt = (!i) ? (sPatt + '\\b' + oEl) : (sPatt + '|\\b' + oEl);
				});

				return new RegExp('(' + sPatt + ')', 'g');
			}()),
			fClear = function (sStr) {
				return sStr.replace(/\xC2\xA0/g, ' ').replace(/\xA0/g, ' ').replace(/[\s]+/g, ' ');
			},
			iPrevKeyCode = -1,
			sUserLanguage = window.navigator.language || window.navigator.userLanguage,
			aTabooLang = ['zh', 'zh-TW', 'zh-CN', 'zh-HK', 'zh-SG', 'zh-MO', 'ja', 'ja-JP', 'ko', 'ko-KR', 'vi', 'vi-VN', 'th', 'th-TH'],// , 'ru', 'ru-RU'
			bHighlight = !_.include(aTabooLang, sUserLanguage)
		;

		ko.bindingHandlers.event.init(oElement, function () {
			return {
				'keydown': function (oData, oEvent) {
					return oEvent.keyCode !== Enums.Key.Enter;
				},
				'keyup': function (oData, oEvent) {
					var
						aMoveKeys = [Enums.Key.Left, Enums.Key.Right, Enums.Key.Home, Enums.Key.End],
						bMoveKeys = -1 !== Utils.inArray(oEvent.keyCode, aMoveKeys)
					;

					if (!(
//							oEvent.keyCode === Enums.Key.Enter					||
							oEvent.keyCode === Enums.Key.Shift					||
							oEvent.keyCode === Enums.Key.Ctrl					||
							// for international english -------------------------
							oEvent.keyCode === Enums.Key.Dash					||
							oEvent.keyCode === Enums.Key.Apostrophe				||
							oEvent.keyCode === Enums.Key.Six && oEvent.shiftKey	||
							// ---------------------------------------------------
							bMoveKeys											||
//							((oEvent.shiftKey || iPrevKeyCode === Enums.Key.Shift) && bMoveKeys) ||
							((oEvent.ctrlKey || iPrevKeyCode === Enums.Key.Ctrl) && oEvent.keyCode === Enums.Key.a)
						))
					{
						oValueObserver(fClear(jqEl.text()));
						highlight(false);
					}
					iPrevKeyCode = oEvent.keyCode;
					return true;
				},
				// firefox fix for html paste
				'paste': function (oData, oEvent) {
					setTimeout(function () {
						oValueObserver(fClear(jqEl.text()));
						highlight(false);
					}, 0);
					return true;
				}
			};
		}, fAllBindingsAccessor, oViewModel);

		// highlight on init
		setTimeout(function () {
			highlight(true);
		}, 0);

		function highlight(bNotRestoreSel) {
			if(bHighlight)
			{
				var
					iCaretPos = 0,
					sContent = jqEl.text(),
					aContent = sContent.split(rPattern),
					aDividedContent = [],
					sReplaceWith = '<span class="search_highlight"' + '>$&</span>'
				;

				$.each(aContent, function (i, sEl) {
					if (_.any(aHighlightWords, function (oAnyEl) {return oAnyEl === sEl;}))
					{
						$.each(sEl, function (i, sElem) {
							aDividedContent.push($(sElem.replace(/(.)/, sReplaceWith)));
						});
					}
					else
					{
						$.each(sEl, function(i, sElem) {
							if(sElem === ' ')
							{
								// space fix for firefox
								aDividedContent.push(document.createTextNode('\u00A0'));
							}
							else
							{
								aDividedContent.push(document.createTextNode(sElem));
							}
						});
					}
				});

				if (bNotRestoreSel)
				{
					jqEl.empty().append(aDividedContent);
				}
				else
				{
					iCaretPos = getCaretOffset();
					jqEl.empty().append(aDividedContent);
					setCursor(iCaretPos);
				}
			}
		}

		function getCaretOffset() {
			var
				caretOffset = 0,
				range,
				preCaretRange,
				textRange,
				preCaretTextRange
				;

			if (typeof window.getSelection !== "undefined")
			{
				range = window.getSelection().getRangeAt(0);
				preCaretRange = range.cloneRange();
				preCaretRange.selectNodeContents(oElement);
				preCaretRange.setEnd(range.endContainer, range.endOffset);
				caretOffset = preCaretRange.toString().length;
			}
			else if (typeof document.selection !== "undefined" && document.selection.type !== "Control")
			{
				textRange = document.selection.createRange();
				preCaretTextRange = document.body.createTextRange();
				preCaretTextRange.moveToElementText(oElement);
				preCaretTextRange.setEndPoint("EndToEnd", textRange);
				caretOffset = preCaretTextRange.text.length;
			}

			return caretOffset;
		}

		function setCursor(iCaretPos) {
			var
				range,
				selection,
				textRange
				;

			if (!oElement)
			{
				return false;
			}
			else if(document.createRange)
			{
				range = document.createRange();
				range.selectNodeContents(oElement);
				range.setStart(oElement, iCaretPos);
				range.setEnd(oElement, iCaretPos);
				selection = window.getSelection();
				selection.removeAllRanges();
				selection.addRange(range);
			}
			else if(oElement.createTextRange)
			{
				textRange = oElement.createTextRange();
				textRange.collapse(true);
				textRange.moveEnd(iCaretPos);
				textRange.moveStart(iCaretPos);
				textRange.select();
				return true;
			}
			else if(oElement.setSelectionRange)
			{
				oElement.setSelectionRange(iCaretPos, iCaretPos);
				return true;
			}

			return false;
		}

		oHighlightTrigger.notifySubscribers();

		oHighlightTrigger.subscribe(function (bNotRestoreSel) {
			setTimeout(function () {
				highlight(!!bNotRestoreSel);
			}, 0);
		}, this);

		oHighlighterValueObserver.subscribe(function () {
			jqEl.text(oValueObserver());
		}, this);
	}
};

ko.bindingHandlers.quoteText = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {

		var
			jqEl = $(oElement),
			jqButton = $('<span class="button_quote">' + Utils.i18n('HELPDESK/BUTTON_QUOTE') + '</span>'),
			oOptions = fValueAccessor(),
			fActionHandler = oOptions.actionHandler,
			bIsQuoteArea = false,
			oSelection = null,
			sText = ''
		;

		$('#pSevenContent').append(jqButton);

		$(document.body).on('click', function(oEvent) {

			bIsQuoteArea = !!(($(oEvent.target)).parents('.posts')[0]);
			if (document.getSelection)
			{
				oSelection = document.getSelection();
				if (oSelection)
				{
					sText = oSelection.toString();
				}
			}
			else
			{
				sText = document.selection.createRange().text;
			}

			if(bIsQuoteArea)
			{
				if(sText.replace(/[\n\r\s]/, '') !== '') //replace - for dbl click on empty area
				{
					jqButton.css({
						'top': oEvent.clientY + 20, //20 - custom indent
						'left': oEvent.clientX + 20
					}).show();
				}
				else
				{
					jqButton.hide();
				}
			}
			else
			{
				jqButton.hide();
			}
		});

		jqButton.on('click', function(oEvent) {
			fActionHandler.call(oViewModel, sText);
		});
	}
};

ko.bindingHandlers.adjustHeightToContent = {
	'init': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {

		var
			jqEl = $(oElement),
			jqTargetEl = null,
			jqParentEl = null,
			jqNearEl = null
		;

		_.delay(_.bind(function(){
			jqTargetEl = $(_.max(jqEl.find('.title .text'), function(domEl){
				return domEl.offsetWidth;
			}));

			jqParentEl = jqTargetEl.parent();
			jqNearEl = jqParentEl.find('.icon');

			jqEl.css('min-width',
				parseInt(jqParentEl.css("margin-left")) +
				parseInt(jqParentEl.css("padding-left")) +
				parseInt(jqNearEl.width()) +
				parseInt(jqNearEl.css("margin-left")) +
				parseInt(jqNearEl.css("margin-right")) +
				parseInt(jqNearEl.css("padding-left")) +
				parseInt(jqNearEl.css("padding-right")) +
				parseInt(jqTargetEl.width()) +
				parseInt(jqTargetEl.css("margin-left")) +
				parseInt(jqTargetEl.css("padding-left")) +
				10
			);
		},this), 1);
	}
};

ko.bindingHandlers.customTooltip = {
	'init': (bMobileDevice || bMobileApp) ? null : function (oElement, fValueAccessor) {
		var
			sTooltipText = Utils.i18n(fValueAccessor()),
			$Element = $(oElement),
			$Dropdown = $Element.find('span.dropdown'),
			bShown = false,
			fMouseIn = function () {
				var $ItemToAlign = $(this);
				if (!$ItemToAlign.hasClass('expand'))
				{
					clearTimeout(Utils.CustomTooltip.iHideTimer);
					bShown = true;
					clearTimeout(Utils.CustomTooltip.iTimer);
					Utils.CustomTooltip.iTimer = setTimeout(function () {
						if (bShown)
						{
							if ($ItemToAlign.hasClass('expand'))
							{
								bShown = false;
								clearTimeout(Utils.CustomTooltip.iTimer);
								Utils.CustomTooltip.hide();
							}
							else
							{
								Utils.CustomTooltip.show(sTooltipText, $ItemToAlign);
							}
						}
					}, 100);
				}
			},
			fMouseOut = function () {
				clearTimeout(Utils.CustomTooltip.iHideTimer);
				Utils.CustomTooltip.iHideTimer = setTimeout(function () {
					bShown = false;
					clearTimeout(Utils.CustomTooltip.iTimer);
					Utils.CustomTooltip.hide();
				}, 10);
			},
			fEmpty = function () {},
			fBindEvents = function () {
				$Element.unbind('mouseover', fMouseIn);
				$Element.unbind('mouseout', fMouseOut);
				$Element.unbind('click', fMouseOut);
				$Dropdown.unbind('mouseover', fMouseOut);
				$Dropdown.unbind('mouseout', fEmpty);
				if (sTooltipText !== '')
				{
					$Element.bind('mouseover', fMouseIn);
					$Element.bind('mouseout', fMouseOut);
					$Element.bind('click', fMouseOut);
					$Dropdown.bind('mouseover', fMouseOut);
					$Dropdown.bind('mouseout', fEmpty);
				}
			},
			fSubscribtion = null
		;
		
		if (typeof sTooltipText === 'function')
		{
			sTooltipText = sTooltipText();
		}
		
		fBindEvents();
		
		if (typeof fValueAccessor().subscribe === 'function' && fSubscribtion === null)
		{
			fSubscribtion = fValueAccessor().subscribe(function (sValue) {
				sTooltipText = sValue;
				fBindEvents();
			});
		}
	}
};

ko.bindingHandlers.foreachprop = {
	transformObject: function (obj) {
		var properties = [];
		
		for (var key in obj) {
			if (obj.hasOwnProperty(key)) {
				properties.push({ key: key, value: obj[key] });
			}
		}
		return properties;
	},
	init: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
		var
			value = ko.utils.unwrapObservable(valueAccessor()),
			properties = ko.bindingHandlers.foreachprop.transformObject(value)
		;
		
		ko.applyBindingsToNode(element, { foreach: properties }, bindingContext);
		return { controlsDescendantBindings: true };
	}
};


/**
 * @constructor
 */
function CRouting()
{
	this.defaultScreen = Enums.Screens.Mailbox;
	this.currentScreen = Enums.Screens.Mailbox;
	this.lastMailboxHash = ko.observable(Enums.Screens.Mailbox);
	this.lastHelpdeskHash = ko.observable(Enums.Screens.Helpdesk);
	this.lastSettingsHash = ko.observable(Enums.Screens.Settings);

	this.currentHash = ko.observable('');
	this.previousHash = ko.observable('');
}

/**
 * Initializes object.
 * 
 * @param {string} sDefaultScreen
 */
CRouting.prototype.init = function (sDefaultScreen)
{
	this.defaultScreen = sDefaultScreen;
	hasher.initialized.removeAll();
	hasher.changed.removeAll();
	hasher.initialized.add(this.parseRouting, this);
	hasher.changed.add(this.parseRouting, this);
	hasher.init();
	hasher.initialized.removeAll();
};

/**
 * Finalizes the object and puts an empty hash.
 */
CRouting.prototype.finalize = function ()
{
	hasher.dispose();
};

/**
 * Sets a new hash.
 * 
 * @param {string} sNewHash
 * 
 * @return {boolean}
 */
CRouting.prototype.setHashFromString = function (sNewHash)
{
	var bSame = (location.hash === decodeURIComponent(sNewHash));
	
	if (!bSame)
	{
		location.hash = sNewHash;
	}
	
	return bSame;
};

/**
 * Sets a new hash without part.
 * 
 * @param {string} sUid
 */
CRouting.prototype.replaceHashWithoutMessageUid = function (sUid)
{
	if (typeof sUid === 'string' && sUid !== '')
	{
		var sNewHash = location.hash.replace('/msg' + sUid, '');
		this.replaceHashFromString(sNewHash);
	}
};

/**
 * Sets a new hash.
 * 
 * @param {string} sNewHash
 */
CRouting.prototype.replaceHashFromString = function (sNewHash)
{
	if (location.hash !== sNewHash)
	{
		location.replace(sNewHash);
	}
};

/**
 * Sets a new hash made ​​up of an array.
 * 
 * @param {Array} aRoutingParts
 * 
 * @return boolean
 */
CRouting.prototype.setHash = function (aRoutingParts)
{
	return this.setHashFromString(this.buildHashFromArray(aRoutingParts));
};

/**
 * @param {Array} aRoutingParts
 */
CRouting.prototype.replaceHash = function (aRoutingParts)
{
	this.replaceHashFromString(this.buildHashFromArray(aRoutingParts));
};

/**
 * @param {Array} aRoutingParts
 */
CRouting.prototype.replaceHashDirectly = function (aRoutingParts)
{
	hasher.stop();
	this.replaceHashFromString(this.buildHashFromArray(aRoutingParts));
	hasher.init();
};

CRouting.prototype.setPreviousHash = function ()
{
	location.hash = this.previousHash();
};

/**
 * Makes a hash of a string array.
 *
 * @param {(string|Array)} aRoutingParts
 * 
 * @return {string}
 */
CRouting.prototype.buildHashFromArray = function (aRoutingParts)
{
	var
		iIndex = 0,
		iLen = 0,
		sHash = ''
	;

	if (_.isArray(aRoutingParts))
	{
		for (iLen = aRoutingParts.length; iIndex < iLen; iIndex++)
		{
			aRoutingParts[iIndex] = encodeURIComponent(aRoutingParts[iIndex]);
		}
	}
	else
	{
		aRoutingParts = [encodeURIComponent(aRoutingParts.toString())];
	}
	
	sHash = aRoutingParts.join('/');
	
	if (sHash !== '')
	{
		sHash = '#' + sHash;
	}

	return sHash;
};

/**
 * Returns the value of the hash string of location.href.
 * location.hash returns the decoded string and location.href - not, so it uses location.href.
 * 
 * @return {string}
 */
CRouting.prototype.getHashFromHref = function ()
{
	var
		iPos = location.href.indexOf('#'),
		sHash = ''
	;

	if (iPos !== -1)
	{
		sHash = location.href.substr(iPos + 1);
	}

	return sHash;
};

CRouting.prototype.isSingleMode = function ()
{
	var
		sScreen = this.getScreenFromHash(),
		bSingleMode = (sScreen === Enums.Screens.SingleMessageView || sScreen === Enums.Screens.SingleCompose || 
			sScreen === Enums.Screens.SingleHelpdesk)
	;
	
	this.currentScreen = sScreen;
	
	return bSingleMode;
};

/**
 * @param {Array} aRoutingParts
 * @param {Array} aAddParams
 */
CRouting.prototype.goDirectly = function (aRoutingParts, aAddParams)
{
	hasher.stop();
	this.setHash(aRoutingParts);
	this.parseRouting(aAddParams);
	hasher.init();
};

/**
 * @param {string} sNeedScreen
 */
CRouting.prototype.historyBackWithoutParsing = function (sNeedScreen)
{
	hasher.stop();
	location.hash = this.currentHash();
	hasher.init();
};

/**
 * @returns {String}
 */
CRouting.prototype.getScreenFromHash = function ()
{
	var
		sHash = this.getHashFromHref(),
		aHash = sHash.split('/')
	;
	return decodeURIComponent(aHash.shift()) || this.defaultScreen;
};

/**
 * @param {Array} aAddParams
 */
CRouting.prototype.parseRouting = function (aAddParams)
{
	var
		oCurrentModel = App.Screens.getCurrentScreenModel(),
		fContinueScreenChanging = _.bind(this.chooseScreen, this, aAddParams)
	;
	
	if (oCurrentModel && Utils.isFunc(oCurrentModel.beforeHide))
	{
		oCurrentModel.beforeHide(fContinueScreenChanging);
	}
	else
	{
		fContinueScreenChanging();
	}
};

/**
 * Parses the hash string and opens the corresponding routing screen.
 * 
 * @param {Array} aAddParams
 */
CRouting.prototype.chooseScreen = function (aAddParams)
{
	var
		sHash = this.getHashFromHref(),
		aHash = sHash.split('/'),
		sScreen = decodeURIComponent(aHash.shift()) || this.defaultScreen,
		bScreenInEnum = !!_.find(Enums.Screens, function (sScreenInEnum) {
			return sScreenInEnum === sScreen;
		}),
		iIndex = 0,
		iLen = aHash.length
	;

	if (sScreen === Enums.Screens.Mailbox)
	{
		this.lastMailboxHash(sHash);
	}
	if (sScreen === Enums.Screens.Helpdesk)
	{
		this.lastHelpdeskHash(sHash);
	}
	if (sScreen === Enums.Screens.Settings)
	{
		this.lastSettingsHash(sHash);
	}
	this.previousHash(this.currentHash());
	this.currentHash(sHash);
	
	for (; iIndex < iLen; iIndex++)
	{
		aHash[iIndex] = decodeURIComponent(aHash[iIndex]);
	}
	
	if ($.isArray(aAddParams))
	{
		aHash = _.union(aHash, aAddParams);
	}
	
	this.currentScreen = sScreen;
	
	switch (sScreen)
	{
		case Enums.Screens.SingleMessageView:
		case Enums.Screens.SingleCompose:
		case Enums.Screens.SingleHelpdesk:
			AppData.SingleMode = true;
			App.Screens.showCurrentScreen(sScreen, aHash);
			break;
		default:
			if (!bScreenInEnum)
			{
				sScreen = this.defaultScreen;
			}
			AppData.SingleMode = false;
			App.Screens.showNormalScreen(Enums.Screens.Header);
			App.Screens.showCurrentScreen(sScreen, aHash);
			break;
		case Enums.Screens.Mailbox:
			AppData.SingleMode = false;
			App.Screens.showNormalScreen(Enums.Screens.Header);
			App.Screens.showCurrentScreen(Enums.Screens.Mailbox, aHash);
			break;
	}
};


/**
 * @constructor
 */
function CLinkBuilder()
{
}

/**
 * @param {string=} sFolder = 'INBOX'
 * @param {number=} iPage = 1
 * @param {string=} sUid = ''
 * @param {string=} sSearch = ''
 * @param {string=} sFilters = ''
 * @return {Array}
 */
CLinkBuilder.prototype.mailbox = function (sFolder, iPage, sUid, sSearch, sFilters)
{
	var	aResult = [Enums.Screens.Mailbox];
	
	iPage = Utils.isNormal(iPage) ? Utils.pInt(iPage) : 1;
	sUid = Utils.isNormal(sUid) ? Utils.pString(sUid) : '';
	sSearch = Utils.isNormal(sSearch) ? Utils.pString(sSearch) : '';
	sFilters = Utils.isNormal(sFilters) ? Utils.pString(sFilters) : '';

	if (sFolder && '' !== sFolder)
	{
		aResult.push(sFolder);
	}
	
	if (sFilters && '' !== sFilters)
	{
		aResult.push('filter:' + sFilters);
	}
	
	if (1 < iPage)
	{
		aResult.push('p' + iPage);
	}

	if (sUid && '' !== sUid)
	{
		aResult.push('msg' + sUid);
	}

	if (sSearch && '' !== sSearch)
	{
		aResult.push(sSearch);
	}
	
	return aResult;
};

/**
 * @return {Array}
 */
CLinkBuilder.prototype.inbox = function ()
{
	return this.mailbox();
};

/**
 * @param {Array} aParams
 * 
 * @return {Object}
 */
CLinkBuilder.prototype.parseMailbox = function (aParams)
{
	var
		sFolder = 'INBOX',
		iPage = 1,
		sUid = '',
		sSearch = '',
		sFilters = '',
		sTemp = '',
		iIndex = 0
	;
	
	if (Utils.isNonEmptyArray(aParams))
	{
		sFolder = Utils.pString(aParams[iIndex]);
		iIndex++;

		if (aParams.length > iIndex)
		{
			sTemp = Utils.pString(aParams[iIndex]);
			if (sTemp === 'filter:' + Enums.FolderFilter.Flagged)
			{
				sFilters = Enums.FolderFilter.Flagged;
				iIndex++;
			}
			if (sTemp === 'filter:' + Enums.FolderFilter.Unseen)
			{
				sFilters = Enums.FolderFilter.Unseen;
				iIndex++;
			}
		}

		if (aParams.length > iIndex)
		{
			sTemp = Utils.pString(aParams[iIndex]);
			if (this.isPageParam(sTemp))
			{
				iPage = Utils.pInt(sTemp.substr(1));
				if (iPage <= 0)
				{
					iPage = 1;
				}
				iIndex++;
			}
		}
		
		if (aParams.length > iIndex)
		{
			sTemp = Utils.pString(aParams[iIndex]);
			if (this.isMsgParam(sTemp))
			{
				sUid = sTemp.substr(3);
				iIndex++;
			}
		}

		if (aParams.length > iIndex)
		{
			sSearch = Utils.pString(aParams[iIndex]);
		}
	}
	
	return {
		'Folder': sFolder,
		'Page': iPage,
		'Uid': sUid,
		'Search': sSearch,
		'Filters': sFilters
	};
};

/**
 * @param {number=} iType
 * @param {string=} sGroupId
 * @param {string=} sSearch
 * @param {number=} iPage
 * @param {string=} sUid
 * @returns {Array}
 */
CLinkBuilder.prototype.contacts = function (iType, sGroupId, sSearch, iPage, sUid)
{
	var
		aParams = [Enums.Screens.Contacts]
	;
	
	if (typeof iType === 'number')
	{
		aParams.push(iType);
	}
	
	if (sGroupId && sGroupId !== '')
	{
		aParams.push(sGroupId);
	}
	
	if (sSearch && sSearch !== '')
	{
		aParams.push(sSearch);
	}
	
	if (Utils.isNumeric(iPage))
	{
		aParams.push('p' + iPage);
	}
	
	if (sUid && sUid !== '')
	{
		aParams.push('cnt' + sUid);
	}
	
	return aParams;
};

/**
 * @param {Array} aParam
 * 
 * @return {Object}
 */
CLinkBuilder.prototype.parseContacts = function (aParam)
{
	var
		iIndex = 0,
		aGroupTypes = [Enums.ContactsGroupListType.Personal, Enums.ContactsGroupListType.SharedToAll, Enums.ContactsGroupListType.Global, Enums.ContactsGroupListType.All],
		iType = Enums.ContactsGroupListType.All,
		sGroupId = '',
		sSearch = '',
		iPage = 1,
		sUid = ''
	;

	if (Utils.isNonEmptyArray(aParam))
	{
		iType = Utils.pInt(aParam[iIndex]);
		iIndex++;
		if (-1 === Utils.inArray(iType, aGroupTypes))
		{
			iType = Enums.ContactsGroupListType.SubGroup;
		}
		if (iType === Enums.ContactsGroupListType.SubGroup)
		{
			if (aParam.length > iIndex)
			{
				sGroupId = Utils.pString(aParam[iIndex]);
				iIndex++;
			}
			else
			{
				iType = Enums.ContactsGroupListType.Personal;
			}
		}
		
		if (aParam.length > iIndex && !this.isPageParam(aParam[iIndex]) && !this.isContactParam(aParam[iIndex]))
		{
			sSearch = Utils.pString(aParam[iIndex]);
			iIndex++;
		}
		
		if (aParam.length > iIndex && this.isPageParam(aParam[iIndex]))
		{
			iPage = Utils.pInt(aParam[iIndex].substr(1));
			iIndex++;
			if (iPage <= 0)
			{
				iPage = 1;
			}
		}
		
		if (aParam.length > iIndex && this.isContactParam(aParam[iIndex]))
		{
			sUid = Utils.pString(aParam[iIndex].substr(3));
			iIndex++;
		}
	}
	
	return {
		'Type': iType,
		'GroupId': sGroupId,
		'Search': sSearch,
		'Page': iPage,
		'Uid': sUid
	};
};

/**
 * @param {string} sTemp
 * 
 * @return {boolean}
 */
CLinkBuilder.prototype.isPageParam = function (sTemp)
{
	return ('p' === sTemp.substr(0, 1) && (/^[1-9][\d]*$/).test(sTemp.substr(1)));
};

/**
 * @param {string} sTemp
 * 
 * @return {boolean}
 */
CLinkBuilder.prototype.isContactParam = function (sTemp)
{
	return ('cnt' === sTemp.substr(0, 3) && (/^[1-9][\d]*$/).test(sTemp.substr(3)));
};

/**
 * @param {string} sTemp
 * 
 * @return {boolean}
 */
CLinkBuilder.prototype.isMsgParam = function (sTemp)
{
	return ('msg' === sTemp.substr(0, 3) && (/^[1-9][\d]*$/).test(sTemp.substr(3)));
};

/**
 * @return {Array}
 */
CLinkBuilder.prototype.compose = function ()
{
	var sScreen = (AppData.SingleMode) ? Enums.Screens.SingleCompose : Enums.Screens.Compose;
	
	return [sScreen];
};

/**
 * @param {string} sType
 * @param {string} sFolder
 * @param {string} sUid
 * @param {boolean} bSingleMode
 * 
 * @return {Array}
 */
CLinkBuilder.prototype.composeFromMessage = function (sType, sFolder, sUid, bSingleMode)
{
	var sScreen = (bSingleMode || AppData.SingleMode) ? Enums.Screens.SingleCompose : Enums.Screens.Compose;
	
	return [sScreen, sType, sFolder, sUid];
};

/**
 * @param {string} sTo
 * 
 * @return {Array}
 */
CLinkBuilder.prototype.composeWithToField = function (sTo)
{
	var sScreen = (AppData.SingleMode) ? Enums.Screens.SingleCompose : Enums.Screens.Compose;
	
	return [sScreen, 'to', sTo];
};

/**
 * @param {?} mToAddr
 * @returns {Object}
 */
CLinkBuilder.prototype.parseToAddr = function (mToAddr)
{
	var
		sToAddr = decodeURI(Utils.pString(mToAddr)),
		bHasMailTo = sToAddr.indexOf('mailto:') !== -1,
		aMailto = [],
		aMessageParts = [],
		sSubject = '',
		sCcAddr = '',
		sBccAddr = '',
		sBody = ''
	;
	
	if (bHasMailTo)
	{
		aMailto = sToAddr.replace(/^mailto:/, '').split('?');
		sToAddr = aMailto[0];
		if (aMailto.length === 2)
		{
			aMessageParts = aMailto[1].split('&');
			_.each(aMessageParts, function (sPart) {
				var
					aParts = sPart.split('=')
				;
				if (aParts.length === 2)
				{
					switch (aParts[0])
					{
						case 'subject': sSubject = aParts[1]; break;
						case 'cc': sCcAddr = aParts[1]; break;
						case 'bcc': sBccAddr = aParts[1]; break;
						case 'body': sBody = aParts[1]; break;
	}
				}
			});
		}
	}
	
	return {
		'to': sToAddr,
		'hasMailto': bHasMailTo,
		'subject': sSubject,
		'cc': sCcAddr,
		'bcc': sBccAddr,
		'body': sBody
	};
};


/**
 * @type {Function}
 */
Utils.inArray = $.inArray;

/**
 * @type {Function}
 */
Utils.isFunc = $.isFunction;

/**
 * @type {Function}
 */
Utils.trim = $.trim;

/**
 * @type {Function}
 */
Utils.emptyFunction = function () {};

/**
 * @param {*} mValue
 * 
 * @return {boolean}
 */
Utils.isUnd = function (mValue)
{
	return undefined === mValue;
};

/**
 * @param {*} oValue
 * 
 * @return {boolean}
 */
Utils.isNull = function (oValue)
{
	return null === oValue;
};

/**
 * @param {*} oValue
 * 
 * @return {boolean}
 */
Utils.isNormal = function (oValue)
{
	return !Utils.isUnd(oValue) && !Utils.isNull(oValue);
};

/**
 * @param {(string|number)} mValue
 * 
 * @return {boolean}
 */
Utils.isNumeric = function (mValue)
{
	return Utils.isNormal(mValue) ? (/^[1-9]+[0-9]*$/).test(mValue.toString()) : false;
};

/**
 * @param {*} mValue
 * 
 * @return {number}
 */
Utils.pInt = function (mValue)
{
	var iValue = window.parseInt(mValue, 10);
	if (isNaN(iValue))
	{
		iValue = 0;
	}
	return iValue;
};

/**
 * @param {*} mValue
 * 
 * @return {string}
 */
Utils.pString = function (mValue)
{
	return Utils.isNormal(mValue) ? mValue.toString() : '';
};

/**
 * @param {*} aValue
 * @param {number=} iArrayLen
 * 
 * @return {boolean}
 */
Utils.isNonEmptyArray = function (aValue, iArrayLen)
{
	iArrayLen = iArrayLen || 1;
	
	return _.isArray(aValue) && iArrayLen <= aValue.length;
};

/**
 * @param {Object} oObject
 * @param {string} sName
 * @param {*} mValue
 */
Utils.pImport = function (oObject, sName, mValue)
{
	oObject[sName] = mValue;
};

/**
 * @param {Object} oObject
 * @param {string} sName
 * @param {*} mDefault
 * @return {*}
 */
Utils.pExport = function (oObject, sName, mDefault)
{
	return Utils.isUnd(oObject[sName]) ? mDefault : oObject[sName];
};

/**
 * @param {string} sText
 * 
 * @return {string}
 */
Utils.encodeHtml = function (sText)
{
	return (sText) ? sText.toString()
		.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;').replace(/'/g, '&#039;') : '';
};

/**
 * @param {string} sKey
 * @param {?Object=} oValueList
 * @param {?string=} sDefaultValue
 * @param {number=} nPluralCount
 * 
 * @return {string}
 */
Utils.i18n = function (sKey, oValueList, sDefaultValue, nPluralCount) {

	var
		sValueName = '',
		sResult = Utils.isUnd(I18n[sKey]) ? (Utils.isNormal(sDefaultValue) ? sDefaultValue : sKey) : I18n[sKey]
	;

	if (!Utils.isUnd(nPluralCount))
	{
		sResult = (function (nPluralCount, sResult) {
			var
				nPlural = Utils.getPlural(AppData.User.DefaultLanguage, nPluralCount),
				aPluralParts = sResult.split('|')
			;

			return (aPluralParts && aPluralParts[nPlural]) ? aPluralParts[nPlural] : (
				aPluralParts && aPluralParts[0] ? aPluralParts[0] : sResult);

		}(nPluralCount, sResult));
	}

	if (Utils.isNormal(oValueList))
	{
		for (sValueName in oValueList)
		{
			if (oValueList.hasOwnProperty(sValueName))
			{
				var reg = new RegExp('%' + sValueName + '%', 'g');
				sResult = sResult.replace(reg, oValueList[sValueName]);
			}
		}
	}

	return sResult;
};

/**
 * @param {number} iNum
 * @param {number} iDec
 * 
 * @return {number}
 */
Utils.roundNumber = function (iNum, iDec)
{
	return Math.round(iNum * Math.pow(10, iDec)) / Math.pow(10, iDec);
};

/**
 * @param {(number|string)} iSizeInBytes
 * 
 * @return {string}
 */
Utils.friendlySize = function (iSizeInBytes)
{
	var
		iBytesInKb = 1024,
		iBytesInMb = iBytesInKb * iBytesInKb,
		iBytesInGb = iBytesInKb * iBytesInKb * iBytesInKb
	;

	iSizeInBytes = Utils.pInt(iSizeInBytes);

	if (iSizeInBytes >= iBytesInGb)
	{
		return Utils.roundNumber(iSizeInBytes / iBytesInGb, 1) + Utils.i18n('MAIN/GIGABYTES');
	}
	else if (iSizeInBytes >= iBytesInMb)
	{
		return Utils.roundNumber(iSizeInBytes / iBytesInMb, 1) + Utils.i18n('MAIN/MEGABYTES');
	}
	else if (iSizeInBytes >= iBytesInKb)
	{
		return Utils.roundNumber(iSizeInBytes / iBytesInKb, 0) + Utils.i18n('MAIN/KILOBYTES');
	}

	return iSizeInBytes + Utils.i18n('MAIN/BYTES');
};

Utils.timeOutAction = (function () {

	var oTimeOuts = {};

	return function (sAction, fFunction, iTimeOut) {
		if (Utils.isUnd(oTimeOuts[sAction]))
		{
			oTimeOuts[sAction] = 0;
		}

		window.clearTimeout(oTimeOuts[sAction]);
		oTimeOuts[sAction] = window.setTimeout(fFunction, iTimeOut);
	};
}());

Utils.$log = null;
Utils.aLog = [];

Utils.log = function ()
{
	if (!AppData || !AppData.ClientDebug || !App.browser || App.browser.ie9AndBelow)
	{
		return;
	}
	
	function fCensor(mKey, mValue) {
		if (typeof(mValue) === 'string' && mValue.length > 50)
		{
			return mValue.substring(0, 50);
		}

		return mValue;
	}
	
	var
		$log = Utils.$log || $('<div style="display: none;"></div>').appendTo('body'),
		aNewRow = []
	;
	
	_.each(arguments, function (mArg) {
		var sRowPart = typeof(mArg) === 'string' ? mArg : JSON.stringify(mArg, fCensor);
		if (aNewRow.length === 0)
		{
			sRowPart = ' *** ' + sRowPart + ' *** ';
		}
		aNewRow.push(sRowPart);
	});
	
	aNewRow.push(moment().format(' *** D MMMM, YYYY, HH:mm:ss *** '));
	
	Utils.$log = $log;
	
	if (Utils.aLog.length > 200)
	{
		Utils.aLog.shift();
	}
	
	Utils.aLog.push(Utils.encodeHtml(aNewRow.join(', ')));
	
	$log.html(Utils.aLog.join('<br /><br />'));
};

/**
 * @param {string} sAction
 * @param {Object} oData
 * 
 * @returns {Object}
 */
Utils.getAjaxDataForLog = function (sAction, oData)
{
	var oDataForLog = oData;
	
	if (oData && oData.Result)
	{
		switch (sAction)
		{
			case 'MessagesGetList':
			case 'MessagesGetListByUids':
				oDataForLog = {
					'Result': {
						'Uids': oData.Result.Uids,
						'UidNext': oData.Result.UidNext,
						'FolderHash': oData.Result.FolderHash,
						'MessageCount': oData.Result.MessageCount,
						'MessageUnseenCount': oData.Result.MessageUnseenCount,
						'MessageResultCount': oData.Result.MessageResultCount
					}
				};
				break;
			case 'MessageGet':
				oDataForLog = {
					'Result': {
						'Folder': oData.Result.Folder,
						'Uid': oData.Result.Uid,
						'Subject': oData.Result.Subject,
						'Size': oData.Result.Size,
						'TextSize': oData.Result.TextSize,
						'From': oData.Result.From,
						'To': oData.Result.To
					}
				};
				break;
			case 'MessagesGetBodies':
				oDataForLog = {
					'Result': _.map(oData.Result, function (oMessage) {
						return {
							'Uid': oMessage.Uid,
							'Subject': oMessage.Subject
						};
					})
				};
				break;
		}
	}
	else if (oData)
	{
		oDataForLog = {
			'Result': oData.Result,
			'ErrorCode': oData.ErrorCode
		};
	}
	
	return oDataForLog;
};

/**
 * Gets link for contacts inport.
 *
 * @return {string}
 */
Utils.getImportContactsLink = function ()
{
	return '?/ImportContacts/';
};

/**
 * Gets link for contacts export.
 *
 * @param {string} $sSyncType
 * @return {string}
 */
Utils.getExportContactsLink = function ($sSyncType)
{
	return '?/Raw/Contacts' + $sSyncType + '/';
};

/**
 * Gets link for calendar export by hash.
 *
 * @param {number} iAccountId
 * @param {string} sHash
 * 
 * @return {string}
 */
Utils.getExportCalendarLinkByHash = function (iAccountId, sHash)
{
	return '?/Raw/Calendar/' + iAccountId + '/' + sHash;
};

/**
 * Gets link for download by hash.
 *
 * @param {number} iAccountId
 * @param {string} sHash
 * @param {boolean=} bIsExt = false
 * @param {string=} sTenatHash = ''
 * 
 * @return {string}
 */
Utils.getDownloadLinkByHash = function (iAccountId, sHash, bIsExt, sTenatHash)
{
	bIsExt = Utils.isUnd(bIsExt) ? false : !!bIsExt;
	sTenatHash = Utils.isUnd(sTenatHash) ? '' : sTenatHash;

	return '?/Raw/Download/' + iAccountId + '/' + sHash + '/' + (bIsExt ? '1' : '0') + ('' === sTenatHash ? '' : '/' + sTenatHash);
};

/**
 * Gets link for view by hash in iframe.
 *
 * @param {number} iAccountId
 * @param {string} sHash
 * @param {boolean=} bIsExt = false
 * @param {string=} sTenatHash = ''
 *
 * @return {string}
 */
Utils.getIframeLinkByHash = function (iAccountId, sHash, bIsExt, sTenatHash)
{
	bIsExt = Utils.isUnd(bIsExt) ? false : !!bIsExt;
	sTenatHash = Utils.isUnd(sTenatHash) ? '' : sTenatHash;

	return '?/Raw/Iframe/' + iAccountId + '/' + sHash + '/' + (bIsExt ? '1' : '0') + ('' === sTenatHash ? '' : '/' + sTenatHash);
};

/**
 * Gets link for view by hash in iframe.
 *
 * @param {number} iAccountId
 * @param {string} sUrl
 *
 * @return {string}
 */
Utils.getIframeWrappwer = function (iAccountId, sUrl)
{
	return '?/Raw/Iframe/' + iAccountId + '/' + window.encodeURIComponent(sUrl) + '/';
};

/**
 * Gets link for thumbnail by hash.
 *
 * @param {number} iAccountId
 * @param {string} sHash
 * @param {boolean=} bIsExt = false
 * @param {string=} sTenatHash = ''
 *
 * @return {string}
 */
Utils.getViewThumbnailLinkByHash = function (iAccountId, sHash, bIsExt, sTenatHash)
{
	bIsExt = Utils.isUnd(bIsExt) ? false : !!bIsExt;
	sTenatHash = Utils.isUnd(sTenatHash) ? '' : sTenatHash;
	
	return '?/Raw/Thumbnail/' + iAccountId + '/' + sHash + '/' + (bIsExt ? '1' : '0') + ('' === sTenatHash ? '' : '/' + sTenatHash);
};

/**
 * Gets link for download by hash.
 *
 * @param {number} iAccountId
 * @param {string} sHash
 * @param {string=} sPublicHash
 * 
 * @return {string}
 */
Utils.getFilestorageDownloadLinkByHash = function (iAccountId, sHash, sPublicHash)
{
	var sUrl = '?/Raw/FilesDownload/' + iAccountId + '/' + sHash;
	if (!Utils.isUnd(sPublicHash))
	{
		sUrl = sUrl + '/0/' + sPublicHash;
	}
	return sUrl;
};

/**
 * Gets link for download by hash.
 *
 * @param {number} iAccountId
 * @param {string} sHash
 * @param {string=} sPublicHash
 * 
 * @return {string}
 */
Utils.getFilestorageViewLinkByHash = function (iAccountId, sHash, sPublicHash)
{
	var sUrl = '?/Raw/FilesView/' + iAccountId + '/' + sHash;
	if (!Utils.isUnd(sPublicHash))
	{
		sUrl = sUrl + '/0/' + sPublicHash;
	}
	return sUrl;
};

/**
 * Gets link for thumbnail by hash.
 *
 * @param {number} iAccountId
 * @param {string} sHash
 * @param {string} sPublicHash
 *
 * @return {string}
 */
Utils.getFilestorageViewThumbnailLinkByHash = function (iAccountId, sHash, sPublicHash)
{
	var sUrl = '?/Raw/FilesThumbnail/' + iAccountId + '/' + sHash;
	if (!Utils.isUnd(sPublicHash))
	{
		sUrl = sUrl + '/0/' + sPublicHash;
	}
	return sUrl;
};

/**
 * Gets link for public by hash.
 *
 * @param {string} sHash
 * 
 * @return {string}
 */
Utils.getFilestoragePublicViewLinkByHash = function (sHash)
{
	return '?/Window/Files/0/' + sHash;
};

/**
 * Gets link for public by hash.
 *
 * @param {string} sHash
 * 
 * @return {string}
 */
Utils.getFilestoragePublicDownloadLinkByHash = function (sHash)
{
	return '?/Raw/FilesPub/0/' + sHash;
};

/**
 * @param {number} iMonth
 * @param {number} iYear
 * 
 * @return {number}
 */
Utils.daysInMonth = function (iMonth, iYear)
{
	if (0 < iMonth && 13 > iMonth && 0 < iYear)
	{
		return new Date(iYear, iMonth, 0).getDate();
	}

	return 31;
};

Utils.WindowOpener = {

	_iDefaultRatio: 0.8,
	_aOpenedWins: [],
	
	/**
	 * @param {{folder:Function, uid:Function}} oMessage
	 * @param {boolean=} bDrafts
	 */
	openMessage: function (oMessage, bDrafts)
	{
		if (oMessage)
		{
			var
				sFolder = oMessage.folder(),
				sUid = oMessage.uid(),
				sHash = ''
			;
			
			if (bDrafts)
			{
				sHash = App.Routing.buildHashFromArray([Enums.Screens.SingleCompose, 'drafts', sFolder, sUid]);
			}
			else
			{
				sHash = App.Routing.buildHashFromArray([Enums.Screens.SingleMessageView, sFolder, 'msg' + sUid]);
			}

			this.openTab(sHash);
		}
	},

	/**
	 * @param {string} sUrl
	 * @param {string=} sWinName
	 * 
	 * @return Object
	 */
	openTab: function (sUrl, sWinName)
	{
		$.cookie('aft-cache-ctrl', '1');
		var oWin = null;

		oWin = window.open(sUrl, '_blank');
		
		if (oWin)
		{
			oWin.focus();
			oWin.name = sWinName ? sWinName : (AppData.Accounts ? AppData.Accounts.currentId() : 0);
			this._aOpenedWins.push(oWin);
		}
		
		return oWin;
	},
	
	/**
	 * @param {string} sUrl
	 * @param {string} sPopupName
	 * @param {boolean=} bMenubar = false
	 * 
	 * @return Object
	 */
	open: function (sUrl, sPopupName, bMenubar)
	{
		var
			sMenubar = (bMenubar) ? ',menubar=yes' : ',menubar=no',
			sParams = 'location=no,toolbar=no,status=no,scrollbars=yes,resizable=yes' + sMenubar,
			oWin = null
		;

		sPopupName = sPopupName.replace(/\W/g, ''); // forbidden characters in the name of the window for ie
		sParams += this._getSizeParameters();

		oWin = window.open(sUrl, sPopupName, sParams);
		oWin.focus();
		oWin.name = AppData.Accounts ? AppData.Accounts.currentId() : 0; //no Accounts in client helpdesk

		this._aOpenedWins.push(oWin);
		
		return oWin;
	},
	
	/**
	 * @returns {Array}
	 */
	getOpenedDraftUids: function ()
	{
		this._aOpenedWins = _.filter(this._aOpenedWins, function (oWin) {
			return !oWin.closed;
		});
		
		var aDraftUids = _.map(this._aOpenedWins, function (oWin) {
			return oWin.App ? oWin.App.MailCache.editedDraftUid() : '';
		});
		
		if (App.Screens.hasOpenedMinimizedPopups())
		{
			aDraftUids.push(App.MailCache.editedDraftUid());
		}
		
		return _.uniq(_.compact(aDraftUids));
	},
	
	/**
	 * @param {string} aUids
	 */
	closeComposesWithDraftUids: function (aUids)
	{
		_.each(this._aOpenedWins, function (oWin) {
			if (oWin.App && -1 !== Utils.inArray(oWin.App.MailCache.editedDraftUid(), aUids))
			{
				oWin.close();
			}
		});
		
		if (-1 !== Utils.inArray(App.MailCache.editedDraftUid(), aUids))
		{
			App.Api.closeComposePopup();
		}
	},

	closeAll: function ()
	{
		var
			iLen = this._aOpenedWins.length,
			iIndex = 0,
			oWin = null
		;

		for (; iIndex < iLen; iIndex++)
		{
			oWin = this._aOpenedWins[iIndex];
			if (!oWin.closed)
			{
				oWin.close();
			}
		}

		this._aOpenedWins = [];
	},

	/**
	 * @return string
	 */
	_getSizeParameters: function ()
	{
		var
			iScreenWidth = window.screen.width,
			iWidth = Math.ceil(iScreenWidth * this._iDefaultRatio),
			iLeft = Math.ceil((iScreenWidth - iWidth) / 2),

			iScreenHeight = window.screen.height,
			iHeight = Math.ceil(iScreenHeight * this._iDefaultRatio),
			iTop = Math.ceil((iScreenHeight - iHeight) / 2)
		;

		return ',width=' + iWidth + ',height=' + iHeight + ',top=' + iTop + ',left=' + iLeft;
	}
};

/**
 * @param {?} oObject
 * @param {string} sDelegateName
 * @param {Array=} aParameters
 */
Utils.delegateRun = function (oObject, sDelegateName, aParameters)
{
	if (oObject && oObject[sDelegateName])
	{
		oObject[sDelegateName].apply(oObject, _.isArray(aParameters) ? aParameters : []);
	}
};

/**
 * @param {string} input
 * @param {number} multiplier
 * @return {string}
 */
Utils.strRepeat = function (input, multiplier)
{
	return (new Array(multiplier + 1)).join(input);
};


Utils.deferredUpdate = function (element, state, duration, callback) {
	
	if (!element.__interval && !!state)
	{
		element.__state = true;
		callback(element, true);

		element.__interval = window.setInterval(function () {
			if (!element.__state)
			{
				callback(element, false);
				window.clearInterval(element.__interval);
				element.__interval = null;
			}
		}, duration);
	}
	else if (!state)
	{
		element.__state = false;
	}
};

Utils.draggableMessages = function ()
{
	return $('<div class="draggable draggableMessages"><div class="content"><span class="count-text"></span></div></div>').appendTo('#pSevenHidden');
};

Utils.draggableContacts = function ()
{
	return $('<div class="draggable draggableContacts"><div class="content"><span class="count-text"></span></div></div>').appendTo('#pSevenHidden');
};

Utils.removeActiveFocus = function ()
{
	if (document && document.activeElement && document.activeElement.blur)
	{
		var oA = $(document.activeElement);
		if (oA.is('input') || oA.is('textarea'))
		{
			document.activeElement.blur();
		}
	}
};

Utils.uiDropHelperAnim = function (oEvent, oUi)
{
	var
		iLeft = 0,
		iTop = 0,
		iNewLeft = 0,
		iNewTop = 0,
		iWidth = 0,
		iHeight = 0,
		helper = oUi.helper.clone().appendTo('#pSevenHidden'),
		target = $(oEvent.target).find('.animGoal'),
		position = null
	;

	target = target[0] ? $(target[0]) : $(oEvent.target);
	position = target && target[0] ? target.offset() : null;

	if (position)
	{
		iLeft = window.Math.round(position.left);
		iTop = window.Math.round(position.top);

		iWidth = target.width();
		iHeight = target.height();

		iNewLeft = iLeft;
		if (0 < iWidth)
		{
			iNewLeft += window.Math.round(iWidth / 2);
		}

		iNewTop = iTop;
		if (0 < iHeight)
		{
			iNewTop += window.Math.round(iHeight / 2);
		}

		helper.animate({
			'left': iNewLeft + 'px',
			'top': iNewTop + 'px',
			'font-size': '0px',
			'opacity': 0
		}, 800, 'easeOutQuint', function() {
			$(this).remove();
		});
	}
};

Utils.isTextFieldFocused = function ()
{
	var
		mTag = document && document.activeElement ? document.activeElement : null,
		mTagName = mTag ? mTag.tagName : null,
		mTagType = mTag && mTag.type ? mTag.type.toLowerCase() : null,
		mContentEditable = mTag ? mTag.contentEditable : null
	;
	
	return ('INPUT' === mTagName && (mTagType === 'text' || mTagType === 'password' || mTagType === 'email')) ||
		'TEXTAREA' === mTagName || 'IFRAME' === mTagName || mContentEditable === 'true';
};

Utils.removeSelection = function ()
{
	if (window.getSelection)
	{
		window.getSelection().removeAllRanges();
	}
	else if (document.selection)
	{
		document.selection.empty();
	}
};

Utils.getMonthNamesArray = function ()
{
	var
		aMonthes = Utils.i18n('DATETIME/MONTH_NAMES').split(' '),
		iLen = 12,
		iIndex = aMonthes.length
	;
	
	for (; iIndex < iLen; iIndex++)
	{
		aMonthes[iIndex] = '';
	}
	
	return aMonthes;
};

/**
 * http://docs.translatehouse.org/projects/localization-guide/en/latest/l10n/pluralforms.html?id=l10n/pluralforms
 * 
 * @param {string} sLang
 * @param {number} iNumber
 * 
 * @return {number}
 */
Utils.getPlural = function (sLang, iNumber)
{
	var iResult = 0;
	iNumber = Utils.pInt(iNumber);

	switch (sLang)
	{
		case 'Arabic':
			iResult = (iNumber === 0 ? 0 : iNumber === 1 ? 1 : iNumber === 2 ? 2 : iNumber % 100 >= 3 && iNumber % 100 <= 10 ? 3 : iNumber % 100 >= 11 ? 4 : 5);
			break;
		case 'Bulgarian':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Chinese-Simplified':
			iResult = 0;
			break;
		case 'Chinese-Traditional':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Czech':
			iResult = (iNumber === 1) ? 0 : (iNumber >= 2 && iNumber <= 4) ? 1 : 2;
			break;
		case 'Danish':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Dutch':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'English':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Estonian':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Finish':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'French':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'German':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Greek':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Hebrew':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Hungarian':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Italian':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Japanese':
			iResult = 0;
			break;
		case 'Korean':
			iResult = 0;
			break;
		case 'Latvian':
			iResult = (iNumber % 10 === 1 && iNumber % 100 !== 11 ? 0 : iNumber !== 0 ? 1 : 2);
			break;
		case 'Lithuanian':
			iResult = (iNumber % 10 === 1 && iNumber % 100 !== 11 ? 0 : iNumber % 10 >= 2 && (iNumber % 100 < 10 || iNumber % 100 >= 20) ? 1 : 2);
			break;
		case 'Norwegian':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Persian':
			iResult = 0;
			break;
		case 'Polish':
			iResult = (iNumber === 1 ? 0 : iNumber % 10 >= 2 && iNumber % 10 <= 4 && (iNumber % 100 < 10 || iNumber % 100 >= 20) ? 1 : 2);
			break;
		case 'Portuguese-Portuguese':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Portuguese-Brazil':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Romanian':
			iResult = (iNumber === 1 ? 0 : (iNumber === 0 || (iNumber % 100 > 0 && iNumber % 100 < 20)) ? 1 : 2);
			break;
		case 'Russian':
			iResult = (iNumber % 10 === 1 && iNumber % 100 !== 11 ? 0 : iNumber % 10 >= 2 && iNumber % 10 <= 4 && (iNumber % 100 < 10 || iNumber % 100 >= 20) ? 1 : 2);
			break;
		case 'Slovenian':
			iResult = ((iNumber % 10 === 1 && iNumber % 100 !== 11) ? 0 : ((iNumber % 10 === 2 && iNumber % 100 !== 12) ? 1 : 2));
			break;
		case 'Serbian':
			iResult = (iNumber % 10 === 1 && iNumber % 100 !== 11 ? 0 : iNumber % 10 >= 2 && iNumber % 10 <= 4 && (iNumber % 100 < 10 || iNumber % 100 >= 20) ? 1 : 2);
			break;
		case 'Spanish':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Swedish':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Thai':
			iResult = 0;
			break;
		case 'Turkish':
			iResult = (iNumber === 1 ? 0 : 1);
			break;
		case 'Ukrainian':
			iResult = (iNumber % 10 === 1 && iNumber % 100 !== 11 ? 0 : iNumber % 10 >= 2 && iNumber % 10 <= 4 && (iNumber % 100 < 10 || iNumber % 100 >= 20) ? 1 : 2);
			break;
		case 'Vietnamese':
			iResult = 0;
			break;
		default:
			iResult = 0;
			break;
	}

	return iResult;
};

/**
 * @param {string} sFile
 * 
 * @return {string}
 */
Utils.getFileExtension = function (sFile)
{
	var 
		sResult = '',
		iIndex = sFile.lastIndexOf('.')
	;
	
	if (iIndex > -1)
	{
		sResult = sFile.substr(iIndex + 1);
	}

	return sResult;
};

/**
 * @param {string} sFile
 * 
 * @return {string}
 */
Utils.getFileNameWithoutExtension = function (sFile)
{
	var 
		sResult = sFile,
		iIndex = sFile.lastIndexOf('.')
	;
	if (iIndex > -1)
	{
		sResult = sFile.substr(0, iIndex);	
	}
	return sResult;
};

/**
 * @param {Object} oElement
 * @param {Object} oItem
 */
Utils.defaultOptionsAfterRender = function (oElement, oItem)
{
	if (oItem)
	{
		if (!Utils.isUnd(oItem.disable))
		{
			ko.applyBindingsToNode(oElement, {
				'disable': oItem.disable
			}, oItem);
		}
	}
};

/**
 * @param {string} sDateFormat
 * 
 * @return string
 */
Utils.getDateFormatForMoment = function (sDateFormat)
{
	var sMomentDateFormat = 'MM/DD/YYYY';
	
	switch (sDateFormat)
	{
		case 'MM/DD/YYYY':
			sMomentDateFormat = 'MM/DD/YYYY';
			break;
		case 'DD/MM/YYYY':
			sMomentDateFormat = 'DD/MM/YYYY';
			break;
		case 'DD Month YYYY':
			sMomentDateFormat = 'DD MMMM YYYY';
			break;
	}
	
	return sMomentDateFormat;
};

/**
 * @param {string} sDateFormat
 * 
 * @return string
 */
Utils.getDateFormatForDatePicker = function (sDateFormat)
{
	var sDatePickerDateFormat = 'mm/dd/yy';
	
	switch (sDateFormat)
	{
		case 'MM/DD/YYYY':
			sDatePickerDateFormat = 'mm/dd/yy';
			break;
		case 'DD/MM/YYYY':
			sDatePickerDateFormat = 'dd/mm/yy';
			break;
		case 'DD Month YYYY':
			sDatePickerDateFormat = 'dd MM yy';
			break;
	}
	
	return sDatePickerDateFormat;
};

/**
 * @return Array
 */
Utils.getDateFormatsForSelector = function ()
{
	return _.map(AppData.App.DateFormats, function (sDateFormat) {
		switch (sDateFormat)
		{
			case 'MM/DD/YYYY':
				return {name: Utils.i18n('DATETIME/DATEFORMAT_MMDDYYYY'), value: sDateFormat};
			case 'DD/MM/YYYY':
				return {name: Utils.i18n('DATETIME/DATEFORMAT_DDMMYYYY'), value: sDateFormat};
			case 'DD Month YYYY':
				return {name: Utils.i18n('DATETIME/DATEFORMAT_DDMONTHYYYY'), value: sDateFormat};
			default:
				return {name: sDateFormat, value: sDateFormat};
		}
	});
};

/**
 * @param {string} sSubject
 * 
 * @return {string}
 */
Utils.getTitleForEvent = function (sSubject)
{
	var
		sTitle = sSubject ? Utils.trim(sSubject.replace(/[\n\r]/, ' ')) : '',
		iFirstSpacePos = sTitle.indexOf(' ', 180)
	;

	if (iFirstSpacePos >= 0)
	{
		sTitle = sTitle.substring(0, iFirstSpacePos) + '...';
	}
	
	if (sTitle.length > 200)
	{
		sTitle = sTitle.substring(0, 200) + '...';
	}
	
	return sTitle;
};

Utils.desktopNotify = (function ()
{
	var aNotifications = [];

	return function (oData)
	{
		if (oData && AppData.User.DesktopNotifications && window.Notification && !App.focused())
		{
			switch (oData.action)
			{
				case 'show':
					if (window.Notification.permission !== Enums.notificationPermission.Denied)
					{
						// oData - action, body, dir, lang, tag, icon, callback, timeout
						var
							oOptions = { //https://developer.mozilla.org/en-US/docs/Web/API/Notification
								body: oData.body || '', //A string representing an extra content to display within the notification
								dir: oData.dir || 'auto', //The direction of the notification; it can be auto, ltr, or rtl
								lang: oData.lang || '', //Specify the lang used within the notification. This string must be a valid BCP 47 language tag
								tag: oData.tag || Math.floor(Math.random() * (1000 - 100) + 100), //An ID for a given notification that allows to retrieve, replace or remove it if necessary
								icon: oData.icon || false //The URL of an image to be used as an icon by the notification
							},
							oNotification,
							fShowNotification = function()
							{
								oNotification = new window.Notification(oData.title, oOptions); //Firefox and Safari close the notifications automatically after a few moments, e.g. 4 seconds.
								oNotification.onclick = function (oEv) //there are also onshow, onclose & onerror events
								{
									if(oData.callback)
									{
										oData.callback();
									}
									oNotification.close();
								};

								if (oData.timeout)
								{
									setTimeout(function() { oNotification.close(); }, oData.timeout);
								}
								aNotifications.push(oNotification);
							}
						;
						
						if (window.Notification.permission === Enums.notificationPermission.Granted)
						{
							fShowNotification();
						}
						else if (window.Notification.permission === Enums.notificationPermission.Default)
						{
							window.Notification.requestPermission(function (sPermission) {
								if(sPermission === Enums.notificationPermission.Granted)
								{
									fShowNotification();
								}
							});
						}
					}
					break;
				case 'hide':
					_.each(aNotifications, function (oNotifi, ikey) {
						if (oData.tag === oNotifi.tag) {
							oNotifi.close();
							aNotifications.splice(ikey, 1);
						}
					});
					break;
				case 'hideAll':
					_.each(aNotifications,function (oNotifi) {
						oNotifi.close();
					});
					aNotifications.length = 0;
					break;
			}
		}
	};
}());

/**
 * @return {boolean}
 */
Utils.isRTL = function ()
{
	return $html.hasClass('rtl');
};

/**
 * @param {string} sName
 * @return {boolean}
 */
Utils.validateFileOrFolderName = function (sName)
{
	sName = Utils.trim(sName);
	return '' !== sName && !/["\/\\*?<>|:]/.test(sName);
};

/**
 * @param {string} sColor
 * @param {number} iPercent
 * 
 * @return {string}
 */
Utils.shadeColor = function (sColor, iPercent) 
{
	var
		usePound = false,
		num = 0,
		r = 0,
		b = 0,
		g = 0
	;
	
	if (sColor[0] === "#") 
	{
		sColor = sColor.slice(1);
		usePound = true;
	}
	num = window.parseInt(sColor, 16);
	r = (num >> 16) + iPercent;
	if (r > 255) 
	{
		r = 255;
	} 
	else if (r < 0) 
	{
		r = 0;
	}
	b = ((num >> 8) & 0x00FF) + iPercent;
	if (b > 255) 
	{
		b = 255;
	} 
	else if (b < 0) 
	{
		b = 0;
	}
	g = (num & 0x0000FF) + iPercent;
	if (g > 255) 
	{
		g = 255;
	} 
	else if (g < 0) 
	{
		g = 0;
	}
	return (usePound ? "#" : "") + (g | (b << 8) | (r << 16)).toString(16);
};

/**
 * @param {Object} ChildClass
 * @param {Object} ParentClass
 */
Utils.extend = function (ChildClass, ParentClass)
{
	/**
	 * @constructor
	 */
	var TmpClass = function(){};
	TmpClass.prototype = ParentClass.prototype;
	ChildClass.prototype = new TmpClass();
	ChildClass.prototype.constructor = ChildClass;
};

Utils.thumbQueue = (function () {

	var
		oImages = {},
		oImagesIncrements = {},
		iNumberOfImages = 2
	;

	return function (sSessionUid, sImageSrc, fImageSrcObserver)
	{
		if(sImageSrc && fImageSrcObserver)
		{
			if(!(sSessionUid in oImagesIncrements) || oImagesIncrements[sSessionUid] > 0) //load first images
			{
				if(!(sSessionUid in oImagesIncrements)) //on first image
				{
					oImagesIncrements[sSessionUid] = iNumberOfImages;
					oImages[sSessionUid] = [];
				}
				oImagesIncrements[sSessionUid]--;

				fImageSrcObserver(sImageSrc); //load image
			}
			else //create queue
			{
				oImages[sSessionUid].push({
					imageSrc: sImageSrc,
					imageSrcObserver: fImageSrcObserver,
					messageUid: sSessionUid
				});
			}
		}
		else //load images from queue (fires load event)
		{
			if(oImages[sSessionUid] && oImages[sSessionUid].length)
			{
				oImages[sSessionUid][0].imageSrcObserver(oImages[sSessionUid][0].imageSrc);
				oImages[sSessionUid].shift();
			}
		}
	};
}());

Utils.checkConnection = (function () {

	var
		iTimer = -1,
		iLastWakeTime = new Date().getTime(),
		iCurrentTime = 0,
		bAwoke = false
	;

	setInterval(function() { //fix for sleep mode
		iCurrentTime = new Date().getTime();
		bAwoke = iCurrentTime > (iLastWakeTime + 5000 + 1000);
		iLastWakeTime = iCurrentTime;
		if (bAwoke)
		{
			App.Api.hideError(true);
		}
	}, 5000);

	return function (sAction, sStatus)
	{
		clearTimeout(iTimer);
		if (sStatus !== 'error')
		{
			App.InternetConnectionError = false;
			App.Api.hideError(true);
		}
		else
		{
			if (sAction === 'SystemPing')
			{
				App.InternetConnectionError = true;
				App.Api.showError(Utils.i18n('WARNING/NO_INTERNET_CONNECTION'), false, true, true);
				iTimer = setTimeout(function () {
					App.Ajax.send({'Action': 'SystemPing'});
				}, 60000);
			}
			else
			{
				App.Ajax.send({'Action': 'SystemPing'});
			}
		}
	};
}());

Utils.loadScript = function (sUrl, fCallback, aParams, sFuncName)
{
	var script = document.createElement('script');
	if (!Utils.isUnd(sFuncName) && fCallback)
	{
		window[sFuncName] = fCallback;
	}
	if (Utils.isUnd(aParams))
	{
		aParams = {};
	}
	
	_.each(aParams, function(value, key){ 
		script.setAttribute(key, value);
	});
	
	script.type = 'text/javascript';
	script.src = sUrl;
	document.body.appendChild(script);
};

Utils.registerMailto = function (bRegisterOnce)
{
	if (window.navigator && Utils.isFunc(window.navigator.registerProtocolHandler) && (!bRegisterOnce || App.Storage.getData('MailtoAsked') !== 1))
	{
		window.navigator.registerProtocolHandler(
			'mailto',
			Utils.Common.getAppPath() + '#' + Enums.Screens.Compose + '/to/%s',
			AppData.App.SiteName !== '' ? AppData.App.SiteName : 'WebMail'
		);

		App.Storage.setData('MailtoAsked', 1);
	}
};

Utils.CustomTooltip = {
	_$Region: null,
	_$ArrowTop: null,
	_$Text: null,
	_$ArrowBottom: null,
	_iArrowBorderLeft: 0,
	_iArrowMarginLeft: 0,
	_iLeftShift: 0,
	_bInitialized: false,
	_bShown: false,
	
	iHideTimer: 0,
	iTimer: 0,
	
	init: function ()
	{
		if (!this._bInitialized)
		{
			this._$Region = $('<span class="custom_tooltip"></span>').appendTo('body').hide();
			this._$ArrowTop = $('<span class="custom_tooltip_arrow top"></span>').appendTo(this._$Region);
			this._$Text = $('<span class="custom_tooltip_text"></span>').appendTo(this._$Region);
			this._$ArrowBottom = $('<span class="custom_tooltip_arrow bottom"></span>').appendTo(this._$Region);
			
			this._iArrowMarginLeft = Utils.pInt(this._$ArrowTop.css('margin-left'));
			this._iArrowBorderLeft = Utils.pInt(this._$ArrowTop.css('border-left-width'));
			this._iLeftShift = Utils.pInt(this._$Region.css('margin-left')) + this._iArrowMarginLeft + this._iArrowBorderLeft;
			
			this._bInitialized = true;
		}
		
		this._$ArrowTop.show();
		this._$ArrowBottom.hide();
		this._$ArrowTop.css({
			'margin-left': this._iArrowMarginLeft + 'px'
		});
		this._$ArrowBottom.css({
			'margin-left': this._iArrowMarginLeft + 'px'
		});
	},
	
	show: function (sText, $ItemToAlign)
	{
		this.init();
		
		var
			oItemOffset = $ItemToAlign.offset(),
			iItemWidth = $ItemToAlign.width(),
			iItemHalfWidth = (iItemWidth < 70) ? iItemWidth/2 : iItemWidth/4,
			iItemPaddingLeft = Utils.pInt($ItemToAlign.css('padding-left')),
			jqBody = $('body')
		;
		
		this._$Text.html(sText);
		this._bShown = true;
		this._$Region.stop().fadeIn(260, _.bind(function () {
			if (!this._bShown)
			{
				this._$Region.hide();
			}
		}, this)).css({
			'top': oItemOffset.top + $ItemToAlign.outerHeight() + 1,
			'left': oItemOffset.left + iItemPaddingLeft + iItemHalfWidth - this._iLeftShift,
			'right': 'auto'
		});
		
		if (jqBody.outerHeight() < this._$Region.outerHeight() + this._$Region.offset().top)
		{
			this._$ArrowTop.hide();
			this._$ArrowBottom.show();
			this._$Region.css({
				'top': oItemOffset.top - this._$Region.outerHeight()
			});
		}

		setTimeout(function () {
			if (jqBody.width() < (this._$Region.outerWidth(true) + this._$Region.offset().left))
			{
				this._$Region.css({
					'left': 'auto',
					'right': 0
				});
				this._$ArrowTop.css({
					'margin-left': (iItemHalfWidth + oItemOffset.left - this._$Region.offset().left - this._iArrowBorderLeft) + 'px'
				});
				this._$ArrowBottom.css({
					'margin-left': (iItemHalfWidth + oItemOffset.left - this._$Region.offset().left - this._iArrowBorderLeft + Utils.pInt(this._$Region.css('margin-right'))) + 'px'
				});
			}
		}.bind(this), 1);
	},
	
	hide: function ()
	{
		if (this._bInitialized)
		{
			this._bShown = false;
			this._$Region.hide();
		}
	}
};

/**
 * @param {string} sHtml
 * @returns {Boolean}
 */
Utils.htmlStartsWithBlockquote = function (sHtml)
{
	var
		aParts = sHtml.split('<blockquote'),
		sBegin = aParts.length > 0 ? aParts[0] : '',
		sBeginWithoutTags = Utils.trim(sBegin.replace(/<[^>]*>/g, ''))
	;
	
	return sBeginWithoutTags === '';
};

/**
 * @param {string} sText
 * @returns {string}
 */
Utils.escapeQuotes  = function (sText)
{
	return sText.replace(/'/g, "\\\'").replace(/"/g, "\\\"");
};

/**
 * @param {string} sFaviconUrl
 */
Utils.changeFavicon  = function (sFaviconUrl)
{
	$('head').append('<link rel="shortcut icon" type="image/x-icon" href=' + sFaviconUrl + ' />');
};

/**
 * @returns {Boolean}
 */
Utils.checkCookies = function ()
{
	$.cookie('checkCookie', '1', { path: '/' });
	var bResult = $.cookie('checkCookie') === '1';
	if (!bResult)
	{
		App.Screens.showError(Utils.i18n('WARNING/COOKIES_DISABLED'), false, true);
	}

	return bResult;
};

/**
 * @param {object} oEvent
 */
Utils.calmEvent  = function (oEvent)
{
	if (oEvent)
	{
		if (oEvent.stop)
		{
			oEvent.stop();
		}
		if (oEvent.preventDefault)
		{
			oEvent.preventDefault();
		}
		if (oEvent.stopPropagation)
		{
			oEvent.stopPropagation();
		}
		if (oEvent.stopImmediatePropagation)
		{
			oEvent.stopImmediatePropagation();
		}
		oEvent.cancelBubble = true;
		oEvent.returnValue = false;
	}
};
/* 
 * Can be connected to external applications. Don't use App object here.
 */

Utils.Common = {};

/**
 * Obtains parameters from browser get-string.
 * **aGetParams** - static variable wich includes all get parameters.
 * 
 * @param {string} sParamName Name of parameter wich is obtained from get-string
 * 
 * @return {string|null}
 */
Utils.Common.getRequestParam = function (sParamName)
{
	var
		aParams = [],
		aGetParams = [],
		sResult = null
	;
	
	if (this.aGetParams === undefined)
	{
		aParams = (location.search !== '') ? (location.search.substr(1)).split('&') : [];

		if (aParams.length > 0)
		{
			_.each(aParams, function (sParam) {
				var aKeyValues = sParam.split('=');
				aGetParams[aKeyValues[0]] = aKeyValues.length > 1 ? aKeyValues[1] : '';
			});
		}
		
		this.aGetParams = aGetParams;
	}
	
	if (this.aGetParams[sParamName] !== undefined)
	{
		sResult = this.aGetParams[sParamName];
	}

	return sResult;
};

/**
 * Obtains application path from location object.
 * 
 * @return {string}
 */
Utils.Common.getAppPath = function ()
{
	var sAppOrigin = window.location.origin || window.location.protocol + '//' + window.location.host;
	
	return sAppOrigin + window.location.pathname;
};

/**
 * Clears search and hash strings and reloads page.
 * 
 * @param {boolean} bOnlyReload If **true** doesn't clear search and hash in location.
 * @param {boolean} bClearSearch If **true** clears search string in location.
 */
Utils.Common.clearAndReloadLocation = function (bOnlyReload, bClearSearch)
{
	if (!bOnlyReload && (window.location.search !== '' || window.location.hash !== ''))
	{
		var sNewHref = Utils.Common.getAppPath();

		if (!bClearSearch && window.location.search !== '')
		{
			sNewHref += window.location.search;
		}

		if ('replaceState' in history)
		{
			history.replaceState('', document.title, sNewHref);
			window.location.reload(true);
		}
		else
		{
			window.location.href = sNewHref;
		}
	}
	else
	{
		window.location.reload();
	}
};

/**
 * Can be connected to external applications.
 */

Utils.File = {};

/**
 * Gets link for view by hash.
 *
 * @param {number} iAccountId
 * @param {string} sHash
 * @param {boolean=} bIsExt = false
 * @param {string=} sTenatHash = ''
 * 
 * @return {string}
 */
Utils.File.getViewLinkByHash = function (iAccountId, sHash, bIsExt, sTenatHash)
{
	var
		sViewLink = '?/Raw/View/' + iAccountId + '/' + sHash,
		sExtPart = (bIsExt === true) ? '/1' : '/0',
		sTenantPart = (typeof sTenatHash === 'string' && sTenatHash !== '') ? '/' + sTenatHash : ''
	;
		
	return sViewLink + sExtPart + sTenantPart;
};

/**
 * Used in AfterLogicApi, OpenPgpKey, Inputosaurus, CComposeViewModel, CHtmlEditorViewModel, CalendarSharePopup.
 * Included in main, mobile, helpdesk, calendar_pub, filestorage_pub applications.
 * 
 * @requires jquery
 * @requires underscore
 */

Utils.Address = {};

/**
 * Checks if specified email is correct.
 * Used in AfterLogicApi, CHtmlEditorViewModel.
 * 
 * @param {string} sValue String to check.
 * 
 * @return {boolean}
 */
Utils.Address.isCorrectEmail = function (sValue)
{
	return !!(sValue.match(/^[A-Z0-9\"!#\$%\^\{\}`~&'\+\-=_\.]+@[A-Z0-9\.\-]+$/i));
};

/**
 * Used in CAccountModel, CAddressModel, CIdentityModel, CContactListItemModel, CContactModel, CFetcherModel, CHelpdeskViewModel, CComposeViewModel.
 * 
 * @param {string} sName
 * @param {string} sEmail
 * @returns {string}
 */
Utils.Address.getFullEmail = function (sName, sEmail)
{
	var sFull = '';
	
	if (sEmail.length > 0)
	{
		if (sName.length > 0)
		{
			if (Utils.Address.isCorrectEmail(sName) || sName.indexOf(',') !== -1)
			{
				sFull = '"' + sName + '" <' + sEmail + '>';
			}
			else
			{
				sFull = sName + ' <' + sEmail + '>';
			}
		}
		else
		{
			sFull = sEmail;
		}
	}
	else
	{
		sFull = sName;
	}
	
	return sFull;
};

/**
 * Obtains Recipient-object which include "name", "email" and "fullEmail" fields from string.
 * Used in AfterLogicApi, OpenPgpKey, Inputosaurus, CComposeViewModel.
 * 
 * @param {string} sFullEmail String includes only name, only email or both name and email.
 * @param {boolean} bIgnoreQuotesInName
 *
 * @return {Object}
 */
Utils.Address.getEmailParts = function (sFullEmail, bIgnoreQuotesInName)
{
	var
		iQuote1Pos = sFullEmail.indexOf('"'),
		iQuote2Pos = sFullEmail.indexOf('"', iQuote1Pos + 1),
		iLeftBrocketPos = sFullEmail.indexOf('<', iQuote2Pos),
		iPrevLeftBroketPos = -1,
		iRightBrocketPos = -1,
		sName = '',
		sEmail = ''
	;

	while (iLeftBrocketPos !== -1)
	{
		iPrevLeftBroketPos = iLeftBrocketPos;
		iLeftBrocketPos = sFullEmail.indexOf('<', iLeftBrocketPos + 1);
	}

	iLeftBrocketPos = iPrevLeftBroketPos;
	iRightBrocketPos = sFullEmail.indexOf('>', iLeftBrocketPos + 1);

	if (iLeftBrocketPos === -1)
	{
		sEmail = $.trim(sFullEmail);
	}
	else
	{
		iQuote1Pos = bIgnoreQuotesInName ? -1 : iQuote1Pos;
		sName = (iQuote1Pos === -1) ?
			$.trim(sFullEmail.substring(0, iLeftBrocketPos)) :
			$.trim(sFullEmail.substring(iQuote1Pos + 1, iQuote2Pos));

		sEmail = $.trim(sFullEmail.substring(iLeftBrocketPos + 1, iRightBrocketPos));
	}

	return {
		'name': sName,
		'email': sEmail,
		'fullEmail': Utils.Address.getFullEmail(sName, sEmail)
	};
};

/**
 * Obtains list of Recipient-objects which include "name", "email" and "fullEmail" fields from string.
 * Used in AfterLogicApi, CalendarSharePopup, Inputosaurus.
 * 
 * @param {string} sRecipients Includes recipients, separated by separators.
 * @param {boolean} bIncludeLastIncorrectEmail If true, last recipient will be included to list, even if it is not correct email.
 * 
 * @returns {Array}
 */
Utils.Address.getArrayRecipients = function (sRecipients, bIncludeLastIncorrectEmail)
{
	var
		aSeparators = [',', ';', ' '],
		sStartRcp = '',
		sEndRcp = sRecipients,
		iPos = 0,
		iNextPos = 0,
		sFullEmail = '',
		oRecipient = null,
		aRecipients = []
	;
	
	while (sEndRcp.length > 0)
	{
		iPos = Utils.Address._getFirstSeparatorPosition(sEndRcp, aSeparators);
		iNextPos = iPos;
		
		while (_.indexOf(aSeparators, sEndRcp[iNextPos + 1]) !== -1)
		{
			iNextPos++;
		}
		
		if (iPos === -1)
		{
			sFullEmail = sStartRcp + sEndRcp;
			oRecipient = Utils.Address.getEmailParts(sFullEmail);
			if (bIncludeLastIncorrectEmail || Utils.Address.isCorrectEmail(oRecipient.email))
			{
				aRecipients.push(oRecipient);
			}
			sEndRcp = '';
		}
		else
		{
			sFullEmail = sStartRcp + sEndRcp.substring(0, iPos);
			oRecipient = Utils.Address.getEmailParts(sFullEmail);
			if (Utils.Address.isCorrectEmail(oRecipient.email))
			{
				aRecipients.push(oRecipient);
				sStartRcp = '';
			}
			else
			{
				sStartRcp += sEndRcp.substring(0, iNextPos + 1);
			}
			sEndRcp = sEndRcp.substring(iNextPos + 1);
		}
	}
	
	return aRecipients;
};

/**
 * Obtains position number of first separator-symbol in string. Avaliable separator symbols are specified in array.
 * 
 * @param {string} sValue String for search separator-symbol in.
 * @param {Array} aSeparators List of separators.
 * @returns {number}
 */
Utils.Address._getFirstSeparatorPosition = function (sValue, aSeparators)
{
	var iPos = -1;

	_.each(aSeparators, function (sSep) {
		var iSepPos = sValue.indexOf(sSep);
		if (iSepPos !== -1 && (iPos === -1 || iSepPos < iPos))
		{
			iPos = iSepPos;
		}
	});

	return iPos;
};

/**
 * Used in CComposeViewModel.
 * 
 * @param {string} sAddresses
 * 
 * @return {Array}
 */
Utils.Address.getIncorrectEmailsFromAddressString = function (sAddresses)
{
	var
		aEmails = sAddresses.replace(/"[^"]*"/g, '').replace(/;/g, ',').split(','),
		aIncorrectEmails = [],
		iIndex = 0,
		iLen = aEmails.length,
		sFullEmail = '',
		oEmailParts = null
	;

	for (; iIndex < iLen; iIndex++)
	{
		sFullEmail = $.trim(aEmails[iIndex]);
		if (sFullEmail.length > 0)
		{
			oEmailParts = Utils.Address.getEmailParts($.trim(aEmails[iIndex]));
			if (!Utils.Address.isCorrectEmail(oEmailParts.email))
			{
				aIncorrectEmails.push(oEmailParts.email);
			}
		}
	}

	return aIncorrectEmails;
};


/**
 * @param {Function} list (knockout)
 * @param {Function=} fSelectCallback
 * @param {Function=} fDeleteCallback
 * @param {Function=} fDblClickCallback
 * @param {Function=} fEnterCallback
 * @param {Function=} multiplyLineFactor (knockout)
 * @param {boolean=} bResetCheckedOnClick = false
 * @param {boolean=} bCheckOnSelect = false
 * @param {boolean=} bUnselectOnCtrl = false
 * @param {boolean=} bDisableMultiplySelection = false
 * @constructor
 */
function CSelector(list, fSelectCallback, fDeleteCallback, fDblClickCallback, fEnterCallback, multiplyLineFactor,
	bResetCheckedOnClick, bCheckOnSelect, bUnselectOnCtrl, bDisableMultiplySelection)
{
	this.fBeforeSelectCallback = null;
	this.fSelectCallback = fSelectCallback || function() {};
	this.fDeleteCallback = fDeleteCallback || function() {};
	this.fDblClickCallback = (!bMobileApp && fDblClickCallback) ? fDblClickCallback : function() {};
	this.fEnterCallback = fEnterCallback || function() {};
	this.bResetCheckedOnClick = Utils.isUnd(bResetCheckedOnClick) ? false : !!bResetCheckedOnClick;
	this.bCheckOnSelect = Utils.isUnd(bCheckOnSelect) ? false : !!bCheckOnSelect;
	this.bUnselectOnCtrl = Utils.isUnd(bUnselectOnCtrl) ? false : !!bUnselectOnCtrl;
	this.bDisableMultiplySelection = Utils.isUnd(bDisableMultiplySelection) ? false : !!bDisableMultiplySelection;

	this.useKeyboardKeys = ko.observable(false);

	this.list = ko.observableArray([]);

	if (list && list['subscribe'])
	{
		list['subscribe'](function (mValue) {
			this.list(mValue);
		}, this);
	}
	
	this.multiplyLineFactor = multiplyLineFactor;
	
	this.oLast = null;
	this.oListScope = null;
	this.oScrollScope = null;

	this.iTimer = 0;
	this.iFactor = 1;

	this.KeyUp = Enums.Key.Up;
	this.KeyDown = Enums.Key.Down;
	this.KeyLeft = Enums.Key.Up;
	this.KeyRight = Enums.Key.Down;

	if (this.multiplyLineFactor)
	{
		if (this.multiplyLineFactor.subscribe)
		{
			this.multiplyLineFactor.subscribe(function (iValue) {
				this.iFactor = 0 < iValue ? iValue : 1;
			}, this);
		}
		else
		{
			this.iFactor = Utils.pInt(this.multiplyLineFactor);
		}

		this.KeyUp = Enums.Key.Up;
		this.KeyDown = Enums.Key.Down;
		this.KeyLeft = Enums.Key.Left;
		this.KeyRight = Enums.Key.Right;

		if ($('html').hasClass('rtl'))
		{
			this.KeyLeft = Enums.Key.Right;
			this.KeyRight = Enums.Key.Left;
		}
	}

	this.sActionSelector = '';
	this.sSelectabelSelector = '';
	this.sCheckboxSelector = '';

	var self = this;

	// reading returns a list of checked items.
	// recording (bool) puts all checked, or unchecked.
	this.listChecked = ko.computed({
		'read': function () {
			var aList = _.filter(this.list(), function (oItem) {
				var
					bC = oItem.checked(),
					bS = oItem.selected()
				;

				return bC || (self.bCheckOnSelect && bS);
			});

			return aList;
		},
		'write': function (bValue) {
			bValue = !!bValue;
			_.each(this.list(), function (oItem) {
				oItem.checked(bValue);
			});
			this.list.valueHasMutated();
		},
		'owner': this
	});

	this.checkAll = ko.computed({
		'read': function () {
			return 0 < this.listChecked().length;
		},

		'write': function (bValue) {
			this.listChecked(!!bValue);
		},
		'owner': this
	});

	this.selectorHook = ko.observable(null);

	this.selectorHook.subscribe(function () {
		var oPrev = this.selectorHook();
		if (oPrev)
		{
			oPrev.selected(false);
		}
	}, this, 'beforeChange');

	this.selectorHook.subscribe(function (oGroup) {
		if (oGroup)
		{
			oGroup.selected(true);
		}
	}, this);

	this.itemSelected = ko.computed({

		'read': this.selectorHook,

		'write': function (oItemToSelect) {

			this.selectorHook(oItemToSelect);

			if (oItemToSelect)
			{
//				self.scrollToSelected();
				this.oLast = oItemToSelect;
			}
		},
		'owner': this
	});

	this.list.subscribe(function (aList) {
		if (_.isArray(aList))
		{
			var	oSelected = this.itemSelected();
			if (oSelected)
			{
				if (!_.find(aList, function (oItem) {
					return oSelected === oItem;
				}))
				{
					this.itemSelected(null);
				}
			}
		}
		else
		{
			this.itemSelected(null);
		}
	}, this);

	this.listCheckedOrSelected = ko.computed({
		'read': function () {
			var
				oSelected = this.itemSelected(),
				aChecked = this.listChecked()
			;
			return 0 < aChecked.length ? aChecked : (oSelected ? [oSelected] : []);
		},
		'write': function (bValue) {
			if (!bValue)
			{
				this.itemSelected(null);
				this.listChecked(false);
			}
			else
			{
				this.listChecked(true);
			}
		},
		'owner': this
	});

	this.listCheckedAndSelected = ko.computed({
		'read': function () {
			var
				aResult = [],
				oSelected = this.itemSelected(),
				aChecked = this.listChecked()
			;

			if (aChecked)
			{
				aResult = aChecked.slice(0);
			}

			if (oSelected && _.indexOf(aChecked, oSelected) === -1)
			{
				aResult.push(oSelected);
			}

			return aResult;
		},
		'write': function (bValue) {
			if (!bValue)
			{
				this.itemSelected(null);
				this.listChecked(false);
			}
			else
			{
				this.listChecked(true);
			}
		},
		'owner': this
	});

	this.isIncompleteChecked = ko.computed(function () {
		var
			iM = this.list().length,
			iC = this.listChecked().length
		;
		return 0 < iM && 0 < iC && iM > iC;
	}, this);

	this.onKeydownBinded = _.bind(this.onKeydown, this);
}

CSelector.prototype.iTimer = 0;
CSelector.prototype.bResetCheckedOnClick = false;
CSelector.prototype.bCheckOnSelect = false;
CSelector.prototype.bUnselectOnCtrl = false;
CSelector.prototype.bDisableMultiplySelection = false;

/**
 * @param {Function} fBeforeSelectCallback
 */
CSelector.prototype.setBeforeSelectCallback = function (fBeforeSelectCallback)
{
	this.fBeforeSelectCallback = fBeforeSelectCallback || null;
};

CSelector.prototype.getLastOrSelected = function ()
{
	var
		iCheckedCount = 0,
		oLastSelected = null
	;
	
	_.each(this.list(), function (oItem) {
		if (oItem.checked())
		{
			iCheckedCount++;
		}

		if (oItem.selected())
		{
			oLastSelected = oItem;
		}
	});

	return 0 === iCheckedCount && oLastSelected ? oLastSelected : this.oLast;
};

/**
 * @return {boolean}
 */
/*CSelector.prototype.inFocus = function ()
{
	var mTagName = document && document.activeElement ? document.activeElement.tagName : null;
	return 'INPUT' === mTagName || 'TEXTAREA' === mTagName || 'IFRAME' === mTagName;
};*/

/**
 * @param {string} sActionSelector css-selector for the active for pressing regions of the list
 * @param {string} sSelectabelSelector css-selector to the item that was selected
 * @param {string} sCheckboxSelector css-selector to the element that checkbox in the list
 * @param {*} oListScope
 * @param {*} oScrollScope
 */
CSelector.prototype.initOnApplyBindings = function (sActionSelector, sSelectabelSelector, sCheckboxSelector, oListScope, oScrollScope)
{
	$(document).on('keydown', this.onKeydownBinded);

	this.oListScope = oListScope;
	this.oScrollScope = oScrollScope;
	this.sActionSelector = sActionSelector;
	this.sSelectabelSelector = sSelectabelSelector;
	this.sCheckboxSelector = sCheckboxSelector;

	var
		self = this,

		fEventClickFunction = function (oLast, oItem, oEvent) {

			var
				iIndex = 0,
				iLength = 0,
				oListItem = null,
				bChangeRange = false,
				bIsInRange = false,
				aList = [],
				bChecked = false
			;

			oItem = oItem ? oItem : null;
			if (oEvent && oEvent.shiftKey)
			{
				if (null !== oItem && null !== oLast && oItem !== oLast)
				{
					aList = self.list();
					bChecked = oItem.checked();

					for (iIndex = 0, iLength = aList.length; iIndex < iLength; iIndex++)
					{
						oListItem = aList[iIndex];

						bChangeRange = false;
						if (oListItem === oLast || oListItem === oItem)
						{
							bChangeRange = true;
						}

						if (bChangeRange)
						{
							bIsInRange = !bIsInRange;
						}

						if (bIsInRange || bChangeRange)
						{
							oListItem.checked(bChecked);
						}
					}
				}
			}

			if (oItem)
			{
				self.oLast = oItem;
			}
		}
	;

	$(this.oListScope).on('dblclick', sActionSelector, function (oEvent) {
		var oItem = ko.dataFor(this);
		if (oItem && oEvent && !oEvent.ctrlKey && !oEvent.altKey && !oEvent.shiftKey)
		{
			self.onDblClick(oItem);
		}
	});

	if (bMobileDevice)
	{
		$(this.oListScope).on('touchstart', sActionSelector, function (e) {

			if (!e)
			{
				return;
			}

			var
				t2 = e.timeStamp,
				t1 = $(this).data('lastTouch') || t2,
				dt = t2 - t1,
				fingers = e.originalEvent && e.originalEvent.touches ? e.originalEvent.touches.length : 0
			;

			$(this).data('lastTouch', t2);
			if (!dt || dt > 250 || fingers > 1)
			{
				return;
			}

			e.preventDefault();
			$(this).trigger('dblclick');
		});
	}

	$(this.oListScope).on('click', sActionSelector, function (oEvent) {

		var
			bClick = true,
			oSelected = null,
			oLast = self.getLastOrSelected(),
			oItem = ko.dataFor(this)
		;

		if (oItem && oEvent)
		{
			if (oEvent.shiftKey)
			{
				bClick = false;
				if (!self.bDisableMultiplySelection)
				{
					if (null === self.oLast)
					{
						self.oLast = oItem;
					}


					oItem.checked(!oItem.checked());
					fEventClickFunction(oLast, oItem, oEvent);
				}
			}
			else if (oEvent.ctrlKey)
			{
				bClick = false;
				if (!self.bDisableMultiplySelection)
				{
					self.oLast = oItem;
					oSelected = self.itemSelected();
					if (oSelected && !oSelected.checked() && !oItem.checked())
					{
						oSelected.checked(true);
					}

					if (self.bUnselectOnCtrl && oItem === self.itemSelected())
					{
						oItem.checked(!oItem.selected());
						self.itemSelected(null);
					}
					else
					{
						oItem.checked(!oItem.checked());
					}
				}
			}

			if (bClick)
			{
				self.onSelect(oItem);
				self.scrollToSelected();
			}
		}
	});

	$(this.oListScope).on('click', sCheckboxSelector, function (oEvent) {

		var oItem = ko.dataFor(this);
		if (oItem && oEvent && !self.bDisableMultiplySelection)
		{
			if (oEvent.shiftKey)
			{
				if (null === self.oLast)
				{
					self.oLast = oItem;
				}

				fEventClickFunction(self.getLastOrSelected(), oItem, oEvent);
			}
			else
			{
				self.oLast = oItem;
			}
		}

		if (oEvent && oEvent.stopPropagation)
		{
			oEvent.stopPropagation();
		}
	});

	$(this.oListScope).on('dblclick', sCheckboxSelector, function (oEvent) {
		if (oEvent && oEvent.stopPropagation)
		{
			oEvent.stopPropagation();
		}
	});
};

/**
 * @param {Object} oSelected
 * @param {number} iEventKeyCode
 * 
 * @return {Object}
 */
CSelector.prototype.getResultSelection = function (oSelected, iEventKeyCode)
{
	var
		self = this,
		bStop = false,
		bNext = false,
		oResult = null,
		iPageStep = this.iFactor,
		bMultiply = !!this.multiplyLineFactor,
		iIndex = 0,
		iLen = 0,
		aList = []
	;

	if (!oSelected && -1 < Utils.inArray(iEventKeyCode, [this.KeyUp, this.KeyDown, this.KeyLeft, this.KeyRight,
		Enums.Key.PageUp, Enums.Key.PageDown, Enums.Key.Home, Enums.Key.End]))
	{
		aList = this.list();
		if (aList && 0 < aList.length)
		{
			if (-1 < Utils.inArray(iEventKeyCode, [this.KeyDown, this.KeyRight, Enums.Key.PageUp, Enums.Key.Home]))
			{
				oResult = aList[0];
			}
			else if (-1 < Utils.inArray(iEventKeyCode, [this.KeyUp, this.KeyLeft, Enums.Key.PageDown, Enums.Key.End]))
			{
				oResult = aList[aList.length - 1];
			}
		}
	}
	else if (oSelected)
	{
		aList = this.list();
		iLen = aList ? aList.length : 0;

		if (0 < iLen)
		{
			if (
				Enums.Key.Home === iEventKeyCode || Enums.Key.PageUp === iEventKeyCode ||
				Enums.Key.End === iEventKeyCode || Enums.Key.PageDown === iEventKeyCode ||
				(bMultiply && (Enums.Key.Left === iEventKeyCode || Enums.Key.Right === iEventKeyCode)) ||
				(!bMultiply && (Enums.Key.Up === iEventKeyCode || Enums.Key.Down === iEventKeyCode))
			)
			{
				_.each(aList, function (oItem) {
					if (!bStop)
					{
						switch (iEventKeyCode) {
							case self.KeyUp:
							case self.KeyLeft:
								if (oSelected === oItem)
								{
									bStop = true;
								}
								else
								{
									oResult = oItem;
								}
								break;
							case Enums.Key.Home:
							case Enums.Key.PageUp:
								oResult = oItem;
								bStop = true;
								break;
							case self.KeyDown:
							case self.KeyRight:
								if (bNext)
								{
									oResult = oItem;
									bStop = true;
								}
								else if (oSelected === oItem)
								{
									bNext = true;
								}
								break;
							case Enums.Key.End:
							case Enums.Key.PageDown:
								oResult = oItem;
								break;
						}
					}
				});
			}
			else if (bMultiply && this.KeyDown === iEventKeyCode)
			{
				for (; iIndex < iLen; iIndex++)
				{
					if (oSelected === aList[iIndex])
					{
						iIndex += iPageStep;
						if (iLen - 1 < iIndex)
						{
							iIndex -= iPageStep;
						}

						oResult = aList[iIndex];
						break;
					}
				}
			}
			else if (bMultiply && this.KeyUp === iEventKeyCode)
			{
				for (iIndex = iLen; iIndex >= 0; iIndex--)
				{
					if (oSelected === aList[iIndex])
					{
						iIndex -= iPageStep;
						if (0 > iIndex)
						{
							iIndex += iPageStep;
						}

						oResult = aList[iIndex];
						break;
					}
				}
			}
		}
	}

	return oResult;
};

/**
 * @param {Object} oResult
 * @param {Object} oSelected
 * @param {number} iEventKeyCode
 */
CSelector.prototype.shiftClickResult = function (oResult, oSelected, iEventKeyCode)
{
	if (oSelected)
	{
		var
			bMultiply = !!this.multiplyLineFactor,
			bInRange = false,
			bSelected = false
		;

		if (-1 < Utils.inArray(iEventKeyCode,
			bMultiply ? [Enums.Key.Left, Enums.Key.Right] : [Enums.Key.Up, Enums.Key.Down]))
		{
			oSelected.checked(!oSelected.checked());
		}
		else if (-1 < Utils.inArray(iEventKeyCode, bMultiply ?
			[Enums.Key.Up, Enums.Key.Down, Enums.Key.PageUp, Enums.Key.PageDown, Enums.Key.Home, Enums.Key.End] :
			[Enums.Key.Left, Enums.Key.Right, Enums.Key.PageUp, Enums.Key.PageDown, Enums.Key.Home, Enums.Key.End]
		))
		{
			bSelected = !oSelected.checked();

			_.each(this.list(), function (oItem) {
				var Add = false;
				if (oItem === oResult || oSelected === oItem)
				{
					bInRange = !bInRange;
					Add = true;
				}

				if (bInRange || Add)
				{
					oItem.checked(bSelected);
					Add = false;
				}
			});
			
			if (bMultiply && oResult && (iEventKeyCode === Enums.Key.Up || iEventKeyCode === Enums.Key.Down))
			{
				oResult.checked(!oResult.checked());
			}
		}
	}	
};

/**
 * @param {number} iEventKeyCode
 * @param {boolean} bShiftKey
 */
CSelector.prototype.clickNewSelectPosition = function (iEventKeyCode, bShiftKey)
{
	var
		self = this,
		iTimeout = 0,
		oResult = null,
		oSelected = this.itemSelected()
	;

	oResult = this.getResultSelection(oSelected, iEventKeyCode);

	if (oResult)
	{
		if (bShiftKey)
		{
			this.shiftClickResult(oResult, oSelected, iEventKeyCode);
		}

		if (oResult && this.fBeforeSelectCallback)
		{
			this.fBeforeSelectCallback(oResult, function (bResult) {
				if (bResult)
				{
					self.itemSelected(oResult);

					iTimeout = 0 === self.iTimer ? 50 : 150;
					if (0 !== self.iTimer)
					{
						window.clearTimeout(self.iTimer);
					}

					self.iTimer = window.setTimeout(function () {
						self.iTimer = 0;
						self.onSelect(oResult, false);
					}, iTimeout);

					this.scrollToSelected();
				}
			});

			this.scrollToSelected();
		}
		else
		{
			this.itemSelected(oResult);

			iTimeout = 0 === this.iTimer ? 50 : 150;
			if (0 !== this.iTimer)
			{
				window.clearTimeout(this.iTimer);
			}

			this.iTimer = window.setTimeout(function () {
				self.iTimer = 0;
				self.onSelect(oResult);
			}, iTimeout);

			this.scrollToSelected();
		}
	}
	else if (oSelected)
	{
		if (bShiftKey && (-1 < Utils.inArray(iEventKeyCode, [this.KeyUp, this.KeyDown, this.KeyLeft, this.KeyRight,
			Enums.Key.PageUp, Enums.Key.PageDown, Enums.Key.Home, Enums.Key.End])))
		{
			oSelected.checked(!oSelected.checked());
		}
	}
};

/**
 * @param {Object} oEvent
 * 
 * @return {boolean}
 */
CSelector.prototype.onKeydown = function (oEvent)
{
	var
		bResult = true,
		iCode = 0
	;

	if (this.useKeyboardKeys() && oEvent && !Utils.isTextFieldFocused() && !App.Screens.hasOpenedMaximizedPopups())
	{
		iCode = oEvent.keyCode;
		if (!oEvent.ctrlKey &&
			(
				this.KeyUp === iCode || this.KeyDown === iCode ||
				this.KeyLeft === iCode || this.KeyRight === iCode ||
				Enums.Key.PageUp === iCode || Enums.Key.PageDown === iCode ||
				Enums.Key.Home === iCode || Enums.Key.End === iCode
			)
		)
		{
			this.clickNewSelectPosition(iCode, oEvent.shiftKey);
			bResult = false;
		}
		else if (Enums.Key.Del === iCode && !oEvent.ctrlKey && !oEvent.shiftKey)
		{
			if (0 < this.list().length)
			{
				this.onDelete();
				bResult = false;
			}
		}
		else if (Enums.Key.Enter === iCode)
		{
			if (0 < this.list().length && !oEvent.ctrlKey)
			{
				this.onEnter(this.itemSelected());
				bResult = false;
			}
		}
		else if (oEvent.ctrlKey && !oEvent.altKey && !oEvent.shiftKey && Enums.Key.a === iCode)
		{
			this.checkAll(!(this.checkAll() && !this.isIncompleteChecked()));
			bResult = false;
		}
	}

	return bResult;
};

CSelector.prototype.onDelete = function ()
{
	this.fDeleteCallback.call(this, this.listCheckedOrSelected());
};

/**
 * @param {Object} oItem
 */
CSelector.prototype.onEnter = function (oItem)
{
	var self = this;
	if (oItem && this.fBeforeSelectCallback)
	{
		this.fBeforeSelectCallback(oItem, function (bResult) {
			if (bResult)
			{
				self.itemSelected(oItem);
				self.fEnterCallback.call(this, oItem);
			}
		});
	}
	else
	{
		this.itemSelected(oItem);
		this.fEnterCallback.call(this, oItem);
	}
};

/**
 * @param {Object} oItem
 */
CSelector.prototype.selectionFunc = function (oItem)
{
	this.itemSelected(null);
	if (this.bResetCheckedOnClick)
	{
		this.listChecked(false);
	}

	this.itemSelected(oItem);
	this.fSelectCallback.call(this, oItem);
};

/**
 * @param {Object} oItem
 * @param {boolean=} bCheckBefore = true
 */
CSelector.prototype.onSelect = function (oItem, bCheckBefore)
{
	bCheckBefore = Utils.isUnd(bCheckBefore) ? true : !!bCheckBefore;
	if (this.fBeforeSelectCallback && bCheckBefore)
	{
		var self = this;
		this.fBeforeSelectCallback(oItem, function (bResult) {
			if (bResult)
			{
				self.selectionFunc(oItem);
			}
		});
	}
	else
	{
		this.selectionFunc(oItem);
	}
};

/**
 * @param {Object} oItem
 */
CSelector.prototype.onDblClick = function (oItem)
{
	this.fDblClickCallback.call(this, oItem);
};

CSelector.prototype.koCheckAll = function ()
{
	return ko.computed({
		'read': this.checkAll,
		'write': this.checkAll,
		'owner': this
	});
};

CSelector.prototype.koCheckAllIncomplete = function ()
{
	return ko.computed({
		'read': this.isIncompleteChecked,
		'write': this.isIncompleteChecked,
		'owner': this
	});
};

/**
 * @return {boolean}
 */
CSelector.prototype.scrollToSelected = function ()
{
	if (!this.oListScope || !this.oScrollScope)
	{
		return false;
	}

	var
		iOffset = 20,
		oSelected = $(this.sSelectabelSelector, this.oScrollScope),
		oPos = oSelected.position(),
		iVisibleHeight = this.oScrollScope.height(),
		iSelectedHeight = oSelected.outerHeight()
	;

	if (oPos && (oPos.top < 0 || oPos.top + iSelectedHeight > iVisibleHeight))
	{
		if (oPos.top < 0)
		{
			this.oScrollScope.scrollTop(this.oScrollScope.scrollTop() + oPos.top - iOffset);
		}
		else
		{
			this.oScrollScope.scrollTop(this.oScrollScope.scrollTop() + oPos.top - iVisibleHeight + iSelectedHeight + iOffset);
		}

		return true;
	}

	return false;
};

(function ($) {
 
 /**
  * @param {{name:string,resizeFunc:Function}} args
  */
 $.fn.splitter = function(args) {

	args = args || {};

	return this.each(function () {
		
		var
			bIsMouseSplit = false,
			bCollapse = args.collapse ? args.collapse : false,
			storageKey = args.name,
			initPosition = 0,
			nSize = 0,
			nPreviousNewPosition = 0,
			oLastState = {},
			oLastStateReserve = {},
			startSplitMouse = function (e) {
				bIsMouseSplit = true;
				bar.addClass(opts['activeClass']);

				opts['_posSplit'] = -((rtl ? splitter._overallWidth - e[opts['eventPos']] : e[opts['eventPos']]) - panes.get(0)[opts['pxSplit']] );
				
				$('body')
					.attr({'unselectable': "on"})
					.addClass('unselectable');

				$(document)
					.bind('mousemove', doSplitMouse)
					.bind('mouseup', endSplitMouse);
			},
			doSplitMouse = function (e) {
				var newPos = (rtl ? splitter._overallWidth - e[opts['eventPos']] : e[opts['eventPos']]) + opts['_posSplit'];
				resplit(newPos);
				
				if (Utils.isFunc(args.resizeFunc))
				{
					args.resizeFunc();
				}
			},
			endSplitMouse = function endSplitMouse(e) {
				bar.removeClass(opts['activeClass']);

				$('body')
					.attr({'unselectable': 'off'})
					.removeClass('unselectable');

				// Store 'width' data
				if (storageKey)
				{
					App.Storage.setData(storageKey + 'ResizerWidth', panes.get(0)[opts['pxSplit']]);
				}

				$(document)
					.unbind('mousemove', doSplitMouse)
					.unbind('mouseup', endSplitMouse);
				
				if (Utils.isFunc(args.resizeFunc))
				{
					args.resizeFunc();
				}
			},
			resplit = function (newPosition, bIgnoreSizeLimits) {

				var iLeftMin = panes.get(0)._min;

				nPreviousNewPosition = newPosition;
				if (bCollapse && (iLeftMin - newPosition) > 150) //Collapse
				{
					newPosition = 5;
					bIgnoreSizeLimits = true;
				}

				if (!bIgnoreSizeLimits) { //Constrain new splitbar position to fit pane size limits
					newPosition = window.Math.max(
						iLeftMin,
						splitter._overallWidth - panes.get(1)._max,
						window.Math.min(
							newPosition,
							panes.get(0)._max,
							splitter._overallWidth - panes.get(1)._min
						)
					);
				}

				panes.get(0).$.css(opts['split'], newPosition);
				panes.get(1).$.css(opts['split'], splitter._overallWidth - newPosition);
				
				if (!App.browser.ie8AndBelow)
				{
					panes.trigger('resize');
				}
			},
			dimSum = function (elem, dims) {
				// Opera returns -1 for missing min/max width, turn into 0
				var sum = 0, i = 1;
				for (; i < arguments.length; i++)
				{
					sum += window.Math.max(window.parseInt(elem.css(arguments[i]), 10) || 0, 0);
				}
				
				return sum;
			},
			vh = (args.splitHorizontal ? 'h' : args.splitVertical ? 'v' : args.type) || 'v',
			opts = $.extend({
				'activeClass': 'active',	// class name for active splitter
				'pxPerKey': 8,				// splitter px moved per keypress
				'tabIndex': 0,				// tab order indicator
				'accessKey': ''				// accessKey for splitbar
			},{
				v: {						// Vertical splitters:
					'keyLeft': 39, 'keyRight': 37,
					'type': 'v', 'eventPos': "pageX", 'origin': "left",
					'split': "width",  'pxSplit': "offsetWidth",  'side1': "Left", 'side2': "Right",
					'fixed': "height", 'pxFixed': "offsetHeight", 'side3': "Top",  'side4': "Bottom"
				},
				h: {						// Horizontal splitters:
					'keyTop': 40, 'keyBottom': 38,
					'type': 'h', 'eventPos': "pageY", 'origin': "top",
					'split': "height", 'pxSplit': "offsetHeight", 'side1': "Top",  'side2': "Bottom",
					'fixed': "width",  'pxFixed': "offsetWidth",  'side3': "Left", 'side4': "Right"
				}
			}[vh], args),
			
			splitter = $(this),
			panes = $(">*:not(css3pie)", splitter).each(function(){this.$ = $(this);}),
			bar = $('.resize_handler', panes.get(0))
				.attr({'unselectable': 'on'})
				.bind('mousedown', startSplitMouse),
			rtl = splitter.css('direction') === 'rtl'
		;

		panes.get(0)._paneName = opts['side1'];
		panes.get(1)._paneName = opts['side2'];
		
		panes.each(function(){
			this._min = opts['min' + this._paneName] || dimSum(this.$, 'min-' + opts['split']);
			this._max = opts['max' + this._paneName] || dimSum(this.$, 'max-' + opts['split']) || 9999;
			this._init = opts['size' + this._paneName] === undefined ?
				window.parseInt($.css(this, opts['split']), 10) : opts['size' + this._paneName];
		});

		// Determine initial position, get from cookie if specified
		if (storageKey)
		{
			initPosition = App.Storage.getData(storageKey + 'ResizerWidth') || panes.get(0)._init;
		}
		else
		{
			initPosition = panes.get(0)._init;
		}

		if (isNaN(initPosition))
		{
			initPosition = splitter[0][opts['pxSplit']];
			initPosition = window.Math.round(initPosition / panes.length);
		}
		// Resize event propagation and splitter sizing
		if (opts['resizeToWidth'] && !(App.browser.ie8AndBelow))
		{
			$(window).bind('resize', function(e) {
				if (e.target !== this)
				{
					return;
				}
				splitter.trigger('resize'); 
			});
		}

		splitter.bind('resize', function (ev, size, sCommand, bIgnoreSizeLimits, bMaximizeOnly) {

			var tKey = ev.target.className + '_' + sCommand;
			if (bIsMouseSplit)
			{
				oLastState = {};
			}

			// Custom events bubble in jQuery 1.3; don't get into a Yo Dawg
			if (ev.target !== this)
			{
				return;
			}

			// Determine new width/height of splitter container
			splitter._overallWidth = splitter[0][opts['pxSplit']];

			// Return if splitter isn't visible or content isn't there yet
			if (splitter._overallWidth <= 0)
			{
				return;
			}

			if (!(opts['sizeRight'] || opts['sizeBottom']))
			{
				nSize = panes.get(0)[opts['pxSplit']];
			}
			else
			{
				nSize = splitter._overallWidth - panes.get(1)[opts['pxSplit']];
			}

			if (isNaN(size))
			{
				size = nSize;
			}
			else if (sCommand)
			{
				if(bMaximizeOnly)
				{
					size = oLastState[tKey] ? oLastState[tKey] : nSize;
				}
				else
				{
					bIsMouseSplit = false;

					if (oLastState[tKey])
					{
						size = oLastState[tKey];
						oLastState[tKey] = null;
					}
					else
					{
						if (size === nSize)
						{
							oLastState[tKey] = null;
							size = oLastStateReserve[tKey];
						}
						else
						{
							oLastState[tKey] = oLastStateReserve[tKey] = nSize;
						}

						_.each(oLastState, function(num, key) {
							if (key !== tKey)
							{
								oLastState[key] = null;
							}
						});
					}
				}
			}

			resplit(size, bIgnoreSizeLimits);
			
		}).trigger('resize', [initPosition]);
	});
};

})(jQuery);
(function ($) {

/**
 * extend jQuery autocomplete
 */

	// styling results
	$.ui.autocomplete.prototype._renderItem = function (ul, item) {

		/*item.label = Utils.trim(item.label);

		item.label = item.label.replace(/\([^\)]+\)$/i, function (sMatch) {
			return '~~1~~' + sMatch + '~~2~~';
		});

		item.label = item.label.replace(/<[^>]+>$/i, function (sMatch) {
			return '~~1~~' + sMatch + '~~2~~';
		});

		//item.label = Utils.encodeHtml(item.label);

		item.label = item.label
			.replace(/~~1~~/, '<span class="email" style="opacity: 0.5">')
			.replace(/~~2~~/, '</span>')
		;*/
		var aEmail = item.label.match(/[a-zA-Z0-9.\-_]+@[a-zA-Z0-9.]+/g);
		if (aEmail) {
			item.label = item.label.replace('<' + aEmail[0] + '>', "<span style='opacity: 0.5'>" + '&lt;' + aEmail[0] + '&gt' + "</span>"); //highlight <email>
		}

		return $('<li>')
			.append('<a>' + item.label + (item.global ? '' : '<span class="del"></span>') + '</a>')
			//.append(item.global ? null : '<span class="del"></span>')
			.appendTo(ul);
	};

	// add categories
	$.ui.autocomplete.prototype._renderMenu = function(ul, items) {
		
		var
			self = this,
			currentCategory = ''
		;

		$.each(items, function(index, item) {
			
			if (item && item.category && item.category !== currentCategory) {
				currentCategory = item.category;
				ul.append('<li class="ui-autocomplete-category">' + item.category + '</li>');
			}

			self._renderItemData(ul, item);
		});
	};

	// Prevent blur then you reach last/first element in list of suggestions
	$.ui.autocomplete.prototype._move = function(direction, event) {

		if ( !this.menu.element.is( ":visible" ) ) {
			this.search( null, event );
			return;
		}
		if ( this.menu.isFirstItem() && /^previous/.test( direction ))
		{
			this._value( this.term );
			this.menu._move( "first", "first", event );
		}
		else if ( this.menu.isLastItem() && /^next/.test( direction ))
		{
			this._value( this.term );
			this.menu._move( "last", "last", event );
		}

		this.menu[ direction ]( event );
	};
})(jQuery);

/**
 * @constructor
 */
function CApi()
{
	this.openPgp = null;
	this.openPgpCallbacks = [];
}

CApi.prototype.composeMessage = function ()
{
	App.Routing.setHash([Enums.Screens.Compose]);
};

/**
 * @param {string} sFolder
 * @param {string} sUid
 */
CApi.prototype.composeMessageFromDrafts = function (sFolder, sUid)
{
	var aParams = App.Links.composeFromMessage('drafts', sFolder, sUid);
	App.Routing.setHash(aParams);
};

/**
 * @param {string} sReplyType
 * @param {string} sFolder
 * @param {string} sUid
 */
CApi.prototype.composeMessageAsReplyOrForward = function (sReplyType, sFolder, sUid)
{
	var aParams = App.Links.composeFromMessage(sReplyType, sFolder, sUid);
	App.Routing.setHash(aParams);
};

/**
 * @param {string} sToAddresses
 */
CApi.prototype.composeMessageToAddresses = function (sToAddresses)
{
	var aParams = App.Links.composeWithToField(sToAddresses);
	App.Routing.setHash(aParams);
};

/**
 * @param {Object} oVcard
 */
CApi.prototype.composeMessageWithVcard = function (oVcard)
{
	var aParams = ['vcard', oVcard];
	App.Routing.goDirectly(App.Links.compose(), aParams);
};

/**
 * @param {string} sArmor
 * @param {string} sDownloadLinkFilename
 */
CApi.prototype.composeMessageWithPgpKey = function (sArmor, sDownloadLinkFilename)
{
	var aParams = ['data-as-file', sArmor, sDownloadLinkFilename];
	App.Routing.goDirectly(App.Links.compose(), aParams);
};

/**
 * @param {Array} aFileItems
 */
CApi.prototype.composeMessageWithFiles = function (aFileItems)
{
	var aParams = ['file', aFileItems];
	App.Routing.goDirectly(App.Links.compose(), aParams);
};

CApi.prototype.closeComposePopup = function ()
{
	//function is overrided in mail module
};

/**
 * @param {string} sEmail
 */
CApi.prototype.createMailAccount = function (sEmail)
{
	//function is overrided in mail module
};

CApi.prototype.showChangeDefaultAccountPasswordPopup = function ()
{
	//function is overrided in mail module
};

/**
 * @param {Function=} fAfterConfigureMail
 */
CApi.prototype.showConfigureMailPopup = function (fAfterConfigureMail)
{
	//function is overrided in mail module
};

/**
 * Downloads by url through iframe or new window.
 *
 * @param {string} sUrl
 */
CApi.prototype.downloadByUrl = function (sUrl)
{
	var oIframe = null;
	
	if (bMobileDevice)
	{
		window.open(sUrl);
	}
	else
	{
		oIframe = $('<iframe style="display: none;"></iframe>').appendTo(document.body);
		
		oIframe.attr('src', sUrl);
		
		setTimeout(function () {
			oIframe.remove();
		}, 60000);
	}
};

/**
 * @return {boolean}
 */
CApi.prototype.isPgpSupported = function ()
{
	return !!(window.crypto && window.crypto.getRandomValues);
};

/**
 * @param {Function} fCallback
 * @param {mixed=} sUserUid
 */
CApi.prototype.pgp = function (fCallback, sUserUid)
{
	if (Utils.isFunc(fCallback))
	{
		if (this.openPgp)
		{
			fCallback(this.openPgp);
		}
		else if (this.isPgpSupported())
		{
			if (null !== this.openPgpCallbacks)
			{
				this.openPgpCallbacks.push(fCallback);
			}
			else
			{
				fCallback(false);
			}
			
			var self = this;
			if (!this.openPgpRequest)
			{
				this.openPgpRequest = true;
				
				$.ajax({
					'url': 'static/js/openpgp.js',
					'dataType': 'script',
					'cache': true,
					'complete': function () {
						
						self.openPgp = window.openpgp ? new OpenPgp(window.openpgp, 'user_' + (sUserUid || '0') + '_') : false;

						if (null !== self.openPgpCallbacks)
						{
							_.each(self.openPgpCallbacks, function (fItemCallback) {
								fItemCallback(self.openPgp);
							});
						}

						self.openPgpCallbacks = null;
					}
				});
			}
		}
		else
		{
			fCallback(false);
		}
	}
};

/**
 * @param {string} sLoading
 */
CApi.prototype.showLoading = function (sLoading)
{
	App.Screens.showLoading(sLoading);
};

CApi.prototype.hideLoading = function ()
{
	App.Screens.hideLoading();
};

/**
 * @param {string} sReport
 * @param {number=} iDelay if 0 comes then report will not be closed automatically
 */
CApi.prototype.showReport = function (sReport, iDelay)
{
	App.Screens.showReport(sReport, iDelay);
};

/**
 * @param {string} sError
 * @param {boolean=} bHtml = false
 * @param {boolean=} bNotHide = false
 * @param {boolean=} bGray = false
 */
CApi.prototype.showError = function (sError, bHtml, bNotHide, bGray)
{
	App.Screens.showError(sError, bHtml, bNotHide, bGray);
};

/**
 * @param {boolean=} bGray = false
 */
CApi.prototype.hideError = function (bGray)
{
	App.Screens.hideError(bGray);
};

/**
 * @param {Object} oRes
 * @param {string} sPgpAction
 * @param {string=} sDefaultError
 */
CApi.prototype.showPgpErrorByCode = function (oRes, sPgpAction, sDefaultError)
{
	var
		aErrors = Utils.isNonEmptyArray(oRes.errors) ? oRes.errors : [],
		aNotices = Utils.isNonEmptyArray(oRes.notices) ? oRes.notices : [],
		aEmailsWithoutPublicKey = [],
		aEmailsWithoutPrivateKey = [],
		sError = '',
		bNoSignDataNotice = false,
		bNotice = true
	;
	
	_.each(_.union(aErrors, aNotices), function (aError) {
		if (aError.length === 2)
		{
			switch(aError[0])
			{
				case OpenPgpResult.Enum.GenerateKeyError:
					sError = Utils.i18n('OPENPGP/ERROR_GENERATE_KEY');
					break;
				case OpenPgpResult.Enum.ImportKeyError:
					sError = Utils.i18n('OPENPGP/ERROR_IMPORT_KEY');
					break;
				case OpenPgpResult.Enum.ImportNoKeysFoundError:
					sError = Utils.i18n('OPENPGP/ERROR_IMPORT_NO_KEY_FOUND');
					break;
				case OpenPgpResult.Enum.PrivateKeyNotFoundError:
				case OpenPgpResult.Enum.PrivateKeyNotFoundNotice:
					aEmailsWithoutPrivateKey.push(aError[1]);
					break;
				case OpenPgpResult.Enum.PublicKeyNotFoundError:
					bNotice = false;
					aEmailsWithoutPublicKey.push(aError[1]);
					break;
				case OpenPgpResult.Enum.PublicKeyNotFoundNotice:
					aEmailsWithoutPublicKey.push(aError[1]);
					break;
				case OpenPgpResult.Enum.KeyIsNotDecodedError:
					if (sPgpAction === Enums.PgpAction.DecryptVerify)
					{
						sError = Utils.i18n('OPENPGP/ERROR_DECRYPT') + ' ' + Utils.i18n('OPENPGP/ERROR_KEY_NOT_DECODED', {'USER': aError[1]});
					}
					else if (sPgpAction === Enums.PgpAction.Sign || sPgpAction === Enums.PgpAction.EncryptSign)
					{
						sError = Utils.i18n('OPENPGP/ERROR_SIGN') + ' ' + Utils.i18n('OPENPGP/ERROR_KEY_NOT_DECODED', {'USER': aError[1]});
					}
					break;
				case OpenPgpResult.Enum.SignError:
					sError = Utils.i18n('OPENPGP/ERROR_SIGN');
					break;
				case OpenPgpResult.Enum.VerifyError:
					sError = Utils.i18n('OPENPGP/ERROR_VERIFY');
					break;
				case OpenPgpResult.Enum.EncryptError:
					sError = Utils.i18n('OPENPGP/ERROR_ENCRYPT');
					break;
				case OpenPgpResult.Enum.DecryptError:
					sError = Utils.i18n('OPENPGP/ERROR_DECRYPT');
					break;
				case OpenPgpResult.Enum.SignAndEncryptError:
					sError = Utils.i18n('OPENPGP/ERROR_ENCRYPT_OR_SIGN');
					break;
				case OpenPgpResult.Enum.VerifyAndDecryptError:
					sError = Utils.i18n('OPENPGP/ERROR_DECRYPT_OR_VERIFY');
					break;
				case OpenPgpResult.Enum.DeleteError:
					sError = Utils.i18n('OPENPGP/ERROR_DELETE_KEY');
					break;
				case OpenPgpResult.Enum.VerifyErrorNotice:
					sError = Utils.i18n('OPENPGP/ERROR_VERIFY');
					break;
				case OpenPgpResult.Enum.NoSignDataNotice:
					bNoSignDataNotice = true;
					break;
			}
		}
	});
	
	if (aEmailsWithoutPublicKey.length > 0)
	{
		aEmailsWithoutPublicKey = _.without(aEmailsWithoutPublicKey, '');
		if (aEmailsWithoutPublicKey.length > 0)
		{
			sError = Utils.i18n('OPENPGP/ERROR_NO_PUBLIC_KEYS_FOR_USERS_PLURAL', 
					{'USERS': aEmailsWithoutPublicKey.join(', ')}, null, aEmailsWithoutPublicKey.length);
		}
		else if (sPgpAction === Enums.PgpAction.Verify)
		{
			sError = Utils.i18n('OPENPGP/ERROR_NO_PUBLIC_KEY_FOUND_FOR_VERIFY');
		}
		if (bNotice && sError !== '')
		{
			sError += ' ' + Utils.i18n('OPENPGP/ERROR_MESSAGE_WAS_NOT_VERIFIED');
		}
	}
	else if (aEmailsWithoutPrivateKey.length > 0)
	{
		aEmailsWithoutPrivateKey = _.without(aEmailsWithoutPrivateKey, '');
		if (aEmailsWithoutPrivateKey.length > 0)
		{
			sError = Utils.i18n('OPENPGP/ERROR_NO_PRIVATE_KEYS_FOR_USERS_PLURAL', 
					{'USERS': aEmailsWithoutPrivateKey.join(', ')}, null, aEmailsWithoutPrivateKey.length);
		}
		else if (sPgpAction === Enums.PgpAction.DecryptVerify)
		{
			sError = Utils.i18n('OPENPGP/ERROR_NO_PRIVATE_KEY_FOUND_FOR_DECRYPT');
		}
	}
	
	if (sError === '' && !bNoSignDataNotice)
	{
		switch (sPgpAction)
		{
			case Enums.PgpAction.Generate:
				sError = Utils.i18n('OPENPGP/ERROR_GENERATE_KEY');
				break;
			case Enums.PgpAction.Import:
				sError = Utils.i18n('OPENPGP/ERROR_IMPORT_KEY');
				break;
			case Enums.PgpAction.DecryptVerify:
				sError = Utils.i18n('OPENPGP/ERROR_DECRYPT');
				break;
			case Enums.PgpAction.Verify:
				sError = Utils.i18n('OPENPGP/ERROR_VERIFY');
				break;
			case Enums.PgpAction.Encrypt:
				sError = Utils.i18n('OPENPGP/ERROR_ENCRYPT');
				break;
			case Enums.PgpAction.EncryptSign:
				sError = Utils.i18n('OPENPGP/ERROR_ENCRYPT_OR_SIGN');
				break;
			case Enums.PgpAction.Sign:
				sError = Utils.i18n('OPENPGP/ERROR_SIGN');
				break;
		}
		sError = sDefaultError;
	}
	
	if (sError !== '')
	{
		App.Api.showError(sError);
	}
	
	return bNoSignDataNotice;
};

/**
 * @param {Object} oResponse
 * @param {string=} sDefaultError
 * @param {boolean=} bNotHide = false
 */
CApi.prototype.showErrorByCode = function (oResponse, sDefaultError, bNotHide)
{
	var
		iErrorCode = oResponse.ErrorCode,
		sResponseError = oResponse.ErrorMessage || '',
		sResultError = ''
	;
	
	switch (iErrorCode)
	{
		default:
			sResultError = sDefaultError;
			break;
		case Enums.Errors.AuthError:
			sResultError = Utils.i18n('WARNING/LOGIN_PASS_INCORRECT');
			break;
		case Enums.Errors.DataBaseError:
			sResultError = Utils.i18n('WARNING/DATABASE_ERROR');
			break;
		case Enums.Errors.LicenseProblem:
			sResultError = Utils.i18n('WARNING/INVALID_LICENSE');
			break;
		case Enums.Errors.DemoLimitations:
			sResultError = Utils.i18n('DEMO/WARNING_THIS_FEATURE_IS_DISABLED');
			break;
		case Enums.Errors.Captcha:
			sResultError = Utils.i18n('WARNING/CAPTCHA_IS_INCORRECT');
			break;
		case Enums.Errors.CanNotGetMessage:
			sResultError = Utils.i18n('MESSAGE/ERROR_MESSAGE_DELETED');
			break;
		case Enums.Errors.NoRequestedMailbox:
			sResultError = sDefaultError + ' ' + Utils.i18n('COMPOSE/ERROR_INVALID_ADDRESS', {'ADDRESS': (oResponse.Mailbox || '')});
			break;
		case Enums.Errors.CanNotChangePassword:
			sResultError = Utils.i18n('WARNING/UNABLE_CHANGE_PASSWORD');
			break;
		case Enums.Errors.AccountOldPasswordNotCorrect:
			sResultError = Utils.i18n('WARNING/CURRENT_PASSWORD_NOT_CORRECT');
			break;
		case Enums.Errors.FetcherIncServerNotAvailable:
			sResultError = Utils.i18n('WARNING/FETCHER_SAVE_ERROR');
			break;
		case Enums.Errors.FetcherLoginNotCorrect:
			sResultError = Utils.i18n('WARNING/FETCHER_SAVE_ERROR');
			break;
		case Enums.Errors.HelpdeskUserNotExists:
			sResultError = Utils.i18n('HELPDESK/ERROR_FORGOT_NO_ACCOUNT');
			break;
		case Enums.Errors.MailServerError:
			sResultError = Utils.i18n('WARNING/CANT_CONNECT_TO_SERVER');
			break;
		case Enums.Errors.DataTransferFailed:
			sResultError = Utils.i18n('WARNING/DATA_TRANSFER_FAILED');
			break;
		case Enums.Errors.NotDisplayedError:
			sResultError = '';
			break;
	}
	
	if (sResultError !== '')
	{
		if (sResponseError !== '')
		{
			sResultError += ' (' + sResponseError + ')';
		}
		this.showError(sResultError, false, !!bNotHide);
	}
	else if (sResponseError !== '')
	{
		this.showError(sResponseError);
	}
};

/**
 * @param {string} sFileName
 * @param {number} iSize
 * @returns {Boolean}
 */
CApi.prototype.showErrorIfAttachmentSizeLimit = function (sFileName, iSize)
{
	var
		sWarning = Utils.i18n('COMPOSE/UPLOAD_ERROR_FILENAME_SIZE', {
			'FILENAME': sFileName,
			'MAXSIZE': Utils.friendlySize(AppData.App.AttachmentSizeLimit)
		})
	;
	
	if (AppData.App.AttachmentSizeLimit > 0 && iSize > AppData.App.AttachmentSizeLimit)
	{
		App.Screens.showPopup(AlertPopup, [sWarning]);
		return true;
	}
	
	return false;
};

/**
 * Moves the specified messages in the current folder to the Trash or delete permanently 
 * if the current folder is Trash or Spam.
 * 
 * @param {Array} aUids
 * @param {Object} oApp
 * @param {Function=} fAfterDelete
 */
CApi.prototype.deleteMessages = function (aUids, oApp, fAfterDelete)
{
	if (!Utils.isFunc(fAfterDelete))
	{
		fAfterDelete = function () {};
	}
	
	var
		oFolderList = App.MailCache.folderList(),
		sCurrFolder = oFolderList.currentFolderFullName(),
		oTrash = oFolderList.trashFolder(),
		bInTrash =(oTrash && sCurrFolder === oTrash.fullName()),
		oSpam = oFolderList.spamFolder(),
		bInSpam = (oSpam && sCurrFolder === oSpam.fullName()),
		fDeleteMessages = function (bResult) {
			if (bResult)
			{
				oApp.MailCache.deleteMessages(aUids);
				fAfterDelete();
			}
		}
	;
	
	if (bInSpam)
	{
		oApp.MailCache.deleteMessages(aUids);
		fAfterDelete();
	}
	else if (bInTrash)
	{
		App.Screens.showPopup(ConfirmPopup, [Utils.i18n('MAILBOX/CONFIRM_MESSAGES_DELETE'), fDeleteMessages]);
	}
	else if (oTrash)
	{
		oApp.MailCache.moveMessagesToFolder(oTrash.fullName(), aUids);
		fAfterDelete();
	}
	else if (!oTrash)
	{
		App.Screens.showPopup(ConfirmPopup, [Utils.i18n('MAILBOX/CONFIRM_MESSAGES_DELETE_NO_TRASH_FOLDER'), fDeleteMessages]);
	}
};

CApi.prototype.contactCreate = function (sName, sEmail, fContactCreateResponse, oContactCreateContext)
{
	//function is overrided in contacts module
};


/**
 * @param {string} sName
 * @param {string} sHeaderTitle
 * @param {string} sDocumentTitle
 * @param {string} sTemplateName
 * @param {Object} oViewModelClass
 */
AfterLogicApi.addScreenToHeader = function (sName, sHeaderTitle, sDocumentTitle, sTemplateName, oViewModelClass)
{
	App.addScreenToHeader(sName, sHeaderTitle, sDocumentTitle, sTemplateName, oViewModelClass, true);
};

/**
 * @param {string} sNewDefaultTab
 */
AfterLogicApi.setDefaultTab = function (sNewDefaultTab)
{
	var bDefaultTabInEnum = !!_.find(Enums.Screens, function (sScreenInEnum) {
		return sScreenInEnum === sNewDefaultTab;
	});
	
	if (bDefaultTabInEnum)
	{
		AppData.App.DefaultTab = sNewDefaultTab;
	}
};

AfterLogicApi.aSettingsTabs = [];

/**
 * @param {Object} oViewModelClass
 */
AfterLogicApi.addSettingsTab = function (oViewModelClass)
{
	if (oViewModelClass.prototype.TabName)
	{
		Enums.SettingsTab[oViewModelClass.prototype.TabName] = oViewModelClass.prototype.TabName;
		AfterLogicApi.aSettingsTabs.push(oViewModelClass);
	}
};

/**
 * @return {Array}
 */
AfterLogicApi.getPluginsSettingsTabs = function ()
{
	return AfterLogicApi.aSettingsTabs;
};

/**
 * @param {string} sSettingName
 * 
 * @return {string}
 */
AfterLogicApi.getSetting = function (sSettingName)
{
	return AppData.App[sSettingName];
};

/**
 * @param {string} sPluginName
 * 
 * @return {string|null}
 */
AfterLogicApi.getPluginSettings = function (sPluginName)
{
	if (AppData && AppData.Plugins)
	{
		return AppData.Plugins[sPluginName];
	}
	
	return null;
};

AfterLogicApi.oPluginHooks = {};

/**
 * @param {string} sName
 * @param {Function} fCallback
 */
AfterLogicApi.addPluginHook = function (sName, fCallback)
{
	if (Utils.isFunc(fCallback))
	{
		if (!$.isArray(this.oPluginHooks[sName]))
		{
			this.oPluginHooks[sName] = [];
		}
		
		this.oPluginHooks[sName].push(fCallback);
	}
};

/**
 * @param {string} sName
 * @param {Array=} aArguments
 */
AfterLogicApi.runPluginHook = function (sName, aArguments)
{
	if ($.isArray(this.oPluginHooks[sName]))
	{
		aArguments = aArguments || [];
		
		_.each(this.oPluginHooks[sName], function (fCallback) {
			fCallback.apply(null, aArguments);
		});
	}
};

/**
 * @param {Object} oParameters
 * @param {Function=} fResponseHandler
 * @param {Object=} oContext
 */
AfterLogicApi.sendAjaxRequest = function (oParameters, fResponseHandler, oContext)
{
	App.Ajax.send(oParameters, fResponseHandler, oContext);
};

/**
 * @param {string} sKey
 * @param {?Object=} oValueList
 * @param {?string=} sDefaulValue
 * @param {number=} nPluralCount
 * 
 * @return {string}
 */
AfterLogicApi.i18n = Utils.i18n;

/**
 * @param {string} sRecipients
 * 
 * @return {Array}
 */
AfterLogicApi.getArrayRecipients = Utils.Address.getArrayRecipients;

/**
 * @param {string} sFullEmail
 * 
 * @return {Object}
 */
AfterLogicApi.getEmailParts = Utils.Address.getEmailParts;

/**
 * @param {string} sFullEmail
 *
 * @return {Object}
 */
AfterLogicApi.isCorrectEmail = Utils.Address.isCorrectEmail;

/**
* @param {string} sAlert
*/
AfterLogicApi.showAlertPopup = function (sAlert)
{
	App.Screens.showPopup(AlertPopup, [sAlert]);
};

/**
* @param {string} sConfirm
* @param {Function} fConfirmCallback
*/
AfterLogicApi.showConfirmPopup = function (sConfirm, fConfirmCallback)
{
	App.Screens.showPopup(ConfirmPopup, [sConfirm, fConfirmCallback]);
};

AfterLogicApi.showPopup = function (sName, aParams)
{
	App.Screens.showPopup(sName, aParams);
};

/**
* @param {string} sReport
* @param {number=} iDelay if 0 comes then report will not be closed automatically
*/
AfterLogicApi.showReport = function(sReport, iDelay)
{
	App.Screens.showReport(sReport, iDelay);
};

/**
* @param {string} sError
*/
AfterLogicApi.showError = function(sError)
{
	App.Screens.showError(sError);
};

AfterLogicApi.getPrimaryAccountData = function()
{
	var oDefault = AppData.Accounts.getDefault();
	
	return {
		'Id': oDefault.id(),
		'Email': oDefault.email(),
		'FriendlyName': oDefault.friendlyName()
	};
};

AfterLogicApi.getCurrentAccountData = function()
{
	var oDefault = AppData.Accounts.getCurrent();
	
	return {
		'Id': oDefault.id(),
		'Email': oDefault.email(),
		'FriendlyName': oDefault.friendlyName()
	};
};

/**
 * @return {boolean}
 */
AfterLogicApi.isMobile = function ()
{
	return this.getAppDataItem('IsMobile');
};

/**
 * @param {string} sParamName
 * 
 * @return {string|null}
 */

AfterLogicApi.getRequestParam = Utils.Common.getRequestParam;

AfterLogicApi.editedFolderList = function ()
{
	return App.MailCache.editedFolderList;
};

AfterLogicApi.FileSizeLimit = AppData.App.FileSizeLimit;

AfterLogicApi.isFunc = Utils.isFunc;
AfterLogicApi.isUnd = Utils.isUnd;
AfterLogicApi.emptyFunction = Utils.emptyFunction;

/**
 * @param {string} sItemName
 * 
 * @return {string}
 */
AfterLogicApi.getAppDataItem = function (sItemName)
{
	if (AppData && AppData[sItemName])
	{
		return AppData[sItemName];
	}
	
	return null;	
};

AfterLogicApi.WindowOpener = Utils.WindowOpener;

AfterLogicApi.getAppPath = Utils.Common.getAppPath;

AfterLogicApi.loadScript = Utils.loadScript;

/* jshint ignore:start */		
AfterLogicApi.createObjectInstance = function (sClassName)
{
	var 
		oReg = new RegExp('^[a-zA-Z]+$')
	; 
	if(oReg.test(sClassName))
	{
		return eval('new ' + sClassName + '()');
	}		
	
	return null;
};
/* jshint ignore:end */

/**
 * Object for saving and restoring data in local storage or cookies.
 * 
 * @constructor
 */
function CStorage()
{
	this.bHtml5 = true;
	
	this.init();
}

/**
 * Returns **true** if data with specified key exists in the storage.
 * 
 * @param {string} sKey
 * @returns {boolean}
 */
CStorage.prototype.hasData = function (sKey)
{
	var sValue = this.bHtml5 ? localStorage.getItem(sKey) : $.cookie(sKey);
	
	return !!sValue;
};

/**
 * Returns value of data with specified key from the storage.
 * 
 * @param {string} sKey
 * @returns {string|number|Object}
 */
CStorage.prototype.getData = function (sKey)
{
	var sValue = this.bHtml5 ? localStorage.getItem(sKey) : $.cookie(sKey);
	
	return $.parseJSON(sValue);
};

/**
 * Sets value of data with specified key to the storage.
 * 
 * @param {string} sKey
 * @param {string|number|Object} mValue
 */
CStorage.prototype.setData = function (sKey, mValue)
{
	var sValue = JSON.stringify(mValue);
	
	if (this.bHtml5)
	{
		localStorage.setItem(sKey, sValue);
	}
	else
	{
		$.cookie(sKey, sValue, { expires: 30 });
	}
};

/**
 * Removes data with specified key from the storage.
 * 
 * @param {srting} sKey
 */
CStorage.prototype.removeData = function (sKey)
{
	if (this.bHtml5)
	{
		localStorage.removeItem(sKey);
	}
	else
	{
		$.cookie(sKey, null);
	}
};

/**
 * Initializes the object for work with local storage or cookie.
 */
CStorage.prototype.init = function ()
{
	if (typeof Storage === 'undefined')
	{
		this.bHtml5 = false;
	}
	else
	{
		try
		{
			localStorage.setItem('check', 'val');
			localStorage.removeItem('check');
		}
		catch (err)
		{
			this.bHtml5 = false;
		}
	}
};


/**
 * @todo
 * @param {Object} oOpenPgp
 * @param {string=} sPrefix
 * @constructor
 */
function OpenPgp(oOpenPgp, sPrefix)
{
	this.pgp = oOpenPgp;
	this.pgpKeyring = new this.pgp.Keyring(new this.pgp.Keyring.localstore(sPrefix));
	
	this.keys = ko.observableArray([]);

	this.reloadKeysFromStorage();
}

OpenPgp.prototype.pgp = null;
OpenPgp.prototype.pgpKeyring = null;
OpenPgp.prototype.keys = [];

/**
 * @return {Array}
 */
OpenPgp.prototype.getKeys = function ()
{
	return this.keys();
};

/**
 * @return {mixed}
 */
OpenPgp.prototype.getKeysObservable = function ()
{
	return this.keys;
};

/**
 * @private
 */
OpenPgp.prototype.reloadKeysFromStorage = function ()
{
	var
		aKeys = [],
		oOpenpgpKeys = this.pgpKeyring.getAllKeys()
	;

	_.each(oOpenpgpKeys, function (oItem) {
		if (oItem && oItem.primaryKey)
		{
			aKeys.push(new OpenPgpKey(oItem));
		}
	});

	this.keys(aKeys);
};

/**
 * @private
 * @param {Array} aKeys
 * @return {Array}
 */
OpenPgp.prototype.convertToNativeKeys = function (aKeys)
{
	return _.map(aKeys, function (oItem) {
		return (oItem && oItem.pgpKey) ? oItem.pgpKey : oItem;
	});
};

/**
 * @private
 */
OpenPgp.prototype.cloneKey = function (oKey)
{
	var oPrivateKey = null;
	if (oKey)
	{
		oPrivateKey = this.pgp.key.readArmored(oKey.armor());
		if (oPrivateKey && !oPrivateKey.err && oPrivateKey.keys && oPrivateKey.keys[0])
		{
			oPrivateKey = oPrivateKey.keys[0];
			if (!oPrivateKey || !oPrivateKey.primaryKey)
			{
				oPrivateKey = null;
			}
		}
		else
		{
			oPrivateKey = null;
		}
	}

	return oPrivateKey;
};

/**
 * @private
 */
OpenPgp.prototype.decryptKeyHelper = function (oResult, oKey, sPassword, sKeyEmail)
{
	if (oKey)
	{
		try
		{
			oKey.decrypt(Utils.pString(sPassword));
			if (!oKey || !oKey.primaryKey || !oKey.primaryKey.isDecrypted)
			{
				oResult.addError(OpenPgpResult.Enum.KeyIsNotDecodedError, sKeyEmail || '');
			}
		}
		catch (e)
		{
			oResult.addExceptionMessage(e, OpenPgpResult.Enum.KeyIsNotDecodedError, sKeyEmail || '');
		}
	}
	else
	{
		oResult.addError(OpenPgpResult.Enum.KeyIsNotDecodedError, sKeyEmail || '');
	}
};

/**
 * @private
 */
OpenPgp.prototype.verifyMessageHelper = function (oResult, sFromEmail, oDecryptedMessage)
{
	var
		bResult = false,
		oValidKey = null,
		aVerifyResult = [],
		aVerifyKeysId = [],
		aPublicKeys = []
	;

	if (oDecryptedMessage && oDecryptedMessage.getSigningKeyIds)
	{
		aVerifyKeysId = oDecryptedMessage.getSigningKeyIds();
		if (aVerifyKeysId && 0 < aVerifyKeysId.length)
		{
			aPublicKeys = this.findKeysByEmails([sFromEmail], true);
			if (!aPublicKeys || 0 === aPublicKeys.length)
			{
				oResult.addNotice(OpenPgpResult.Enum.PublicKeyNotFoundNotice, sFromEmail);
			}
			else
			{
				aVerifyResult = [];
				try
				{
					aVerifyResult = oDecryptedMessage.verify(this.convertToNativeKeys(aPublicKeys));
				}
				catch (e)
				{
					oResult.addNotice(OpenPgpResult.Enum.VerifyErrorNotice, sFromEmail);
				}

				if (aVerifyResult && 0 < aVerifyResult.length)
				{
					oValidKey = _.find(aVerifyResult, function (oItem) {
						return oItem && oItem.keyid && oItem.valid;
					});

					if (oValidKey && oValidKey.keyid && 
						aPublicKeys && aPublicKeys[0] &&
						oValidKey.keyid.toHex().toLowerCase() === aPublicKeys[0].getId())
					{
						bResult = true;
					}
					else
					{
						oResult.addNotice(OpenPgpResult.Enum.VerifyErrorNotice, sFromEmail);
					}
				}
			}
		}
		else
		{
			oResult.addNotice(OpenPgpResult.Enum.NoSignDataNotice);
		}
	}
	else
	{
		oResult.addError(OpenPgpResult.Enum.UnknownError);
	}

	if (!bResult && !oResult.hasNotices())
	{
		oResult.addNotice(OpenPgpResult.Enum.VerifyErrorNotice);
	}

	return bResult;
};

/**
 * @param {string} sUserID
 * @param {string} sPassword
 * @param {number} nKeyLength
 *
 * @return {OpenPgpResult}
 */
OpenPgp.prototype.generateKey = function (sUserID, sPassword, nKeyLength)
{
	var 
		oResult = new OpenPgpResult(),
		mKeyPair = null
	;

	try
	{
//		mKeyPair = this.pgp.generateKeyPair(1, Utils.pInt(nKeyLength), sUserID, Utils.trim(sPassword));
		mKeyPair = this.pgp.generateKeyPair({
			'userId': sUserID,
			'numBits': Utils.pInt(nKeyLength),
			'passphrase': Utils.trim(sPassword)
		});
	}
	catch (e)
	{
		oResult.addExceptionMessage(e);
	}

	if (mKeyPair && mKeyPair.privateKeyArmored)
	{
		try
		{
			this.pgpKeyring.privateKeys.importKey(mKeyPair.privateKeyArmored);
			this.pgpKeyring.publicKeys.importKey(mKeyPair.publicKeyArmored);
			this.pgpKeyring.store();
		}
		catch (e)
		{
			oResult.addExceptionMessage(e, OpenPgpResult.Enum.GenerateKeyError);
		}
	}
	else
	{
		oResult.addError(OpenPgpResult.Enum.GenerateKeyError);
	}

	this.reloadKeysFromStorage();

	return oResult;
};

/**
 * @private
 * @param {string} sArmor
 * @return {Array}
 */
OpenPgp.prototype.splitKeys = function (sArmor)
{
	var
		aResult = [],
		iCount = 0,
		iLimit = 30,
		aMatch = null,
		sKey = Utils.trim(sArmor),
		oReg = /[\-]{3,6}BEGIN[\s]PGP[\s](PRIVATE|PUBLIC)[\s]KEY[\s]BLOCK[\-]{3,6}[\s\S]+?[\-]{3,6}END[\s]PGP[\s](PRIVATE|PUBLIC)[\s]KEY[\s]BLOCK[\-]{3,6}/gi
	;

	sKey = sKey.replace(/[\r\n]([a-zA-Z0-9]{2,}:[^\r\n]+)[\r\n]+([a-zA-Z0-9\/\\+=]{10,})/g, '\n$1---xyx---$2')
		.replace(/[\n\r]+/g, '\n').replace(/---xyx---/g, '\n\n');

	do
	{
		aMatch = oReg.exec(sKey);
		if (!aMatch || 0 > iLimit)
		{
			break;
		}

		if (aMatch[0] && aMatch[1] && aMatch[2] && aMatch[1] === aMatch[2])
		{
			if ('PRIVATE' === aMatch[1] || 'PUBLIC' === aMatch[1])
			{
				aResult.push([aMatch[1], aMatch[0]]);
				iCount++;
			}
		}

		iLimit--;
	}
	while (true);

	return aResult;
};

/**
 * @param {string} sArmor
 * @return {OpenPgpResult}
 */
OpenPgp.prototype.importKeys = function (sArmor)
{
	sArmor = Utils.trim(sArmor);

	var
		iIndex = 0,
		iCount = 0,
		oResult = new OpenPgpResult(),
		aData = null,
		aKeys = []
	;

	if (!sArmor)
	{
		return oResult.addError(OpenPgpResult.Enum.InvalidArgumentErrors);
	}

	aKeys = this.splitKeys(sArmor);

	for (iIndex = 0; iIndex < aKeys.length; iIndex++)
	{
		aData = aKeys[iIndex];
		if ('PRIVATE' === aData[0])
		{
			try
			{
				this.pgpKeyring.privateKeys.importKey(aData[1]);
				iCount++;
			}
			catch (e)
			{
				oResult.addExceptionMessage(e, OpenPgpResult.Enum.ImportKeyError, 'private');
			}
		}
		else if ('PUBLIC' === aData[0])
		{
			try
			{
				this.pgpKeyring.publicKeys.importKey(aData[1]);
				iCount++;
			}
			catch (e)
			{
				oResult.addExceptionMessage(e, OpenPgpResult.Enum.ImportKeyError, 'public');
			}
		}
	}

	if (0 < iCount)
	{
		this.pgpKeyring.store();
	}
	else
	{
		oResult.addError(OpenPgpResult.Enum.ImportNoKeysFoundError);
	}

	this.reloadKeysFromStorage();

	return oResult;
};

/**
 * @param {string} sArmor
 * @return {Array|boolean}
 */
OpenPgp.prototype.getArmorInfo = function (sArmor)
{
	sArmor = Utils.trim(sArmor);

	var
		iIndex = 0,
		iCount = 0,
		oKey = null,
		aResult = [],
		aData = null,
		aKeys = []
	;

	if (!sArmor)
	{
		return false;
	}

	aKeys = this.splitKeys(sArmor);

	for (iIndex = 0; iIndex < aKeys.length; iIndex++)
	{
		aData = aKeys[iIndex];
		if ('PRIVATE' === aData[0])
		{
			try
			{
				oKey = this.pgp.key.readArmored(aData[1]);
				if (oKey && !oKey.err && oKey.keys && oKey.keys[0])
				{
					aResult.push(new OpenPgpKey(oKey.keys[0]));
				}
				
				iCount++;
			}
			catch (e)
			{
				aResult.push(null);
			}
		}
		else if ('PUBLIC' === aData[0])
		{
			try
			{
				oKey = this.pgp.key.readArmored(aData[1]);
				if (oKey && !oKey.err && oKey.keys && oKey.keys[0])
				{
					aResult.push(new OpenPgpKey(oKey.keys[0]));
				}

				iCount++;
			}
			catch (e)
			{
				aResult.push(null);
			}
		}
	}

	return aResult;
};

/**
 * @param {string} sID
 * @param {boolean} bPublic
 * @return {OpenPgpKey|null}
 */
OpenPgp.prototype.findKeyByID = function (sID, bPublic)
{
	bPublic = !!bPublic;
	sID = sID.toLowerCase();
	
	var oKey = _.find(this.keys(), function (oKey) {
		
		var
			oResult = false,
			aKeys = null
		;
		
		if (oKey && bPublic === oKey.isPublic())
		{
			aKeys = oKey.pgpKey.getKeyIds();
			if (aKeys)
			{
				oResult = _.find(aKeys, function (oKey) {
					return oKey && oKey.toHex && sID === oKey.toHex().toLowerCase();
				});
			}
		}
		
		return !!oResult;
	});

	return oKey ? oKey : null;
};

/**
 * @param {Array} aEmail
 * @param {boolean} bIsPublic
 * @param {OpenPgpResult=} oResult
 * @return {Array}
 */
OpenPgp.prototype.findKeysByEmails = function (aEmail, bIsPublic, oResult)
{
	bIsPublic = !!bIsPublic;
	
	var
		aResult = [],
		aKeys = this.keys()
	;
	_.each(aEmail, function (sEmail) {

		var oKey = _.find(aKeys, function (oKey) {
			return oKey && bIsPublic === oKey.isPublic() && sEmail === oKey.getEmail();
		});

		if (oKey)
		{
			aResult.push(oKey);
		}
		else
		{
			if (oResult)
			{
				oResult.addError(bIsPublic ?
					OpenPgpResult.Enum.PublicKeyNotFoundError : OpenPgpResult.Enum.PrivateKeyNotFoundError, sEmail);
			}
		}
	});

	return aResult;
};

/**
 * @param {string} sData
 * @param {string} sAccountEmail
 * @param {string} sFromEmail
 * @param {string=} sPrivateKeyPassword = ''
 * @return {string}
 */
OpenPgp.prototype.decryptAndVerify = function (sData, sAccountEmail, sFromEmail, sPrivateKeyPassword)
{
	var
		self = this,
		oMessage = null,
		oPrivateEmailKey = null,
		oPrivateKey = null,
		oPrivateKeyClone = null,
		oMessageDecrypted = null,
		oResult = new OpenPgpResult(),
		aEncryptionKeyIds = []
	;

	oMessage = this.pgp.message.readArmored(sData);
	if (oMessage && oMessage.decrypt)
	{
		aEncryptionKeyIds = oMessage.getEncryptionKeyIds();
		if (aEncryptionKeyIds)
		{
			oPrivateKey = null;
			oPrivateEmailKey = null;
			
			_.each(aEncryptionKeyIds, function (oKey) {
				if (!oPrivateEmailKey)
				{
					oPrivateEmailKey = self.findKeyByID(oKey.toHex(), false);
					if (oPrivateEmailKey && sAccountEmail !== oPrivateEmailKey.getEmail())
					{
						oPrivateEmailKey = null;
					}
				}
			});

			if (oPrivateEmailKey)
			{
				oPrivateKey = oPrivateEmailKey;
			}

			if (!oPrivateKey)
			{
				_.each(aEncryptionKeyIds, function (oKey) {
					if (!oPrivateKey)
					{
						oPrivateKey = self.findKeyByID(oKey.toHex(), false);
					}
				});
			}
		}

		if (!oPrivateKey)
		{
			oResult.addError(OpenPgpResult.Enum.PrivateKeyNotFoundError);
		}
		else
		{
			oPrivateKeyClone = this.cloneKey(this.convertToNativeKeys([oPrivateKey])[0]);
			
			this.decryptKeyHelper(oResult, oPrivateKeyClone, sPrivateKeyPassword, oPrivateKey.getEmail());

			if (oPrivateKeyClone && !oResult.hasErrors())
			{
				try
				{
					oMessageDecrypted = oMessage.decrypt(oPrivateKeyClone);
				}
				catch (e)
				{
					oResult.addExceptionMessage(e, OpenPgpResult.Enum.DecryptError);
					oMessageDecrypted = null;
				}
			}

			if (oMessageDecrypted && !oResult.hasErrors())
			{
				this.verifyMessageHelper(oResult, sFromEmail, oMessageDecrypted);

				oResult.result = oMessageDecrypted.getText();
			}
		}
	}

	return oResult;
};

/**
 * @param {string} sData
 * @param {string} sFromEmail
 * @return {string}
 */
OpenPgp.prototype.verify = function (sData, sFromEmail)
{
	var
		oMessageDecrypted = null,
		oResult = new OpenPgpResult()
	;

	oMessageDecrypted = this.pgp.cleartext.readArmored(sData);
	if (oMessageDecrypted && oMessageDecrypted.getText && oMessageDecrypted.verify)
	{
		this.verifyMessageHelper(oResult, sFromEmail, oMessageDecrypted);

		oResult.result = oMessageDecrypted.getText();
	}
	else
	{
		oResult.addError(OpenPgpResult.Enum.CanNotReadMessage);
	}

	return oResult;
};

/**
 * @param {string} sData
 * @param {Array} aPrincipalsEmail
 * @return {string}
 */
OpenPgp.prototype.encrypt = function (sData, aPrincipalsEmail)
{
	var
		oResult = new OpenPgpResult(),
		aPublicKeys = this.findKeysByEmails(aPrincipalsEmail, true, oResult)
	;

	if (!oResult.hasErrors())
	{
		try
		{
			oResult.result = this.pgp.encryptMessage(
				this.convertToNativeKeys(aPublicKeys), sData);
		}
		catch (e)
		{
			oResult.addExceptionMessage(e, OpenPgpResult.Enum.EncryptError);
		}
	}

	return oResult;
};

/**
 * @param {string} sData
 * @param {string} sFromEmail
 * @param {string=} sPrivateKeyPassword
 * @return {string}
 */
OpenPgp.prototype.sign = function (sData, sFromEmail, sPrivateKeyPassword)
{
	var
		oResult = new OpenPgpResult(),
		oPrivateKey = null,
		oPrivateKeyClone = null,
		aPrivateKeys = this.findKeysByEmails([sFromEmail], false, oResult)
	;

	if (!oResult.hasErrors())
	{
		oPrivateKey = this.convertToNativeKeys(aPrivateKeys)[0];
		oPrivateKeyClone = this.cloneKey(oPrivateKey);

		this.decryptKeyHelper(oResult, oPrivateKeyClone, sPrivateKeyPassword, sFromEmail);

		if (oPrivateKeyClone && !oResult.hasErrors())
		{
			try
			{
				oResult.result = this.pgp.signClearMessage([oPrivateKeyClone], sData);
			}
			catch (e)
			{
				oResult.addExceptionMessage(e, OpenPgpResult.Enum.SignError, sFromEmail);
			}
		}
	}

	return oResult;
};

/**
 * @param {string} sData
 * @param {string} sFromEmail
 * @param {Array} aPrincipalsEmail
 * @param {string=} sPrivateKeyPassword
 * @return {string}
 */
OpenPgp.prototype.signAndEncrypt = function (sData, sFromEmail, aPrincipalsEmail, sPrivateKeyPassword)
{
	var
		oPrivateKey = null,
		oPrivateKeyClone = null,
		oResult = new OpenPgpResult(),
		aPrivateKeys = this.findKeysByEmails([sFromEmail], false, oResult),
		aPublicKeys = this.findKeysByEmails(aPrincipalsEmail, true, oResult)
	;

	if (!oResult.hasErrors())
	{
		oPrivateKey = this.convertToNativeKeys(aPrivateKeys)[0];
		oPrivateKeyClone = this.cloneKey(oPrivateKey);

		this.decryptKeyHelper(oResult, oPrivateKeyClone, sPrivateKeyPassword, sFromEmail);
		
		if (oPrivateKeyClone && !oResult.hasErrors())
		{
			try
			{
				oResult.result = this.pgp.signAndEncryptMessage(
					this.convertToNativeKeys(aPublicKeys), oPrivateKeyClone, sData);
			}
			catch (e)
			{
				oResult.addExceptionMessage(e, OpenPgpResult.Enum.SignAndEncryptError);
			}
		}
	}
	
	return oResult;
};

/**
 * @param {OpenPgpKey} oKey
 */
OpenPgp.prototype.deleteKey = function (oKey)
{
	var oResult = new OpenPgpResult();
	if (oKey)
	{
		try
		{
			this.pgpKeyring[oKey.isPrivate() ? 'privateKeys' : 'publicKeys'].removeForId(oKey.getFingerprint());
			this.pgpKeyring.store();
		}
		catch (e)
		{
			oResult.addExceptionMessage(e, OpenPgpResult.Enum.DeleteError);
		}
	}
	else
	{
		oResult.addError(oKey ? OpenPgpResult.Enum.UnknownError : OpenPgpResult.Enum.InvalidArgumentError);
	}

	this.reloadKeysFromStorage();

	return oResult;
};


/**
 * @todo
 * @param {Object} oOpenPgpKey
 * @constructor
 */
function OpenPgpKey(oOpenPgpKey)
{
	this.pgpKey = oOpenPgpKey;

	var oPrimaryUser = this.pgpKey.getPrimaryUser();
	
	this.user = (oPrimaryUser && oPrimaryUser.user) ? oPrimaryUser.user.userId.userid :
		(this.pgpKey.users && this.pgpKey.users[0] ? this.pgpKey.users[0].userId.userid : '');

	this.emailParts = Utils.Address.getEmailParts(this.user);
}

/**
 * @type {Object}
 */
OpenPgpKey.prototype.pgpKey = null;

/**
 * @type {Object}
 */
OpenPgpKey.prototype.emailParts = null;

/**
 * @type {string}
 */
OpenPgpKey.prototype.user = '';

/**
 * @return {string}
 */
OpenPgpKey.prototype.getId = function ()
{
	return this.pgpKey.primaryKey.getKeyId().toHex().toLowerCase();
};

/**
 * @return {string}
 */
OpenPgpKey.prototype.getEmail = function ()
{
	return this.emailParts['email'] || this.user;
};

/**
 * @return {string}
 */
OpenPgpKey.prototype.getUser = function ()
{
	return this.user;
};

/**
 * @return {string}
 */
OpenPgpKey.prototype.getFingerprint = function ()
{
	return this.pgpKey.primaryKey.getFingerprint();
};

/**
 * @return {number}
 */
OpenPgpKey.prototype.getBitSize = function ()
{
	return this.pgpKey.primaryKey.getBitSize();
};

/**
 * @return {string}
 */
OpenPgpKey.prototype.getArmor = function ()
{
	return this.pgpKey.armor();
};

/**
 * @return {boolean}
 */
OpenPgpKey.prototype.isPrivate = function ()
{
	return !!this.pgpKey.isPrivate();
};

/**
 * @return {boolean}
 */
OpenPgpKey.prototype.isPublic = function ()
{
	return !this.isPrivate();
};


/**
 * @todo
 * @constructor
 */
function OpenPgpResult()
{
	this.result = true;
	this.errors = null;
	this.notices = null;
	this.exceptions = null;
}

OpenPgpResult.Enum = {
	'UnknownError': 0,
	'UnknownNotice': 1,
	'InvalidArgumentError': 2,
	'GenerateKeyError': 10,
	'ImportKeyError': 20,
	'ImportNoKeysFoundError': 21,
	'PrivateKeyNotFoundError': 30,
	'PublicKeyNotFoundError': 31,
	'KeyIsNotDecodedError': 32,
	'SignError': 40,
	'VerifyError': 41,
	'EncryptError': 42,
	'DecryptError': 43,
	'SignAndEncryptError': 44,
	'VerifyAndDecryptError': 45,
	'CanNotReadMessage': 50,
	'CanNotReadKey': 51,
	'DeleteError': 60,
	'PublicKeyNotFoundNotice': 70,
	'PrivateKeyNotFoundNotice': 71,
	'VerifyErrorNotice': 72,
	'NoSignDataNotice': 73
};

/**
 * @type {mixed}
 */
OpenPgpResult.prototype.result = false;

/**
 * @type {Array|null}
 */
OpenPgpResult.prototype.errors = null;

/**
 * @type {Array|null}
 */
OpenPgpResult.prototype.notices = null;

/**
 * @param {number} iCode
 * @param {string} sValue
 * @return {OpenPgpResult}
 */
OpenPgpResult.prototype.addError = function (iCode, sValue)
{
	this.result = false;
	this.errors = this.errors || [];
	this.errors.push([iCode || OpenPgpResult.Enum.UnknownError, sValue || '']);

	return this;
};

/**
 * @param {number} iCode
 * @param {string} sValue
 * @return {OpenPgpResult}
 */
OpenPgpResult.prototype.addNotice = function (iCode, sValue)
{
	this.notices = this.notices || [];
	this.notices.push([iCode || OpenPgpResult.Enum.UnknownNotice, sValue || '']);

	return this;
};

/**
 * @param {Error} e
 * @param {number=} iErrorCode
 * @param {string=} sErrorMessage
 * @return {OpenPgpResult}
 */
OpenPgpResult.prototype.addExceptionMessage = function (e, iErrorCode, sErrorMessage)
{
	if (e)
	{
		this.result = false;
		this.exceptions = this.exceptions || [];
		this.exceptions.push('' + (e.name || 'unknown') + ': ' + (e.message || ''));
	}

	if (!Utils.isUnd(iErrorCode))
	{
		this.addError(iErrorCode, sErrorMessage);
	}

	return this;
};

/**
 *  @return {boolean}
 */
OpenPgpResult.prototype.hasErrors = function ()
{
	return this.errors && 0 < this.errors.length;
};

/**
 *  @return {boolean}
 */
OpenPgpResult.prototype.hasNotices = function ()
{
	return this.notices && 0 < this.notices.length;
};

/**
 * @constructor
 */
function AlertPopup()
{
	this.alertDesc = ko.observable('');
	this.closeCallback = null;
	this.title = ko.observable('');
	this.okButtonText = ko.observable(Utils.i18n('MAIN/BUTTON_OK'));
}

/**
 * @param {string} sDesc
 * @param {Function=} fCloseCallback = null
 * @param {string=} sTitle = ''
 * @param {string=} sOkButtonText = 'Ok'
 */
AlertPopup.prototype.onShow = function (sDesc, fCloseCallback, sTitle, sOkButtonText)
{
	this.alertDesc(sDesc);
	this.closeCallback = fCloseCallback || null;
	this.title(sTitle || '');
	this.okButtonText(sOkButtonText || Utils.i18n('MAIN/BUTTON_OK'));
};

/**
 * @return {string}
 */
AlertPopup.prototype.popupTemplate = function ()
{
	return 'Popups_AlertPopupViewModel';
};

AlertPopup.prototype.onEnterHandler = function ()
{
	this.close();
};

AlertPopup.prototype.close = function ()
{
	if (Utils.isFunc(this.closeCallback))
	{
		this.closeCallback();
	}
	this.closeCommand();
};

/**
 * @constructor
 */
function ConfirmPopup()
{
	this.fConfirmCallback = null;
	this.confirmDesc = ko.observable('');
	this.title = ko.observable('');
	this.okButtonText = ko.observable(Utils.i18n('MAIN/BUTTON_OK'));
	this.cancelButtonText = ko.observable(Utils.i18n('MAIN/BUTTON_CANCEL'));
	this.shown = false;
}

/**
 * @param {string} sDesc
 * @param {Function} fConfirmCallback
 * @param {string=} sTitle = ''
 * @param {string=} sOkButtonText = ''
 * @param {string=} sCancelButtonText = ''
 */
ConfirmPopup.prototype.onShow = function (sDesc, fConfirmCallback, sTitle, sOkButtonText, sCancelButtonText)
{
	this.confirmDesc(sDesc);
	this.title(sTitle || '');
	this.okButtonText(sOkButtonText || Utils.i18n('MAIN/BUTTON_OK'));
	this.cancelButtonText(sCancelButtonText || Utils.i18n('MAIN/BUTTON_CANCEL'));
	if (Utils.isFunc(fConfirmCallback))
	{
		this.fConfirmCallback = fConfirmCallback;
	}
	this.shown = true;
};

ConfirmPopup.prototype.onHide = function ()
{
	this.shown = false;
};

/**
 * @return {string}
 */
ConfirmPopup.prototype.popupTemplate = function ()
{
	return 'Popups_ConfirmPopupViewModel';
};

ConfirmPopup.prototype.onEnterHandler = function ()
{
	this.yesClick();
};

ConfirmPopup.prototype.yesClick = function ()
{
	if (this.shown && this.fConfirmCallback)
	{
		this.fConfirmCallback(true);
	}

	this.closeCommand();
};

ConfirmPopup.prototype.noClick = function ()
{
	if (this.fConfirmCallback)
	{
		this.fConfirmCallback(false);
	}

	this.closeCommand();
};

ConfirmPopup.prototype.onEscHandler = function ()
{
	this.noClick();
};

/**
 * @constructor
 */
function ChangePasswordPopup()
{
	this.currentPassword = ko.observable('');
	this.newPassword = ko.observable('');
	this.confirmPassword = ko.observable('');
	
	this.isHelpdesk = ko.observable(false);
	
	this.hasOldPassword = ko.observable(false);
}

/**
 * @param {boolean} bHelpdesk
 * @param {boolean} bHasOldPassword
 * @param {Function=} fOnPasswordChangedCallback
 */
ChangePasswordPopup.prototype.onShow = function (bHelpdesk, bHasOldPassword, fOnPasswordChangedCallback)
{
	this.isHelpdesk(bHelpdesk);
	
	this.hasOldPassword(bHasOldPassword);
	
	this.fOnPasswordChangedCallback = fOnPasswordChangedCallback;
	
	this.currentPassword('');
	this.newPassword('');
	this.confirmPassword('');
};

/**
 * @return {string}
 */
ChangePasswordPopup.prototype.popupTemplate = function ()
{
	return 'Popups_ChangePasswordPopupViewModel';
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
ChangePasswordPopup.prototype.onUpdatePasswordResponse = function (oResponse, oRequest)
{
	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ACCOUNT_PROPERTIES_NEW_PASSWORD_UPDATE_ERROR'));
	}
	else
	{
		if (this.hasOldPassword())
		{
			App.Api.showReport(Utils.i18n('SETTINGS/ACCOUNT_PROPERTIES_CHANGE_PASSWORD_SUCCESS'));
		}
		else
		{
			App.Api.showReport(Utils.i18n('SETTINGS/ACCOUNT_PROPERTIES_SET_PASSWORD_SUCCESS'));
		}
		
		this.closeCommand();
		
		if (typeof this.fOnPasswordChangedCallback === 'function')
		{
			this.fOnPasswordChangedCallback();
		}
		
		App.sResetPassHash = '';
	}
};

ChangePasswordPopup.prototype.onOKClick = function ()
{
	var 
		oParameters = null
	;
	
	if (this.confirmPassword() !== this.newPassword())
	{
		App.Api.showError(Utils.i18n('WARNING/PASSWORDS_DO_NOT_MATCH'));
	}
	else
	{
		if (this.newPassword().length < AppData.App.PasswordMinLength) 
		{ 
			App.Api.showError(Utils.i18n('WARNING/PASSWORDS_MIN_LENGTH_ERROR').replace('%N%', AppData.App.PasswordMinLength));
			
		}
		else if (AppData.App.PasswordMustBeComplex && (!this.newPassword().match(/([0-9])/) || !this.newPassword().match(/([!,%,&,@,#,$,^,*,?,_,~])/)))
		{
			App.Api.showError(Utils.i18n('WARNING/PASSWORD_MUST_BE_COMPLEX'));
		}
		else
		{
			if (this.isHelpdesk())
			{
				oParameters = {
					'Action': 'HelpdeskUserPasswordUpdate',
					'CurrentPassword': this.currentPassword(),
					'NewPassword': this.newPassword()
				};
				App.Ajax.sendExt(oParameters, this.onUpdatePasswordResponse, this);
			}
			else
			{
				oParameters = {
					'Action': 'AccountUpdatePassword',
					'AccountID': AppData.Accounts.editedId(),
					'CurrentIncomingMailPassword': this.currentPassword(),
					'NewIncomingMailPassword': this.newPassword(),
					'Hash': App.sResetPassHash
				};
				App.Ajax.send(oParameters, this.onUpdatePasswordResponse, this);
			}
		}
	}
};

ChangePasswordPopup.prototype.onCancelClick = function ()
{
	this.closeCommand();
};

/**
 * @constructor
 */
function CImportOpenPgpKeyPopup()
{
	this.pgp = null;
	this.keyArmor = ko.observable('');
	this.keyArmorFocused = ko.observable(false);
	this.keys = ko.observableArray([]);
	this.hasExistingKeys = ko.observable(false);
	this.headlineText = ko.computed(function () {
		return Utils.i18n('OPENPGP/INFO_TEXT_INCLUDES_KEYS_PLURAL', {}, null, this.keys().length);
	}, this);
}

/**
 * @param {Object} oPgp
 */
CImportOpenPgpKeyPopup.prototype.onShow = function (oPgp, sArmor)
{
	this.pgp = oPgp;
	this.keyArmor(sArmor || '');
	this.keyArmorFocused(true);
	this.keys([]);
	this.hasExistingKeys(false);
	if (this.keyArmor() !== '')
	{
		this.checkArmor();
	}
};

/**
 * @return {string}
 */
CImportOpenPgpKeyPopup.prototype.popupTemplate = function ()
{
	return 'Popups_ImportOpenPgpKeyPopupViewModel';
};

CImportOpenPgpKeyPopup.prototype.checkArmor = function ()
{
	var
		aRes = null,
		aKeys = [],
		oPgp = this.pgp,
		bHasExistingKeys = false
	;
	
	if (this.keyArmor() === '')
	{
		this.keyArmorFocused(true);
	}
	else if (oPgp)
	{
		aRes = oPgp.getArmorInfo(this.keyArmor());
		
		if (Utils.isNonEmptyArray(aRes))
		{
			_.each(aRes, function (oKey) {
				if (oKey)
				{
					var
						oSameKey = oPgp.findKeyByID(oKey.getId(), oKey.isPublic()),
						bHasSameKey = (oSameKey !== null),
						sAddInfoLangKey = oKey.isPublic() ? 'OPENPGP/PUBLIC_KEY_ADD_INFO' : 'OPENPGP/PRIVATE_KEY_ADD_INFO'
					;
					bHasExistingKeys = bHasExistingKeys || bHasSameKey;
					aKeys.push({
						'armor': oKey.getArmor(),
						'email': oKey.user,
						'id': oKey.getId(),
						'addInfo': Utils.i18n(sAddInfoLangKey, {'LENGTH': oKey.getBitSize()}),
						'needToImport': ko.observable(!bHasSameKey),
						'disabled': bHasSameKey
					});
				}
			});
		}
		
		if (aKeys.length === 0)
		{
			App.Api.showError(Utils.i18n('OPENPGP/ERROR_IMPORT_NO_KEY_FOUND'));
		}
		
		this.keys(aKeys);
		this.hasExistingKeys(bHasExistingKeys);
	}
};

CImportOpenPgpKeyPopup.prototype.importKey = function ()
{
	var
		oRes = null,
		aArmors = []
	;
	if (this.pgp)
	{
		
		_.each(this.keys(), function (oSimpleKey) {
			if (oSimpleKey.needToImport())
			{
				aArmors.push(oSimpleKey.armor);
			}
		});
		
		if (aArmors.length > 0)
		{
			oRes = this.pgp.importKeys(aArmors.join(''));

			if (oRes && oRes.result)
			{
				App.Api.showReport(Utils.i18n('OPENPGP/REPORT_KEY_SUCCESSFULLY_IMPORTED_PLURAL', {}, null, aArmors.length));
			}

			if (oRes && !oRes.result)
			{
				App.Api.showPgpErrorByCode(oRes, Enums.PgpAction.Import, Utils.i18n('OPENPGP/ERROR_IMPORT_KEY'));
			}

			this.closeCommand();
		}
		else
		{
			App.Api.showError(Utils.i18n('OPENPGP/ERROR_IMPORT_NO_KEY_SELECTED'));
		}
	}
};


/**
 * @constructor
 */
function ContactCreatePopup()
{
	this.displayName = ko.observable('');
	this.email = ko.observable('');
	this.phone = ko.observable('');
	this.address = ko.observable('');
	this.skype = ko.observable('');
	this.facebook = ko.observable('');

	this.focusDisplayName = ko.observable(false);

	this.loading = ko.observable(false);

	this.fCallback = null;
	this.oContext = null;
}

/**
 * @return {string}
 */
ContactCreatePopup.prototype.popupTemplate = function ()
{
	return 'Popups_ContactCreatePopupViewModel';
};

/**
 * @param {string} sName
 * @param {string} sEmail
 * @param {Function} fContactCreateResponse
 * @param {Object} oContactCreateContext
 */
ContactCreatePopup.prototype.onShow = function (sName, sEmail, fContactCreateResponse, oContactCreateContext)
{
	if (this.displayName() !== sName || this.email() !== sEmail)
	{
		this.displayName(sName);
		this.email(sEmail);
		this.phone('');
		this.address('');
		this.skype('');
		this.facebook('');
	}

	if (Utils.isFunc(fContactCreateResponse))
	{
		this.fCallback = fContactCreateResponse;
	}
	if (oContactCreateContext)
	{
		this.oContext = oContactCreateContext;
	}
};

ContactCreatePopup.prototype.onSaveClick = function ()
{
	if (!this.canBeSave())
	{
		App.Api.showError(Utils.i18n('CONTACTS/ERROR_EMPTY_CONTACT'));
	}
	else if (!this.loading())
	{
		var
			oParameters = {
				'Action': 'ContactCreate',
				'PrimaryEmail': 'Home',
				'UseFriendlyName': '1',
				'FullName': this.displayName(),
				'HomeEmail': this.email(),
				'HomePhone': this.phone(),
				'HomeStreet': this.address(),
				'Skype': this.skype(),
				'Facebook': this.facebook()
			}
		;

		this.loading(true);
		App.Ajax.send(oParameters, this.onContactCreateResponse, this);
	}
};


ContactCreatePopup.prototype.onCancelClick = function ()
{
	this.loading(false);
	this.closeCommand();
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
ContactCreatePopup.prototype.onContactCreateResponse = function (oResponse, oRequest)
{
	this.loading(false);

	if (!oResponse.Result)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('WARNING/CREATING_ACCOUNT_ERROR'));
	}
	else
	{
		App.Api.showReport(Utils.i18n('CONTACTS/REPORT_CONTACT_SUCCESSFULLY_ADDED'));
		App.ContactsCache.clearInfoAboutEmail(oRequest.HomeEmail);
		App.ContactsCache.getContactsByEmails([oRequest.HomeEmail], this.fCallback, this.oContext);
		this.closeCommand();
	}
};

ContactCreatePopup.prototype.canBeSave = function ()
{
	return this.displayName() !== '' || this.email() !== '';
};

ContactCreatePopup.prototype.goToContacts = function ()
{
	App.ContactsCache.newContactParams = {
		displayName: this.displayName(),
		email: this.email(),
		phone: this.phone(),
		address: this.address(),
		skype: this.skype(),
		facebook: this.facebook()
	};
	this.closeCommand();
	App.Routing.replaceHash(App.Links.contacts());
};
/**
 * @constructor
 */
function PlayerPopup()
{
	this.iframe = ko.observable('');
	//this.closeCallback = null;
}

PlayerPopup.prototype.onShow = function (sIframe)
{
	this.iframe(sIframe);
	//this.closeCallback = fCloseCallback || null;
};

/**
 * @return {string}
 */
PlayerPopup.prototype.popupTemplate = function ()
{
	return 'Popups_PlayerPopupViewModel';
};

PlayerPopup.prototype.onClose = function ()
{
	if (Utils.isFunc(this.closeCallback))
	{
		this.closeCallback();
	}
	this.closeCommand();
	this.iframe('');
};


/**
 * @constructor
 * @param {boolean} bAllowOpenPgp
 */
function CAppSettingsModel(bAllowOpenPgp)
{
	this.AllowWebMail  = true;

	// allows to edit common settings and calendar settings
	this.AllowUsersChangeInterfaceSettings = true;

	// allows to delete accounts, allows to change account properties (name and password is always possible to change),
	// allows to manage special folders, allows to add new accounts
	this.AllowUsersChangeEmailSettings = true;

	// allows to add new accounts (if AllowUsersChangeEmailSettings === true)
	this.AllowUsersAddNewAccounts = true || this.AllowUsersChangeEmailSettings;
	
	this.SiteName = '';

	// list of available languages
	this.Languages = [
		{name: 'English', value: 'en'}
	];

	// list of available themes
	this.Themes = [
		'Default'
	];

	// list of available date formats
	this.DateFormats = [];
	
	this.DefaultLanguage = 'English';

	// maximum size of uploading attachment
	this.AttachmentSizeLimit = 10240000;
	this.ImageUploadSizeLimit = 10240000;
	
	this.FileSizeLimit = 10240000;

	// activate autosave
	this.AutoSave = true;
	this.AutoSaveIntervalSeconds = 60;
	this.IdleSessionTimeout = 0;
	
	// allows to insert an image to html-text in rich text editor
	this.AllowInsertImage = true;
	this.AllowBodySize = false;
	this.MaxBodySize = 600;
	this.MaxSubjectSize = 255;
	this.JoinReplyPrefixes = true;

	this.AllowAppRegisterMailto = true;
	this.AllowPrefetch = true;
	this.MaxPrefetchBodiesSize = 50000;

	this.LoginFormType = Enums.LoginFormType.Email;
	this.LoginAtDomainValue = '';
	this.AllowRegistration = false;
	this.AllowPasswordReset = false;
	this.RegistrationDomains = [];
	this.RegistrationQuestions = [];

	this.DemoWebMail = true;
	this.DemoWebMailLogin = '';
	this.DemoWebMailPassword = '';
	this.LoginDescription = '';
	this.GoogleAnalyticsAccount = '';
	this.ShowQuotaBar = false;
	this.ServerUseUrlRewrite = false;

	this.AllowLanguageOnLogin = false;
	this.FlagsLangSelect = false;
	
	this.CustomLoginUrl = '';
	this.CustomLogoutUrl = '';

	this.IosDetectOnLogin = false;
	
	this.AllowContactsSharing = false;

	this.DefaultLanguageShort = 'en';
	
	this.AllowOpenPgp = bAllowOpenPgp;
	
	this.DefaultTab = '';
	
	this.AllowIosProfile = true;
	
	this.PasswordMinLength = 0;
	this.PasswordMustBeComplex = false;
}
	
/**
 * Parses the application settings from the server.
 * 
 * @param {Object} oData
 */
CAppSettingsModel.prototype.parse = function (oData)
{
	this.AllowWebMail = !!oData.AllowWebMail;
	this.AllowUsersChangeInterfaceSettings = !!oData.AllowUsersChangeInterfaceSettings;
	this.AllowUsersChangeEmailSettings = !!oData.AllowUsersChangeEmailSettings;
	this.AllowUsersAddNewAccounts = !!oData.AllowUsersAddNewAccounts || this.AllowUsersChangeEmailSettings;
	this.SiteName = Utils.pString(oData.SiteName);
	this.Languages = oData.Languages;
	this.Themes = oData.Themes;
	this.DateFormats = oData.DateFormats;
	this.AttachmentSizeLimit = Utils.pInt(oData.AttachmentSizeLimit);
	this.ImageUploadSizeLimit = Utils.pInt(oData.ImageUploadSizeLimit);
	this.FileSizeLimit = Utils.pInt(oData.FileSizeLimit);
	this.AutoSave = !!oData.AutoSave;
	this.IdleSessionTimeout = Utils.pInt(oData.IdleSessionTimeout) * 60 * 1000; // converts minutes to milliseconds
	this.AllowInsertImage = !!oData.AllowInsertImage;
	this.AllowBodySize = !!oData.AllowBodySize;
	this.MaxBodySize = Utils.pInt(oData.MaxBodySize);
	this.MaxSubjectSize = Utils.pInt(oData.MaxSubjectSize);
	this.JoinReplyPrefixes = !!oData.JoinReplyPrefixes;
	this.AllowAppRegisterMailto = !!oData.AllowAppRegisterMailto;
	this.AllowPrefetch = !!oData.AllowPrefetch;

	this.LoginFormType = Utils.pInt(oData.LoginFormType);
	this.LoginSignMeType = Utils.pInt(oData.LoginSignMeType);
	this.LoginAtDomainValue = Utils.pString(oData.LoginAtDomainValue);
	this.AllowRegistration = !!oData.AllowRegistration;
	this.AllowPasswordReset = !!oData.AllowPasswordReset;
	this.RegistrationDomains = oData.RegistrationDomains;
	this.RegistrationQuestions = _.without(oData.RegistrationQuestions, '');
	
	this.DemoWebMail = !!oData.DemoWebMail;
	this.DemoWebMailLogin = Utils.pString(oData.DemoWebMailLogin);
	this.DemoWebMailPassword = Utils.pString(oData.DemoWebMailPassword);
	this.GoogleAnalyticsAccount = oData.GoogleAnalyticsAccount;
	this.ShowQuotaBar = !!oData.ShowQuotaBar;
	this.ServerUseUrlRewrite = !!oData.ServerUseUrlRewrite;

	this.AllowLanguageOnLogin = !bMobileApp && !!oData.AllowLanguageOnLogin;
	this.FlagsLangSelect = !!oData.FlagsLangSelect;

	this.DefaultLanguage = Utils.pString(oData.DefaultLanguage);
	this.LoginDescription = Utils.pString(oData.LoginDescription);
	
	this.CustomLoginUrl = Utils.pString(oData.CustomLoginUrl);
	this.CustomLogoutUrl = Utils.pString(oData.CustomLogoutUrl);

	this.IosDetectOnLogin = !!oData.IosDetectOnLogin;

	this.AllowContactsSharing = !!oData.AllowContactsSharing;

	if (oData.DefaultLanguageShort !== '')
	{
		this.DefaultLanguageShort = oData.DefaultLanguageShort;
	}
	this.DefaultTab = oData.DefaultTab;
	this.AllowIosProfile = !!oData.AllowIosProfile;
	this.PasswordMinLength = oData.PasswordMinLength;
	this.PasswordMustBeComplex = !!oData.PasswordMustBeComplex;
};

/**
 * @constructor
 */
function CDateModel()
{
	this.iTimeStampInUTC = 0;
	this.oMoment = null;
}

/**
 * @param {number} iTimeStampInUTC
 */
CDateModel.prototype.parse = function (iTimeStampInUTC)
{
	this.iTimeStampInUTC = iTimeStampInUTC;
	this.oMoment = moment.unix(this.iTimeStampInUTC);
};

/**
 * @param {number} iYear
 * @param {number} iMonth
 * @param {number} iDay
 */
CDateModel.prototype.setDate = function (iYear, iMonth, iDay)
{
	this.oMoment = moment([iYear, iMonth, iDay]);
};

/**
 * @return {string}
 */
CDateModel.prototype.getTimeFormat = function ()
{
	return (AppData.User.defaultTimeFormat() === Enums.TimeFormat.F24) ?
		'HH:mm' : 'hh:mm A';
};

/**
 * @return {string}
 */
CDateModel.prototype.getFullDate = function ()
{
	return (this.oMoment) ? this.oMoment.format('ddd, MMM D, YYYY, ' + this.getTimeFormat()) : '';
};

/**
 * @return {string}
 */
CDateModel.prototype.getMidDate = function ()
{
	return this.getShortDate(true);
};

/**
 * @param {boolean=} bTime = false
 * 
 * @return {string}
 */
CDateModel.prototype.getShortDate = function (bTime)
{
	var
		sResult = '',
		oMomentNow = null
	;

	if (this.oMoment)
	{
		oMomentNow = moment();

		if (oMomentNow.format('L') === this.oMoment.format('L'))
		{
			sResult = this.oMoment.format(this.getTimeFormat());
		}
		else
		{
			if (oMomentNow.clone().subtract(1, 'days').format('L') === this.oMoment.format('L'))
			{
				sResult = Utils.i18n('DATETIME/YESTERDAY');
			}
			else if (oMomentNow.year() === this.oMoment.year())
			{
				sResult = this.oMoment.format('MMM D');
			}
			else
			{
				sResult = this.oMoment.format('MMM D, YYYY');
			}

			if (Utils.isUnd(bTime) ? false : !!bTime)
			{
				sResult += ', ' + this.oMoment.format(this.getTimeFormat());
			}
		}
	}

	return sResult;
};

/**
 * @return {string}
 */
CDateModel.prototype.getDate = function ()
{
	return (this.oMoment) ? this.oMoment.format('ddd, MMM D, YYYY') : '';
};

/**
 * @return {string}
 */
CDateModel.prototype.getTime = function ()
{
	return (this.oMoment) ? this.oMoment.format(this.getTimeFormat()): '';
};

/**
 * @param {string} iDate
 * 
 * @return {string}
 */
CDateModel.prototype.convertDate = function (iDate)
{
	var sFormat = Utils.getDateFormatForMoment(AppData.User.DefaultDateFormat) + ' ' + this.getTimeFormat();
	
	return moment(iDate * 1000).format(sFormat);
};

/**
 * @return {number}
 */
CDateModel.prototype.getTimeStampInUTC = function ()
{
	return this.iTimeStampInUTC;
};

/**
 * @constructor
 */
function CCommonFileModel()
{
	this.isIosDevice = bIsIosDevice;

	this.isFolder = ko.observable(false);
	this.isLink = ko.observable(false);
	this.linkType = ko.observable(Enums.FileStorageLinkType.Unknown);
	this.linkUrl = ko.observable('');
	this.isPopupItem = ko.observable(false);
	
	this.id = ko.observable('');
	this.fileName = ko.observable('');
	this.tempName = ko.observable('');
	this.displayName = ko.observable('');
	this.extension = ko.observable('');
	this.oembed = ko.observable('');
	this.linkType.subscribe(function (iLinkType) {
		var sOembed = '';
		switch (iLinkType)
		{
			case Enums.FileStorageLinkType.YouTube:
				sOembed = 'YouTube';
				break;
			case Enums.FileStorageLinkType.Vimeo:
				if (!App.browser.ie || App.browser.ie11)
				{
					sOembed = 'Vimeo';
				}
				break;
			case Enums.FileStorageLinkType.SoundCloud:
				if (!App.browser.ie || App.browser.ie10AndAbove)
				{
					sOembed = 'SoundCloud';
				}
				break;
		}

		this.oembed(sOembed);
	}, this);
	
	this.fileName.subscribe(function (sFileName) {
		this.id(sFileName);
		this.displayName(sFileName);
		this.extension(this.isFolder() ? '' : Utils.getFileExtension(sFileName));
	}, this);
	
	this.size = ko.observable(0);
	this.friendlySize = ko.computed(function () {
		return this.size() > 0 ? Utils.friendlySize(this.size()) : '';
	}, this);
	
	this.content = ko.observable('');

	this.accountId = ko.observable((AppData.Accounts) ? AppData.Accounts.defaultId() : null);
	this.hash = ko.observable('');
	this.thumb = ko.observable(false);
	this.iframedView = ko.observable(false);

	this.downloadLink = ko.computed(function () {
		return Utils.getDownloadLinkByHash(this.accountId(), this.hash());
	}, this);

	this.viewLink = ko.computed(function () {
		var sUrl = Utils.File.getViewLinkByHash(this.accountId(), this.hash());
		return this.iframedView() ? Utils.getIframeWrappwer(this.accountId(), sUrl) : sUrl;
	}, this);

	this.thumbnailSrc = ko.observable('');
	this.thumbnailLoaded = ko.observable(false);
	this.thumbnailSessionUid = ko.observable('');

	this.thumbnailLink = ko.computed(function () {
		return this.thumb() ? Utils.getViewThumbnailLinkByHash(this.accountId(), this.hash()) : '';
	}, this);

	this.type = ko.observable('');
	this.uploadUid = ko.observable('');
	this.uploaded = ko.observable(false);
	this.uploadError = ko.observable(false);
	this.visibleImportLink = ko.computed(function () {
		return AppData.User.enableOpenPgp() && this.extension().toLowerCase() === 'asc' && this.content() !== '' && !this.isPopupItem();
	}, this);
	this.isViewMimeType = ko.computed(function () {
		return (-1 !== $.inArray(this.type(), aViewMimeTypes)) || this.iframedView();
	}, this);
	this.isMessageType = ko.observable(false);
	this.visibleViewLink = ko.computed(function () {
		return this.isVisibleViewLink() && !this.isPopupItem();
	}, this);
	this.visibleOpenLink = ko.computed(function () {
		return this.linkUrl() !== '';
	}, this);
	this.visibleDownloadLink = ko.computed(function () {
		return !this.isPopupItem() && !this.visibleOpenLink();
	}, this);

	this.subFiles = ko.observableArray([]);
	this.allowExpandSubFiles = ko.observable(false);
	this.subFilesLoaded = ko.observable(false);
	this.subFilesCollapsed = ko.observable(false);
	this.subFilesStartedLoading = ko.observable(false);
	this.visibleExpandLink = ko.computed(function () {
		return this.allowExpandSubFiles() && !this.subFilesCollapsed() && !this.subFilesStartedLoading();
	}, this);
	this.visibleExpandingText = ko.computed(function () {
		return this.allowExpandSubFiles() && !this.subFilesCollapsed() && this.subFilesStartedLoading();
	}, this);
	
	this.visibleSpinner = ko.observable(false);
	this.statusText = ko.observable('');
	this.statusTooltip = ko.computed(function () {
		return this.uploadError() ? this.statusText() : '';
	}, this);
	this.progressPercent = ko.observable(0);
	this.visibleProgress = ko.observable(false);
	
	this.uploadStarted = ko.observable(false);
	this.uploadStarted.subscribe(function () {
		if (this.uploadStarted())
		{
			this.uploaded(false);
			this.visibleProgress(true);
			this.progressPercent(20);
		}
		else
		{
			this.progressPercent(100);
			this.visibleProgress(false);
			this.uploaded(true);
		}
	}, this);
	
	this.allowDrag = ko.observable(false);
	this.allowSelect = ko.observable(false);
	this.allowCheck = ko.observable(false);
	this.allowDelete = ko.observable(false);
	this.allowUpload = ko.observable(false);
	this.allowSharing = ko.observable(false);
	this.allowHeader = ko.observable(false);
	this.allowDownload = ko.observable(true);

	this.downloadTitle = ko.computed(function () {
		var sTitle = '';
		
		if (!this.allowSelect() && this.allowDownload())
		{
			sTitle = Utils.i18n('MESSAGE/ATTACHMENT_CLICK_TO_DOWNLOAD', {
				'FILENAME': this.fileName(),
				'SIZE': this.friendlySize()
			});
			
			if (this.friendlySize() === '')
			{
				sTitle = sTitle.replace(' ()', '');
			}
		}
		
		return sTitle;
	}, this);

	this.oembedHtml = ko.observable('');
}

/**
 * Can be overridden.
 */
CCommonFileModel.prototype.dataObjectName = '';

/**
 * Can be overridden.
 * 
 * @returns {boolean}
 */
CCommonFileModel.prototype.isVisibleViewLink = function ()
{
	return this.uploaded() && !this.uploadError() && this.isViewMimeType();
};

/**
 * Parses attachment data from server.
 *
 * @param {AjaxAttachmenResponse} oData
 * @param {number} iAccountId
 */
CCommonFileModel.prototype.parse = function (oData, iAccountId)
{
	if (oData['@Object'] === this.dataObjectName)
	{
		this.fileName(Utils.pString(oData.FileName));
		this.tempName(Utils.pString(oData.TempName));
		if (this.tempName() === '')
		{
			this.tempName(this.fileName());
		}

		this.type(Utils.pString(oData.MimeType));
		this.size(oData.EstimatedSize ? parseInt(oData.EstimatedSize, 10) : parseInt(oData.SizeInBytes, 10));
		this.content(Utils.pString(oData.Content));

		this.thumb(!!oData.Thumb);

		this.hash(Utils.pString(oData.Hash));
		this.accountId(iAccountId);
		this.allowExpandSubFiles(!!oData.Expand);
		
		this.iframedView(!!oData.Iframed);

		this.uploadUid(this.hash());
		this.uploaded(true);
		
		if (Utils.isFunc(this.additionalParse))
		{
			this.additionalParse(oData);
		}
	}
};

CCommonFileModel.prototype.getInThumbQueue = function (sThumbSessionUid)
{
	this.thumbnailSessionUid(sThumbSessionUid);
	if(this.thumb() && (!this.linked || this.linked && !this.linked()))
	{
		Utils.thumbQueue(this.thumbnailSessionUid(), this.thumbnailLink(), this.thumbnailSrc);
	}
};

/**
 * @param {Object=} oApp
 * 
 * Starts downloading attachment on click.
 */
CCommonFileModel.prototype.downloadFile = function (oApp)
{
	if (this.allowDownload())
	{
		if (!oApp || !oApp.Api || !oApp.Api.downloadByUrl)
		{
			oApp = App;
		}

		if (oApp && this.downloadLink().length > 0 && this.downloadLink() !== '#')
		{
			oApp.Api.downloadByUrl(this.downloadLink());
		}
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CCommonFileModel.prototype.onFileExpandResponse = function (oResponse, oRequest)
{
	this.subFiles([]);
	if (Utils.isNonEmptyArray(oResponse.Result))
	{
		_.each(oResponse.Result, _.bind(function (oRawFile) {
			var oFile = this.getInstance();
			oRawFile['@Object'] = this.dataObjectName;
			oFile.parse(oRawFile, this.accountId());
			this.subFiles.push(oFile);
		}, this));
		this.subFilesLoaded(true);
		this.subFilesCollapsed(true);
	}
	this.subFilesStartedLoading(false);
};

/**
 * Starts expanding attachment on click.
 */
CCommonFileModel.prototype.expandFile = function ()
{
	if (!this.subFilesLoaded())
	{
		this.subFilesStartedLoading(true);
		App.Ajax.send({
			'Action': 'FileExpand',
			'RawKey': this.hash()
		}, this.onFileExpandResponse, this);
	}
	else
	{
		this.subFilesCollapsed(true);
	}
};

/**
 * Collapse attachment on click.
 */
CCommonFileModel.prototype.collapseFile = function ()
{
	this.subFilesCollapsed(false);
};

/**
 * @returns {CCommonFileModel}
 */
CCommonFileModel.prototype.getInstance = function ()
{
	return new CCommonFileModel();
};

/**
 * Starts importing attachment on click.
 */
CCommonFileModel.prototype.importFile = function ()
{
	var
		sContent = this.content(),
		fPgpCallback = _.bind(function (oPgp) {
			if (oPgp)
			{
				App.Screens.showPopup(CImportOpenPgpKeyPopup, [oPgp, sContent]);
			}
		}, this)
	;
	
	App.Api.pgp(fPgpCallback, AppData.User.IdUser);
};

/**
 * @param {Object} oViewModel
 * @param {Object} oEvent
 * @param {Object} oApp
 */
CCommonFileModel.prototype.downloadOrViewByIconClick = function (oViewModel, oEvent, oApp)
{
	if (this.oembed() !== '')
	{
		this.viewFile(oViewModel, oEvent);
	}
	else if (!this.allowSelect())
	{
		this.downloadFile(oApp);
	}
};

/**
 * Can be overridden.
 * 
 * Starts viewing attachment on click.
 */
CCommonFileModel.prototype.viewFile = function (oViewModel, oEvent)
{
	Utils.calmEvent(oEvent);
	this.viewCommonFile();
};

CCommonFileModel.prototype.openLink = function ()
{
	Utils.WindowOpener.openTab(this.viewLink());
};

/**
 * Starts viewing attachment on click.
 */
CCommonFileModel.prototype.viewCommonFile = function ()
{
	var
		sUrl = Utils.Common.getAppPath() + this.viewLink(),
		oWin = null
	;

	if (this.visibleViewLink() && this.viewLink().length > 0 && this.viewLink() !== '#')
	{
		if (this.isLink()/* && this.linkType() === Enums.FileStorageLinkType.GoogleDrive*/)
		{
			sUrl = this.linkUrl();
		}

		if (this.oembedHtml() !== '')
		{
			App.Api.showPlayer(this.oembedHtml());
		}
		else if (this.iframedView())
		{
			oWin = Utils.WindowOpener.openTab(sUrl);
		}
		else
		{
			oWin = Utils.WindowOpener.open(sUrl, sUrl, false);
		}

		if (oWin)
		{
			oWin.focus();
		}
	}
};

/**
 * @param {Object} oAttachment
 * @param {*} oEvent
 * @return {boolean}
 */
CCommonFileModel.prototype.eventDragStart = function (oAttachment, oEvent)
{
	var oLocalEvent = oEvent.originalEvent || oEvent;
	if (oAttachment && oLocalEvent && oLocalEvent.dataTransfer && oLocalEvent.dataTransfer.setData)
	{
		oLocalEvent.dataTransfer.setData('DownloadURL', this.generateTransferDownloadUrl());
	}

	return true;
};

/**
 * @return {string}
 */
CCommonFileModel.prototype.generateTransferDownloadUrl = function ()
{
	var sLink = this.downloadLink();
	if ('http' !== sLink.substr(0, 4))
	{
		sLink = Utils.Common.getAppPath() + sLink;
	}

	return this.type() + ':' + this.fileName() + ':' + sLink;
};

/**
 * Fills attachment data for upload.
 *
 * @param {string} sFileUid
 * @param {Object} oFileData
 */
CCommonFileModel.prototype.onUploadSelect = function (sFileUid, oFileData)
{
	this.fileName(Utils.pString(oFileData['FileName']));
	this.type(Utils.pString(oFileData['Type']));
	this.size(Utils.pInt(oFileData['Size']));

	this.uploadUid(sFileUid);
	this.uploaded(false);
	this.visibleSpinner(false);
	this.statusText('');
	this.progressPercent(0);
	this.visibleProgress(false);
};

/**
 * Starts spinner and progress.
 */
CCommonFileModel.prototype.onUploadStart = function ()
{
	this.visibleSpinner(true);
	this.visibleProgress(true);
};

/**
 * Fills progress upload data.
 *
 * @param {number} iUploadedSize
 * @param {number} iTotalSize
 */
CCommonFileModel.prototype.onUploadProgress = function (iUploadedSize, iTotalSize)
{
	if (iTotalSize > 0)
	{
		this.progressPercent(Math.ceil(iUploadedSize / iTotalSize * 100));
		this.visibleProgress(true);
	}
};

/**
 * Fills data when upload has completed.
 *
 * @param {string} sFileUid
 * @param {boolean} bResponseReceived
 * @param {Object} oResult
 */
CCommonFileModel.prototype.onUploadComplete = function (sFileUid, bResponseReceived, oResult)
{
	var
		bError = !bResponseReceived || !oResult || oResult.Error || false,
		sError = (oResult && oResult.Error === 'size') ?
			Utils.i18n('COMPOSE/UPLOAD_ERROR_SIZE') :
			Utils.i18n('COMPOSE/UPLOAD_ERROR_UNKNOWN')
	;
	
	this.visibleSpinner(false);
	this.progressPercent(0);
	this.visibleProgress(false);
	
	this.uploaded(true);
	this.uploadError(bError);
	this.statusText(bError ? sError : Utils.i18n('COMPOSE/UPLOAD_COMPLETE'));

	if (!bError)
	{
		this.fillDataAfterUploadComplete(oResult, sFileUid);
		
		setTimeout((function (self) {
			return function () {
				self.statusText('');
			};
		})(this), 3000);
	}
};

/**
 * Should be overriden.
 * 
 * @param {Object} oResult
 * @param {string} sFileUid
 */
CCommonFileModel.prototype.fillDataAfterUploadComplete = function (oResult, sFileUid)
{
};

/**
 * @param {Object} oAttachmentModel
 * @param {Object} oEvent
 */
CCommonFileModel.prototype.onImageLoad = function (oAttachmentModel, oEvent)
{
	if(this.thumb() && !this.thumbnailLoaded())
	{
		this.thumbnailLoaded(true);
		Utils.thumbQueue(this.thumbnailSessionUid());
	}
};


/**
 * @constructor
 */
function CContactModel()
{
	this.allowSendEmails = ko.computed(function () {
		return AppData.App.AllowWebMail && AppData.Accounts.isCurrentAllowsMail();
	}, this);
	
	this.sEmailDefaultType = Enums.ContactEmailType.Personal;
	this.sPhoneDefaultType = Enums.ContactPhoneType.Mobile;
	this.sAddressDefaultType = Enums.ContactAddressType.Personal;
	
	this.voiceApp = null;
	if (App.Phone)
	{
		this.voiceApp = App.Phone.voiceApp;
	}

	this.idContact = ko.observable('');
	this.idUser = ko.observable('');
	this.global = ko.observable(false);
	this.itsMe = ko.observable(false);

	this.isNew = ko.observable(false);
	this.readOnly = ko.observable(false);
	this.edited = ko.observable(false);
	this.extented = ko.observable(false);
	this.personalCollapsed = ko.observable(false);
	this.businessCollapsed = ko.observable(false);
	this.otherCollapsed = ko.observable(false);
	this.groupsCollapsed = ko.observable(false);

	this.displayName = ko.observable('');
	this.firstName = ko.observable('');
	this.lastName = ko.observable('');
	this.nickName = ko.observable('');

	this.skype = ko.observable('');
	this.facebook = ko.observable('');

	this.displayNameFocused = ko.observable(false);

	this.primaryEmail = ko.observable(this.sEmailDefaultType);
	this.primaryPhone = ko.observable(this.sPhoneDefaultType);
	this.primaryAddress = ko.observable(this.sAddressDefaultType);

	this.mainPrimaryEmail = ko.computed({
		'read': this.primaryEmail,
		'write': function (mValue) {
			if (!Utils.isUnd(mValue) && 0 <= Utils.inArray(mValue, [Enums.ContactEmailType.Personal, Enums.ContactEmailType.Business, Enums.ContactEmailType.Other]))
			{
				this.primaryEmail(mValue);
			}
			else
			{
				this.primaryEmail(Enums.ContactEmailType.Personal);
			}
		},
		'owner': this
	});

	this.mainPrimaryPhone = ko.computed({
		'read': this.primaryPhone,
		'write': function (mValue) {
			if (!Utils.isUnd(mValue) && 0 <= Utils.inArray(mValue, [Enums.ContactPhoneType.Mobile, Enums.ContactPhoneType.Personal, Enums.ContactPhoneType.Business]))
			{
				this.primaryPhone(mValue);
			}
			else
			{
				this.primaryPhone(Enums.ContactPhoneType.Mobile);
			}
		},
		'owner': this
	});
	
	this.mainPrimaryAddress = ko.computed({
		'read': this.primaryAddress,
		'write': function (mValue) {
			if (!Utils.isUnd(mValue) && 0 <= Utils.inArray(mValue, [Enums.ContactAddressType.Personal, Enums.ContactAddressType.Business]))
			{
				this.primaryAddress(mValue);
			}
			else
			{
				this.primaryAddress(Enums.ContactAddressType.Personal);
			}
		},
		'owner': this
	});

	this.personalEmail = ko.observable('');
	this.personalStreetAddress = ko.observable('');
	this.personalCity = ko.observable('');
	this.personalState = ko.observable('');
	this.personalZipCode = ko.observable('');
	this.personalCountry = ko.observable('');
	this.personalWeb = ko.observable('');
	this.personalFax = ko.observable('');
	this.personalPhone = ko.observable('');
	this.personalMobile = ko.observable('');

	this.businessEmail = ko.observable('');
	this.businessCompany = ko.observable('');
	this.businessDepartment = ko.observable('');
	this.businessJob = ko.observable('');
	this.businessOffice = ko.observable('');
	this.businessStreetAddress = ko.observable('');
	this.businessCity = ko.observable('');
	this.businessState = ko.observable('');
	this.businessZipCode = ko.observable('');
	this.businessCountry = ko.observable('');
	this.businessWeb = ko.observable('');
	this.businessFax = ko.observable('');
	this.businessPhone = ko.observable('');

	this.otherEmail = ko.observable('');
	this.otherBirthdayMonth = ko.observable('0');
	this.otherBirthdayDay = ko.observable('0');
	this.otherBirthdayYear = ko.observable('0');
	this.otherNotes = ko.observable('');
	this.etag = ko.observable('');
	
	this.sharedToAll = ko.observable(false);

	this.birthdayIsEmpty = ko.computed(function () {
		var
			bMonthEmpty = '0' === this.otherBirthdayMonth(),
			bDayEmpty = '0' === this.otherBirthdayDay(),
			bYearEmpty = '0' === this.otherBirthdayYear()
		;

		return (bMonthEmpty || bDayEmpty || bYearEmpty);
	}, this);
	
	this.otherBirthday = ko.computed(function () {
		var
			sBirthday = '',
			iYear = Utils.pInt(this.otherBirthdayYear()),
			iMonth = Utils.pInt(this.otherBirthdayMonth()),
			iDay = Utils.pInt(this.otherBirthdayDay()),
			oDateModel = new CDateModel()
		;
		
		if (!this.birthdayIsEmpty())
		{
			var fullYears = moment().diff(moment(iYear + '/' + iMonth + '/' + iDay, "YYYY/MM/DD"), 'years'),
				text = Utils.i18n('CONTACTS/YEARS_TEXT_PLURAL', {
					'COUNT': fullYears
				}, null, fullYears)
			;
			oDateModel.setDate(iYear, 0 < iMonth ? iMonth - 1 : 0, iDay);
			sBirthday = oDateModel.getShortDate() + ' (' + text + ')';
		}
		
		return sBirthday;
	}, this);

	this.groups = ko.observableArray([]);

	this.groupsIsEmpty = ko.computed(function () {
		return 0 === this.groups().length;
	}, this);

	this.email = ko.computed({
		'read': function () {
			var sResult = '';
			switch (this.primaryEmail()) {
				case Enums.ContactEmailType.Personal:
					sResult = this.personalEmail();
					break;
				case Enums.ContactEmailType.Business:
					sResult = this.businessEmail();
					break;
				case Enums.ContactEmailType.Other:
					sResult = this.otherEmail();
					break;
			}
			return sResult;
		},
		'write': function (sEmail) {
			switch (this.primaryEmail()) {
				case Enums.ContactEmailType.Personal:
					this.personalEmail(sEmail);
					break;
				case Enums.ContactEmailType.Business:
					this.businessEmail(sEmail);
					break;
				case Enums.ContactEmailType.Other:
					this.otherEmail(sEmail);
					break;
				default:
					this.primaryEmail(this.sEmailDefaultType);
					this.email(sEmail);
					break;
			}
		},
		'owner': this
	});

	this.personalIsEmpty = ko.computed(function () {
		var sPersonalEmail = (this.personalEmail() !== this.email()) ? this.personalEmail() : '';
		return '' === '' + sPersonalEmail +
			this.personalStreetAddress() +
			this.personalCity() +
			this.personalState() +
			this.personalZipCode() +
			this.personalCountry() +
			this.personalWeb() +
			this.personalFax() +
			this.personalPhone() +
			this.personalMobile()
		;
	}, this);

	this.businessIsEmpty = ko.computed(function () {
		var sBusinessEmail = (this.businessEmail() !== this.email()) ? this.businessEmail() : '';
		return '' === '' + sBusinessEmail +
			this.businessCompany() +
			this.businessDepartment() +
			this.businessJob() +
			this.businessOffice() +
			this.businessStreetAddress() +
			this.businessCity() +
			this.businessState() +
			this.businessZipCode() +
			this.businessCountry() +
			this.businessWeb() +
			this.businessFax() +
			this.businessPhone()
		;
	}, this);

	this.otherIsEmpty = ko.computed(function () {
		var sOtherEmail = (this.otherEmail() !== this.email()) ? this.otherEmail() : '';
		return ('' === ('' + sOtherEmail + this.otherNotes())) && this.birthdayIsEmpty();
	}, this);
	
	this.phone = ko.computed({
		'read': function () {
			var sResult = '';
			switch (this.primaryPhone()) {
				case Enums.ContactPhoneType.Mobile:
					sResult = this.personalMobile();
					break;
				case Enums.ContactPhoneType.Personal:
					sResult = this.personalPhone();
					break;
				case Enums.ContactPhoneType.Business:
					sResult = this.businessPhone();
					break;
			}
			return sResult;
		},
		'write': function (sPhone) {
			switch (this.primaryPhone()) {
				case Enums.ContactPhoneType.Mobile:
					this.personalMobile(sPhone);
					break;
				case Enums.ContactPhoneType.Personal:
					this.personalPhone(sPhone);
					break;
				case Enums.ContactPhoneType.Business:
					this.businessPhone(sPhone);
					break;
				default:
					this.primaryPhone(this.sEmailDefaultType);
					this.phone(sPhone);
					break;
			}
		},
		'owner': this
	});
	
	this.address = ko.computed({
		'read': function () {
			var sResult = '';
			switch (this.primaryAddress()) {
				case Enums.ContactAddressType.Personal:
					sResult = this.personalStreetAddress();
					break;
				case Enums.ContactAddressType.Business:
					sResult = this.businessStreetAddress();
					break;
			}
			return sResult;
		},
		'write': function (sAddress) {
			switch (this.primaryAddress()) {
				case Enums.ContactAddressType.Personal:
					this.personalStreetAddress(sAddress);
					break;
				case Enums.ContactAddressType.Business:
					this.businessStreetAddress(sAddress);
					break;
				default:
					this.primaryAddress(this.sEmailDefaultType);
					this.address(sAddress);
					break;
			}
		},
		'owner': this
	});

	this.emails = ko.computed(function () {
		var aList = [];
		
		if ('' !== this.personalEmail())
		{
			aList.push({'text': Utils.i18n('CONTACTS/OPTION_PERSONAL') + ': ' + this.personalEmail(), 'value': Enums.ContactEmailType.Personal});
		}
		if ('' !== this.businessEmail())
		{
			aList.push({'text': Utils.i18n('CONTACTS/OPTION_BUSINESS') + ': ' + this.businessEmail(), 'value': Enums.ContactEmailType.Business});
		}
		if ('' !== this.otherEmail())
		{
			aList.push({'text': Utils.i18n('CONTACTS/OPTION_OTHER') + ': ' + this.otherEmail(), 'value': Enums.ContactEmailType.Other});
		}

		return aList;

	}, this);

	this.phones = ko.computed(function () {
		var aList = [];

		if ('' !== this.personalMobile())
		{
			aList.push({'text': Utils.i18n('CONTACTS/LABEL_MOBILE') + ': ' + this.personalMobile(), 'value': Enums.ContactPhoneType.Mobile});
		}
		if ('' !== this.personalPhone())
		{
			aList.push({'text': Utils.i18n('CONTACTS/OPTION_PERSONAL') + ': ' + this.personalPhone(), 'value': Enums.ContactPhoneType.Personal});
		}
		if ('' !== this.businessPhone())
		{
			aList.push({'text': Utils.i18n('CONTACTS/OPTION_BUSINESS') + ': ' + this.businessPhone(), 'value': Enums.ContactPhoneType.Business});
		}
		return aList;

	}, this);
	
	this.addresses = ko.computed(function () {
		var aList = [];

		if ('' !== this.personalStreetAddress())
		{
			aList.push({'text': Utils.i18n('CONTACTS/OPTION_PERSONAL') + ': ' + this.personalStreetAddress(), 'value': Enums.ContactAddressType.Personal});
		}
		if ('' !== this.businessStreetAddress())
		{
			aList.push({'text': Utils.i18n('CONTACTS/OPTION_BUSINESS') + ': ' + this.businessStreetAddress(), 'value': Enums.ContactAddressType.Business});
		}
		return aList;

	}, this);

	this.hasEmails = ko.computed(function () {
		return 0 < this.emails().length;
	}, this);

	this.extented.subscribe(function (bValue) {
		if (bValue)
		{
			this.personalCollapsed(!this.personalIsEmpty());
			this.businessCollapsed(!this.businessIsEmpty());
			this.otherCollapsed(!this.otherIsEmpty());
			this.groupsCollapsed(!this.groupsIsEmpty());
		}
	}, this);

	this.birthdayMonthSelect = CContactModel.birthdayMonthSelect;
	this.birthdayYearSelect = CContactModel.birthdayYearSelect;

	this.birthdayDaySelect = ko.computed(function () {

		var
			iIndex = 1,
			iLen = Utils.pInt(Utils.daysInMonth(this.otherBirthdayMonth(), this.otherBirthdayYear())),
			sIndex = '',
			aList = [{'text': Utils.i18n('DATETIME/DAY'), 'value': '0'}]
		;

		for (; iIndex <= iLen; iIndex++)
		{
			sIndex = iIndex.toString();
			aList.push({'text': sIndex, 'value': sIndex});
		}

		return aList;

	}, this);


	for (var oDate = new Date(), sIndex = '', iIndex = oDate.getFullYear(), iLen = 2012 - 80; iIndex >= iLen; iIndex--)
	{
		sIndex = iIndex.toString();
		this.birthdayYearSelect.push(
			{'text': sIndex, 'value': sIndex}
		);
	}

	this.canBeSave = ko.computed(function () {
		return this.displayName() !== '' || !!this.emails().length;
	}, this);
	
	this.sendMailLink = ko.computed(function () {
		return bMobileApp ? this.getSendMailLink(this.email()) : '#';
	}, this);

	this.sendMailToPersonalLink = ko.computed(function () {
		return bMobileApp ? this.getSendMailLink(this.personalEmail()) : '#';
	}, this);
	
	this.sendMailToBusinessLink = ko.computed(function () {
		return bMobileApp ? this.getSendMailLink(this.businessEmail()) : '#';
	}, this);
	
	this.sendMailToOtherLink = ko.computed(function () {
		return bMobileApp ? this.getSendMailLink(this.otherEmail()) : '#';
	}, this);
}

CContactModel.birthdayMonths = Utils.getMonthNamesArray();

CContactModel.birthdayMonthSelect = [
	{'text': Utils.i18n('DATETIME/MONTH'), value: '0'},
	{'text': CContactModel.birthdayMonths[0], value: '1'},
	{'text': CContactModel.birthdayMonths[1], value: '2'},
	{'text': CContactModel.birthdayMonths[2], value: '3'},
	{'text': CContactModel.birthdayMonths[3], value: '4'},
	{'text': CContactModel.birthdayMonths[4], value: '5'},
	{'text': CContactModel.birthdayMonths[5], value: '6'},
	{'text': CContactModel.birthdayMonths[6], value: '7'},
	{'text': CContactModel.birthdayMonths[7], value: '8'},
	{'text': CContactModel.birthdayMonths[8], value: '9'},
	{'text': CContactModel.birthdayMonths[9], value: '10'},
	{'text': CContactModel.birthdayMonths[10], value: '11'},
	{'text': CContactModel.birthdayMonths[11], value: '12'}
];

CContactModel.birthdayYearSelect = [
	{'text': Utils.i18n('DATETIME/YEAR'), 'value': '0'}
];

/**
 * @param {string} sEmail
 * @return {Array}
 */
CContactModel.prototype.getSendMailParts = function (sEmail)
{
	return App.Links.composeWithToField(this.getFullEmail(sEmail));
};

/**
 * @param {string} sEmail
 * @return {string}
 */
CContactModel.prototype.getSendMailLink = function (sEmail)
{
	return App.Routing.buildHashFromArray(this.getSendMailParts(sEmail));
};

CContactModel.prototype.sendMail = function ()
{
	App.Api.composeMessageToAddresses(this.email());
	return bMobileApp;
};

CContactModel.prototype.sendMailToPersonal = function ()
{
	App.Api.composeMessageToAddresses(this.personalEmail());
	return bMobileApp;
};

CContactModel.prototype.sendMailToBusiness = function ()
{
	App.Api.composeMessageToAddresses(this.businessEmail());
	return bMobileApp;
};

CContactModel.prototype.sendMailToOther = function ()
{
	App.Api.composeMessageToAddresses(this.otherEmail());
	return bMobileApp;
};

CContactModel.prototype.clear = function ()
{
	this.isNew(false);
	this.readOnly(false);

	this.idContact('');
	this.idUser('');
	this.global(false);
	this.itsMe(false);

	this.edited(false);
	this.extented(false);
	this.personalCollapsed(false);
	this.businessCollapsed(false);
	this.otherCollapsed(false);
	this.groupsCollapsed(false);

	this.displayName('');
	this.firstName('');
	this.lastName('');
	this.nickName('');

	this.skype('');
	this.facebook('');

	this.primaryEmail(this.sEmailDefaultType);
	this.primaryPhone(this.sPhoneDefaultType);
	this.primaryAddress(this.sAddressDefaultType);

	this.personalEmail('');
	this.personalStreetAddress('');
	this.personalCity('');
	this.personalState('');
	this.personalZipCode('');
	this.personalCountry('');
	this.personalWeb('');
	this.personalFax('');
	this.personalPhone('');
	this.personalMobile('');

	this.businessEmail('');
	this.businessCompany('');
	this.businessDepartment('');
	this.businessJob('');
	this.businessOffice('');
	this.businessStreetAddress('');
	this.businessCity('');
	this.businessState('');
	this.businessZipCode('');
	this.businessCountry('');
	this.businessWeb('');
	this.businessFax('');
	this.businessPhone('');

	this.otherEmail('');
	this.otherBirthdayMonth('0');
	this.otherBirthdayDay('0');
	this.otherBirthdayYear('0');
	this.otherNotes('');

	this.etag('');
	this.sharedToAll(false);

	this.groups([]);
};

CContactModel.prototype.switchToNew = function ()
{
	this.clear();
	this.edited(true);
	this.extented(false);
	this.isNew(true);
	if (!bMobileApp)
	{
		this.displayNameFocused(true);
	}
};

CContactModel.prototype.switchToView = function ()
{
	this.edited(false);
	this.extented(false);
};

/**
 * @return {Object}
 */
CContactModel.prototype.toObject = function ()
{
	var oResult = {
		'ContactId': this.idContact(),
		'PrimaryEmail': this.primaryEmail(),
		'PrimaryPhone': this.primaryPhone(),
		'PrimaryAddress': this.primaryAddress(),
		'UseFriendlyName': '1',
		'Title': '',
		'FullName': this.displayName(),
		'FirstName': this.firstName(),
		'LastName': this.lastName(),
		'NickName': this.nickName(),

		'Global': this.global() ? '1' : '0',
		'ItsMe': this.itsMe() ? '1' : '0',

		'Skype': this.skype(),
		'Facebook': this.facebook(),

		'HomeEmail': this.personalEmail(),
		'HomeStreet': this.personalStreetAddress(),
		'HomeCity': this.personalCity(),
		'HomeState': this.personalState(),
		'HomeZip': this.personalZipCode(),
		'HomeCountry': this.personalCountry(),
		'HomeFax': this.personalFax(),
		'HomePhone': this.personalPhone(),
		'HomeMobile': this.personalMobile(),
		'HomeWeb': this.personalWeb(),

		'BusinessEmail': this.businessEmail(),
		'BusinessCompany': this.businessCompany(),
		'BusinessJobTitle': this.businessJob(),
		'BusinessDepartment': this.businessDepartment(),
		'BusinessOffice': this.businessOffice(),
		'BusinessStreet': this.businessStreetAddress(),
		'BusinessCity': this.businessCity(),
		'BusinessState': this.businessState(),
		'BusinessZip': this.businessZipCode(),
		'BusinessCountry': this.businessCountry(),
		'BusinessFax': this.businessFax(),
		'BusinessPhone': this.businessPhone(),
		'BusinessWeb': this.businessWeb(),

		'OtherEmail': this.otherEmail(),
		'Notes': this.otherNotes(),
		'ETag': this.etag(),
		'BirthdayDay': this.otherBirthdayDay(),
		'BirthdayMonth': this.otherBirthdayMonth(),
		'BirthdayYear': this.otherBirthdayYear(),

		'SharedToAll': this.sharedToAll() ? '1' : '0',
		
		'GroupsIds': this.groups()
	};

	return oResult;
};

/**
 * @param {Object} oData
 */
CContactModel.prototype.parse = function (oData)
{
	if (oData && 'Object/CContact' === oData['@Object'])
	{
		var
			iPrimaryEmail = 0,
			iPrimaryPhone = 0,
			iPrimaryAddress = 0,
			aGroupsIds = []
		;

		this.idContact(Utils.pExport(oData, 'IdContact', '').toString());
		this.idUser(Utils.pExport(oData, 'IdUser', '').toString());

		this.global(!!Utils.pExport(oData, 'Global', false));
		this.itsMe(!!Utils.pExport(oData, 'ItsMe', false));
		this.readOnly(!!Utils.pExport(oData, 'ReadOnly', false));

		this.displayName(Utils.pExport(oData, 'FullName', ''));
		this.firstName(Utils.pExport(oData, 'FirstName', ''));
		this.lastName(Utils.pExport(oData, 'LastName', ''));
		this.nickName(Utils.pExport(oData, 'NickName', ''));

		this.skype(Utils.pExport(oData, 'Skype', ''));
		this.facebook(Utils.pExport(oData, 'Facebook', ''));

		iPrimaryEmail = Utils.pInt(Utils.pExport(oData, 'PrimaryEmail', 0));
		switch (iPrimaryEmail)
		{
			case 1:
				iPrimaryEmail = Enums.ContactEmailType.Business;
				break;
			case 2:
				iPrimaryEmail = Enums.ContactEmailType.Other;
				break;
			default:
			case 0:
				iPrimaryEmail = Enums.ContactEmailType.Personal;
				break;
		}
		this.primaryEmail(iPrimaryEmail);

		iPrimaryPhone = Utils.pInt(Utils.pExport(oData, 'PrimaryPhone', 0));
		switch (iPrimaryPhone)
		{
			case 2:
				iPrimaryPhone = Enums.ContactPhoneType.Business;
				break;
			case 1:
				iPrimaryPhone = Enums.ContactPhoneType.Personal;
				break;
			default:
			case 0:
				iPrimaryPhone = Enums.ContactPhoneType.Mobile;
				break;
		}
		this.primaryPhone(iPrimaryPhone);
		
		iPrimaryAddress = Utils.pInt(Utils.pExport(oData, 'PrimaryAddress', 0));
		switch (iPrimaryAddress)
		{
			case 1:
				iPrimaryAddress = Enums.ContactAddressType.Business;
				break;
			default:
			case 0:
				iPrimaryAddress = Enums.ContactAddressType.Personal;
				break;
		}
		this.primaryAddress(iPrimaryAddress);

		this.personalEmail(Utils.pExport(oData, 'HomeEmail', ''));
		this.personalStreetAddress(Utils.pExport(oData, 'HomeStreet', ''));
		this.personalCity(Utils.pExport(oData, 'HomeCity', ''));
		this.personalState(Utils.pExport(oData, 'HomeState', ''));
		this.personalZipCode(Utils.pExport(oData, 'HomeZip', ''));
		this.personalCountry(Utils.pExport(oData, 'HomeCountry', ''));
		this.personalWeb(Utils.pExport(oData, 'HomeWeb', ''));
		this.personalFax(Utils.pExport(oData, 'HomeFax', ''));
		this.personalPhone(Utils.pExport(oData, 'HomePhone', ''));
		this.personalMobile(Utils.pExport(oData, 'HomeMobile', ''));

		this.businessEmail(Utils.pExport(oData, 'BusinessEmail', ''));
		this.businessCompany(Utils.pExport(oData, 'BusinessCompany', ''));
		this.businessDepartment(Utils.pExport(oData, 'BusinessDepartment', ''));
		this.businessJob(Utils.pExport(oData, 'BusinessJobTitle', ''));
		this.businessOffice(Utils.pExport(oData, 'BusinessOffice', ''));
		this.businessStreetAddress(Utils.pExport(oData, 'BusinessStreet', ''));
		this.businessCity(Utils.pExport(oData, 'BusinessCity', ''));
		this.businessState(Utils.pExport(oData, 'BusinessState', ''));
		this.businessZipCode(Utils.pExport(oData, 'BusinessZip', ''));
		this.businessCountry(Utils.pExport(oData, 'BusinessCountry', ''));
		this.businessWeb(Utils.pExport(oData, 'BusinessWeb', ''));
		this.businessFax(Utils.pExport(oData, 'BusinessFax', ''));
		this.businessPhone(Utils.pExport(oData, 'BusinessPhone', ''));

		this.otherEmail(Utils.pExport(oData, 'OtherEmail', ''));
		this.otherBirthdayMonth(Utils.pExport(oData, 'BirthdayMonth', '0').toString());
		this.otherBirthdayDay(Utils.pExport(oData, 'BirthdayDay', '0').toString());
		this.otherBirthdayYear(Utils.pExport(oData, 'BirthdayYear', '0').toString());
		this.otherNotes(Utils.pExport(oData, 'Notes', ''));

		this.etag(Utils.pExport(oData, 'ETag', ''));

		this.sharedToAll(!!Utils.pExport(oData, 'SharedToAll', false));

		aGroupsIds = Utils.pExport(oData, 'GroupsIds', []);
		if (_.isArray(aGroupsIds))
		{
			this.groups(
				_.map(aGroupsIds, function (sItem) {
					return Utils.pString(sItem);
				})
			);
		}
	}
};

/**
 * @param {string} sEmail
 * @return {string}
 */
CContactModel.prototype.getFullEmail = function (sEmail)
{
	return Utils.Address.getFullEmail(this.displayName(), sEmail);
};

CContactModel.prototype.getEmailsString = function ()
{
	return _.uniq(_.without([this.email(), this.personalEmail(), this.businessEmail(), this.otherEmail()], '')).join(',');
};

CContactModel.prototype.viewAllMails = function ()
{
	App.MailCache.searchMessagesInInbox('email:' + this.getEmailsString());
};

CContactModel.prototype.sendThisContact = function ()
{
	App.Api.composeMessageWithVcard(this);
};

/**
 * @param {?} mLink
 * @return {boolean}
 */
CContactModel.prototype.isStrLink = function (mLink)
{
	return (/^http/).test(mLink);
};

/**
 * @param {string} sPhone
 */
CContactModel.prototype.onCallClick = function (sPhone)
{
	App.Phone.call(sPhone);
};

CContactModel.prototype.viewAllMailsWithContact = function ()
{
	var sSearch = this.getEmailsString();
	
	if (AppData.SingleMode && window.opener && window.opener.App)
	{
		window.opener.App.MailCache.searchMessagesInCurrentFolder('email:' + sSearch);
		window.opener.focus();
		window.close();
	}
	else
	{
		App.MailCache.searchMessagesInCurrentFolder('email:' + sSearch);
	}
};


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


/**
 * @constructor
 */
function CThreadListModel()
{
	this.Id = null;
	this.ThreadHash = '';
	this.IdOwner = null;
	this.ItsMe = false;
	this.aOwner = [];
	this.sSubject = '';
	this.sEmail = '';
	this.sName = '';
	this.sFrom = '';
	this.sFromFull = '';
	this.time = ko.observable(0);
	this.state = ko.observable(0);
	this.unseen = ko.observable(false);
	this.postsCount = ko.observable(0);

	this.date = ko.computed(function () {
		return moment(this.time() * 1000).fromNow(false);
	}, this);

	this.printableState = ko.computed(function () {
		var 
			sText = '',
			sLangSuffix = this.ItsMe ? '_FOR_CLIENT' : ''
		;
		
		switch (this.state())
		{
			case Enums.HelpdeskThreadStates.Pending:
				sText = Utils.i18n('HELPDESK/THREAD_STATE_PENDING' + sLangSuffix);
				break;
			case Enums.HelpdeskThreadStates.Resolved:
				sText = Utils.i18n('HELPDESK/THREAD_STATE_RESOLVED' + sLangSuffix);
				break;
			case Enums.HelpdeskThreadStates.Waiting:
				sText = Utils.i18n('HELPDESK/THREAD_STATE_WAITING' + sLangSuffix);
				break;
			case Enums.HelpdeskThreadStates.Answered:
				sText = Utils.i18n('HELPDESK/THREAD_STATE_ANSWERED' + sLangSuffix);
				break;
			case Enums.HelpdeskThreadStates.Deferred:
				sText = Utils.i18n('HELPDESK/THREAD_STATE_DEFERRED' + sLangSuffix);
				break;
		}
		
		return sText;
	}, this);

	this.deleted = ko.observable(false);
	this.checked = ko.observable(false);
	this.selected = ko.observable(false);
}

/**
 * @param {Object} oData
 */
CThreadListModel.prototype.parse = function (oData)
{
	this.Id = oData.IdHelpdeskThread;
	this.ThreadHash = Utils.pString(oData.ThreadHash);
	this.IdOwner = oData.IdOwner;
	this.ItsMe = !!oData.ItsMe;
	this.sSubject = Utils.pString(oData.Subject);
	this.time(Utils.pInt(oData.Updated));
	this.aOwner = Utils.isNonEmptyArray(oData.Owner) ? oData.Owner : ['', ''];
	this.sEmail = this.aOwner[0] || '';
	this.sName = this.aOwner[1] || '';
	this.sFrom = this.sName || this.sEmail;
	this.sFromFull = Utils.trim('' === this.sName ? this.sEmail :
		(this.sName + ('' !== this.sEmail ? ' (' + this.sEmail  + ')' : '')));

	this.postsCount(oData.PostCount);
	this.state(oData.Type);
	this.unseen(!oData.IsRead);
};

/**
 * @return {string}
 */
CThreadListModel.prototype.Name = function ()
{
	return this.sName;
};

/**
 * @return {string}
 */
CThreadListModel.prototype.Email = function ()
{
	return this.sEmail;
};

CThreadListModel.prototype.updateMomentDate = function ()
{
	this.time.valueHasMutated();
};

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


/**
 * @constructor
 */
function CUserSettingsModel()
{
	this.Name = '';
	this.Email = '';
	this.DefaultLanguage = 'English';
	this.DefaultDateFormat = 'MM/DD/YYYY';
	this.defaultTimeFormat = ko.observable(Enums.TimeFormat.F24);
	this.IsHelpdeskAgent = true;
	this.HelpdeskIframeUrl = '';
	this.enableOpenPgp = ko.observable(false);
	this.helpdeskSignature = ko.observable('');
	this.helpdeskSignatureEnable = ko.observable(false);
	this.HasPassword = false;
}

/**
 * @param {Object} oData
 */
CUserSettingsModel.prototype.parse = function (oData)
{
	if (oData !== null)
	{
		this.Name = Utils.pString(oData.Name);
		this.Email = Utils.pString(oData.Email);

		if (oData.Language)
		{
			this.DefaultLanguage = Utils.pString(oData.Language);
		}

		if (oData.DateFormat)
		{
			this.DefaultDateFormat = Utils.pString(oData.DateFormat);
		}
		
		this.defaultTimeFormat(Utils.pString(oData.TimeFormat));
		this.IsHelpdeskAgent = !!oData.IsHelpdeskAgent;
		this.HelpdeskIframeUrl = Utils.pString(oData.HelpdeskIframeUrl);
		this.HasPassword = oData.HasPassword;
	}
};

/**
 * @param {string} sName
 * @param {string} sLanguage
 * @param {string} sTimeFormat
 * @param {string} sDateFormat
 */
CUserSettingsModel.prototype.updateSettings = function (sName, sLanguage, sTimeFormat, sDateFormat)
{
	this.Name = sName;
	this.DefaultLanguage = sLanguage;
	this.DefaultDateFormat = sDateFormat;
	this.defaultTimeFormat(sTimeFormat);
};

/**
 * @constructor
 */
function CInformationViewModel()
{
	this.iAnimationDuration = 500;
	this.iReportDuration = 5000;
	this.iErrorDuration = 10000;
	
	this.loadingMessage = ko.observable('');
	this.loadingHidden = ko.observable(true);
	this.loadingVisible = ko.observable(false);
	this.reportMessage = ko.observable('');
	this.reportHidden = ko.observable(true);
	this.reportVisible = ko.observable(false);
	this.reportVisibleClose = ko.observable(false);
	this.iReportTimeout = -1;
	this.errorMessage = ko.observable('');
	this.errorHidden = ko.observable(true);
	this.errorVisible = ko.observable(false);
	this.iErrorTimeout = -1;
	this.isHtmlError = ko.observable(false);
	this.gray = ko.observable(false);
}

/**
 * @param {string} sMessage
 */
CInformationViewModel.prototype.showLoading = function (sMessage)
{
	if (sMessage && sMessage !== '')
	{
		this.loadingMessage(sMessage);
	}
	else
	{
		this.loadingMessage(Utils.i18n('MAIN/LOADING'));
	}
	this.loadingVisible(true);
	_.defer(_.bind(function () {
		this.loadingHidden(false);
	}, this));
}
;

CInformationViewModel.prototype.hideLoading = function ()
{
	this.loadingHidden(true);
	setTimeout(_.bind(function () {
		if (this.loadingHidden())
		{
			this.loadingVisible(false);
		}
	}, this), this.iAnimationDuration);
};

/**
 * Displays a message. Starts a timer for hiding.
 * 
 * @param {string} sMessage
 * @param {number=} iDelay
 */
CInformationViewModel.prototype.showReport = function (sMessage, iDelay)
{
	if (iDelay !== 0)
	{
		iDelay = iDelay || this.iReportDuration;
	}
	
	if (sMessage && sMessage !== '')
	{
		this.reportMessage(sMessage);
		
		this.reportVisible(true);
		_.defer(_.bind(this.reportHidden, this, false));
		
		clearTimeout(this.iReportTimeout);
		if (iDelay === 0)
		{
			this.reportVisibleClose(true);
		}
		else
		{
			this.reportVisibleClose(false);
			this.iReportTimeout = setTimeout(_.bind(this.selfHideReport, this), iDelay);
		}
	}
	else
	{
		this.reportHidden(true);
		this.reportVisible(false);
	}
};

CInformationViewModel.prototype.selfHideReport = function ()
{
	this.reportHidden(true);
	setTimeout(_.bind(function () {
		if (this.reportHidden())
		{
			this.reportVisible(false);
		}
	}, this), this.iAnimationDuration);
};

/**
 * Displays an error message. Starts a timer for hiding.
 *
 * @param {string} sMessage
 * @param {boolean=} bHtml = false
 * @param {boolean=} bNotHide = false
 * @param {boolean=} bGray = false
 */
CInformationViewModel.prototype.showError = function (sMessage, bHtml, bNotHide, bGray)
{
	if (sMessage && sMessage !== '')
	{
		this.gray(!!bGray);
		this.errorMessage(sMessage);
		this.isHtmlError(bHtml);
		
		this.errorVisible(true);
		_.defer(_.bind(function () {
			this.errorHidden(false);
		}, this));
		
		clearTimeout(this.iErrorTimeout);
		if (!bNotHide)
		{
			this.iErrorTimeout = setTimeout(_.bind(function () {
				this.selfHideError();
			}, this), this.iErrorDuration);
		}
	}
	else
	{
		this.selfHideError();
	}
};

CInformationViewModel.prototype.selfHideError = function ()
{
	this.errorHidden(true);
	setTimeout(_.bind(function () {
		if (this.errorHidden())
		{
			this.errorVisible(false);
		}
	}, this), this.iAnimationDuration);
};

/**
 * @param {boolean=} bGray = false
 */
CInformationViewModel.prototype.hideError = function (bGray)
{
	bGray = Utils.isUnd(bGray) ? false : !!bGray;
	if (bGray === this.gray())
	{
		this.selfHideError();
	}
};


/**
 * @constructor
 * @param {number} iCount
 * @param {number} iPerPage
 */
function CPageSwitcherViewModel(iCount, iPerPage)
{
	this.shown = false;
	
	this.currentPage = ko.observable(1);
	this.count = ko.observable(iCount);
	this.perPage = ko.observable(iPerPage);
	this.firstPage = ko.observable(1);
	this.lastPage = ko.observable(1);

	this.pagesCount = ko.computed(function () {
		var iCount = Math.ceil(this.count() / this.perPage());
		return (iCount > 0) ? iCount : 1;
	}, this);

	ko.computed(function () {

		var
			iAllLimit = 20,
			iLimit = 4,
			iPagesCount = this.pagesCount(),
			iCurrentPage = this.currentPage(),
			iStart = iCurrentPage,
			iEnd = iCurrentPage
		;

		if (iPagesCount > 1)
		{
			while (true)
			{
				iAllLimit--;
				
				if (1 < iStart)
				{
					iStart--;
					iLimit--;
				}

				if (0 === iLimit)
				{
					break;
				}

				if (iPagesCount > iEnd)
				{
					iEnd++;
					iLimit--;
				}

				if (0 === iLimit)
				{
					break;
				}

				if (0 === iAllLimit)
				{
					break;
				}
			}
		}

		this.firstPage(iStart);
		this.lastPage(iEnd);
		
	}, this);

	
//	this.firstPage = ko.computed(function () {
//		var iValue = this.currentPage() - this.iLimitAround;
//		return (iValue > 0) ? iValue : 1;
//	}, this);
//
//	this.lastPage = ko.computed(function () {
//		var iValue = this.firstPage() + this.iLimitAround * 2;
//		return (iValue <= this.pagesCount()) ? iValue : this.pagesCount();
//	}, this);

	this.visibleFirst = ko.computed(function () {
		return (this.firstPage() > 1);
	}, this);

	this.visibleLast = ko.computed(function () {
		return (this.lastPage() < this.pagesCount());
	}, this);

	this.clickPage = _.bind(this.clickPage, this);

	this.pages = ko.computed(function () {
		var
			iIndex = this.firstPage(),
			aPages = []
		;

		if (this.firstPage() < this.lastPage())
		{
			for (; iIndex <= this.lastPage(); iIndex++)
			{
				aPages.push({
					number: iIndex,
					current: (iIndex === this.currentPage()),
					clickFunc: this.clickPage
				});
			}
		}

		return aPages;
	}, this);
	
	this.hotKeysBind();
}

CPageSwitcherViewModel.prototype.hotKeysBind = function ()
{
	$(document).on('keydown', $.proxy(function(ev) {
		if (this.shown && !Utils.isTextFieldFocused())
		{
			var sKey = ev.keyCode;
			if (ev.ctrlKey && sKey === Enums.Key.Left)
			{
				this.clickPreviousPage();
			}
			else if (ev.ctrlKey && sKey === Enums.Key.Right)
			{
				this.clickNextPage();
			}
		}
	},this));
};

CPageSwitcherViewModel.prototype.hide = function ()
{
	this.shown = false;
};

CPageSwitcherViewModel.prototype.show = function ()
{
	this.shown = true;
};

CPageSwitcherViewModel.prototype.clear = function ()
{
	this.currentPage(1);
	this.count(0);
};

/**
 * @param {number} iCount
 */
CPageSwitcherViewModel.prototype.setCount = function (iCount)
{
	this.count(iCount);
	if (this.currentPage() > this.pagesCount())
	{
		this.currentPage(this.pagesCount());
	}
};

/**
 * @param {number} iPage
 * @param {number} iPerPage
 */
CPageSwitcherViewModel.prototype.setPage = function (iPage, iPerPage)
{
	this.perPage(iPerPage);
	if (iPage > this.pagesCount())
	{
		this.currentPage(this.pagesCount());
	}
	else
	{
		this.currentPage(iPage);
	}
};

/**
 * @param {Object} oPage
 */
CPageSwitcherViewModel.prototype.clickPage = function (oPage)
{
	var iPage = oPage.number;
	if (iPage < 1)
	{
		iPage = 1;
	}
	if (iPage > this.pagesCount())
	{
		iPage = this.pagesCount();
	}
	this.currentPage(iPage);
};

CPageSwitcherViewModel.prototype.clickFirstPage = function ()
{
	this.currentPage(1);
};

CPageSwitcherViewModel.prototype.clickPreviousPage = function ()
{
	var iPrevPage = this.currentPage() - 1;
	if (iPrevPage < 1)
	{
		iPrevPage = 1;
	}
	this.currentPage(iPrevPage);
};

CPageSwitcherViewModel.prototype.clickNextPage = function ()
{
	var iNextPage = this.currentPage() + 1;
	if (iNextPage > this.pagesCount())
	{
		iNextPage = this.pagesCount();
	}
	this.currentPage(iNextPage);
};

CPageSwitcherViewModel.prototype.clickLastPage = function ()
{
	this.currentPage(this.pagesCount());
};
/**
 * @constructor
 */
function CHelpdeskLoginViewModel()
{
	this.emailFocus = ko.observable(false);
	this.email = ko.observable('');
	
	this.passwordFocus = ko.observable(false);
	this.password = ko.observable('');
	
	this.signMeType = ko.observable(true);
	this.signMe = ko.observable(true);
	
	this.loginDescription = ko.observable('');
	this.loginProcess = ko.observable(false);

	this.loginCustomLogo = ko.observable(AppData['HelpdeskStyleImage'] || '');
	
	this.activationDescription = ko.observable('');
	
	this.registeringProcess = ko.observable(false);

	this.regNameFocus = ko.observable(false);
	this.regName = ko.observable('');
	this.regEmailFocus = ko.observable(false);
	this.regSocialEmailFocus = ko.observable(false);
	this.regEmail = ko.observable('');
	this.regSocialEmail = ko.observable('');
	this.regPasswordFocus = ko.observable(false);
	this.regPassword = ko.observable('');
	this.regConfirmPasswordFocus = ko.observable(false);
	this.regConfirmPassword = ko.observable('');
	this.helpdeskQuestion = ko.observable('');
	this.helpdeskQuestion.subscribe(function(sText) {
		if(!sText)
		{
			App.Storage.setData('helpdeskQuestion');
		}
	}, this);
	this.helpdeskQuestionFocus = ko.observable('');

	this.signInButtonText = ko.computed( function () {
		if(this.registeringProcess())
		{
			return Utils.i18n('LOGIN/BUTTON_SIGNING_IN');
		}
		/*else if (this.helpdeskQuestion())
		{
			return Utils.i18n('Sign in and Send');
		}
		else if (!this.helpdeskQuestion())*/
		else
		{
			return Utils.i18n('LOGIN/BUTTON_SIGN_IN');
		}
	}, this);

	this.regButtonText = ko.computed( function () {
		if(this.registeringProcess())
		{
			return Utils.i18n('HELPDESK/BUTTON_REGISTERING');
		}
		/*else if (this.helpdeskQuestion())
		{
			return Utils.i18n('Register and Send');
		}
		else if (!this.helpdeskQuestion())*/
		else
		{
			return Utils.i18n('HELPDESK/BUTTON_REGISTER');
		}
	}, this);

	this.sendingPasswordProcess = ko.observable(false);
	this.forgotButtonText = ko.computed(function () {
		return this.sendingPasswordProcess() ?
			Utils.i18n('MAIN/BUTTON_SENDING') :
			Utils.i18n('HELPDESK/BUTTON_SEND_PASSWORD');
	}, this);
	this.forgotEmailFocus = ko.observable(false);
	this.forgotEmail = ko.observable('');
	
	this.changingPasswordProcess = ko.observable(false);
	this.changepassButtonText = ko.computed(function () {
		return this.changingPasswordProcess() ?
			Utils.i18n('HELPDESK/BUTTON_CHANGING_PASS') :
			Utils.i18n('HELPDESK/BUTTON_CHANGE_PASS');
	}, this);
	this.changepassNewpassFocus = ko.observable(false);
	this.changepassNewpass = ko.observable('');
	this.changepassConfirmpassFocus = ko.observable(false);
	this.changepassConfirmpass = ko.observable('');
	
	this.gotoForgot = ko.observable(false);
	this.gotoRegister = ko.observable(false);
	this.gotoSignin = ko.observable(false);
	this.gotoSocialRegister = ko.observable(false);
//	this.gotoActivation = ko.observable(false);
	this.gotoChangepass = ko.observable(AppData['HelpdeskForgotHash'] ? true : false);

	this.socialFacebook = ko.observable(AppData['SocialFacebook']);
	this.socialGoogle = ko.observable(AppData['SocialGoogle']);
	this.socialTwitter = ko.observable(AppData['SocialTwitter']);

	this.socialEmail = ko.observable(AppData['SocialEmail']);
	this.socialIsLoggedIn = ko.observable(AppData['SocialIsLoggedIn']);

	this.shake = ko.observable(false).extend({'autoResetToFalse': 800});

	this.loginCommand = Utils.createCommand(this, this.actionLogin, function () {
		return !this.loginProcess();
	});
	this.sendCommand = Utils.createCommand(this, this.actionSend, this.helpdeskQuestion);
	this.registerCommand = Utils.createCommand(this, this.actionRegister, function () {
		return !this.registeringProcess() && Utils.trim(this.regPassword()) !== '' && Utils.trim(this.regConfirmPassword()) !== ''  && Utils.trim(this.regEmail()) !== '' ;
	});
	this.forgotCommand = Utils.createCommand(this, this.actionForgot, function () {
		return !this.sendingPasswordProcess() && Utils.trim(this.forgotEmail()) !== '';
	});

	this.socialNetworkLogin();

	if (AfterLogicApi.runPluginHook)
	{
		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
	}
}

CHelpdeskLoginViewModel.prototype.__name = 'CHelpdeskLoginViewModel';

CHelpdeskLoginViewModel.prototype.onShow = function ()
{
	var sReportText = App.Storage.getData('ReportText');
	
	if (sReportText)
	{
		App.Api.showReport(sReportText);
		
		App.Storage.removeData('ReportText');
	}

	if(App.Storage.getData('helpdeskQuestion'))
	{
		this.helpdeskQuestion(App.Storage.getData('helpdeskQuestion'));
	}

	$html.addClass('non-adjustable-valign');
};

CHelpdeskLoginViewModel.prototype.onHide = function ()
{
	this.loginProcess(false);
	this.registeringProcess(false);
	this.sendingPasswordProcess(false);
	this.changingPasswordProcess(false);
	
	this.email('');
	this.password('');
	this.regEmail('');
	this.regSocialEmail('');
	this.regName('');
	this.regPassword('');
	this.regConfirmPassword('');
	this.forgotEmail('');
	this.changepassNewpass('');
	this.changepassConfirmpass('');
	this.helpdeskQuestion('');

	this.gotoForgot(false);
	this.gotoRegister(false);
	this.gotoSignin(false);
	this.gotoSocialRegister(false);
//	this.gotoActivation(false);
	this.gotoChangepass(false);

};

CHelpdeskLoginViewModel.prototype.socialNetworkLogin = function ()
{
	this.regSocialEmail(this.socialEmail());

	if (this.socialIsLoggedIn()) {
		this.gotoSocialRegister(true);
	}
};

CHelpdeskLoginViewModel.prototype.onSocialClick = function (sSocial)
{
	this.storeQuestion();
	$.cookie('external-services-redirect', 'helpdesk');
	if (window !== window.top)
	{
		var
			x = screen.width/2 - 700/2,
			y = screen.height/2 - 600/2
		;

		window.open(Utils.Common.getAppPath() + '?external-services=' + sSocial + '&scopes=login', sSocial, 'location=no,toolbar=no,status=no,scrollbars=yes,resizable=yes,menubar=no,width=700,height=600,left=' + x + ',top=' + y);
	}
	else
	{
		window.location.href = '?external-services=' + sSocial + '&scopes=login';
	}
};

CHelpdeskLoginViewModel.prototype.storeQuestion = function ()
{
	if(this.helpdeskQuestion() !== '')
	{
		App.Storage.setData('helpdeskQuestion', this.helpdeskQuestion());
	}
};

CHelpdeskLoginViewModel.prototype.actionSend = function ()
{
	this.storeQuestion();
	this.gotoRegister(true);
};

CHelpdeskLoginViewModel.prototype.actionLogin = function ()
{
	$('.check_autocomplete_input').trigger('input').trigger('change').trigger('keydown');

	if (Utils.trim(this.password()) && '' !== Utils.trim(this.email()))
	{
		this.storeQuestion();

		this.loginProcess(true);

		App.Ajax.sendExt({
			'Action': 'HelpdeskLogin',
			'Email': this.email(),
			'Password': this.password(),
			'SignMe': this.signMe() ? '1' : '0'
		}, this.onHelpdeskLoginResponse, this);
	}
	else
	{
		this.shake(true);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskLoginViewModel.prototype.onHelpdeskLoginResponse = function (oResponse, oRequest)
{
	this.loginProcess(false);
	
	if (oResponse.Result)
	{
		window.location.reload();
	}
	else
	{
		if (oResponse.ErrorCode === Enums.Errors.HelpdeskThrowInWebmail)
		{
			window.location.href = '';
		}
		else
		{
			if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
			{
				oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
			}

			App.Api.showErrorByCode(oResponse, Utils.i18n('HELPDESK/ERROR_LOGIN_FAILED'));

			this.shake(true);
			this.emailFocus(true);
		}
	}
};

CHelpdeskLoginViewModel.prototype.actionRegister = function ()
{
	if (this.regPassword() !== this.regConfirmPassword())
	{
		App.Api.showError(Utils.i18n('WARNING/PASSWORDS_DO_NOT_MATCH'));
		this.regPasswordFocus(true);
	}
	else
	{
		this.registeringProcess(true);

		App.Ajax.sendExt({
			'Action': 'HelpdeskRegister',
			'Email': this.regEmail(),
			'Password': this.regPassword(),
			'Name': this.regName()
		}, this.onHelpdeskRegisterResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskLoginViewModel.prototype.onHelpdeskRegisterResponse = function (oResponse, oRequest)
{	
	this.registeringProcess(false);

	if (oResponse.Result === false)
	{
		if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
		{
			oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
		}

		App.Api.showErrorByCode(oResponse, Utils.i18n('HELPDESK/ERROR_REGISTRATION_FAILED'));

		this.regEmailFocus(true);
	}
	else
	{
		App.Api.showReport(Utils.i18n('HELPDESK/ACTIVATION_DESCRIPTION', {
			'EMAIL': this.regEmail()
		}));

		this.gotoRegister(false);
	}
};

CHelpdeskLoginViewModel.prototype.actionSocialRegister = function ()
{
	if (!this.registeringProcess())
	{
		if (this.regSocialEmail() === '')
		{
			this.regSocialEmailFocus(true);
			return;
		}

		this.registeringProcess(true);

		App.Ajax.sendExt({
			'Action': 'SocialRegister',
			'NotificationEmail': this.regSocialEmail()
		}, this.onHelpdeskSocialRegisterResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskLoginViewModel.prototype.onHelpdeskSocialRegisterResponse = function (oResponse, oRequest)
{
	this.registeringProcess(false);

	if (oResponse.Result === false)
	{
		if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
		{
			oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
		}

		App.Api.showErrorByCode(oResponse, Utils.i18n('HELPDESK/ERROR_REGISTRATION_FAILED'));

		this.regSocialEmailFocus(true);
	}
	else
	{
		if(AppData['TenantHash'])
		{
			window.location.href = '?helpdesk=' + AppData['TenantHash'];
		}
		else
		{
			window.location.href = '?helpdesk';
		}
	}
};

CHelpdeskLoginViewModel.prototype.actionForgot = function ()
{
	this.sendingPasswordProcess(true);

	App.Ajax.sendExt({
		'Action': 'HelpdeskForgot',
		'Email': this.forgotEmail()
	}, this.onHelpdeskForgotResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskLoginViewModel.prototype.onHelpdeskForgotResponse = function (oResponse, oRequest)
{
	this.sendingPasswordProcess(false);
	
	if (oResponse.Result === false)
	{
		if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
		{
			oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
		}

		App.Api.showErrorByCode(oResponse, Utils.i18n('HELPDESK/ERROR_FORGOT_FAILED'));
		
		this.forgotEmailFocus(true);
	}
	else
	{
		App.Api.showReport(Utils.i18n('HELPDESK/INFO_FORGOT_SUCCESSFULL'));

		this.email(this.forgotEmail());
		this.passwordFocus(true);
		_.delay(_.bind(function () {this.forgotEmail('');}, this), 500);

		this.gotoForgot(false);
	}
};

CHelpdeskLoginViewModel.prototype.backToLogin = function ()
{
	location.replace('?helpdesk=' + AppData['TenantHash']);
};

CHelpdeskLoginViewModel.prototype.actionChangepass = function ()
{
	if (!this.changingPasswordProcess())
	{
		var
			oParameters = {
				'Action': 'HelpdeskForgotChangePassword',
				'TenantHash': AppData['TenantHash'],
				'ActivateHash': AppData['HelpdeskForgotHash'],
				'NewPassword': this.changepassNewpass()
			}
		;

		if (this.changepassNewpass() === '')
		{
			this.changepassNewpassFocus(true);
			return;
		}

		if (this.changepassConfirmpass() === '')
		{
			this.changepassConfirmpassFocus(true);
			return;
		}

		if (this.changepassNewpass() !== this.changepassConfirmpass())
		{
			App.Api.showError(Utils.i18n('WARNING/PASSWORDS_DO_NOT_MATCH'));
			this.changepassNewpassFocus(true);
			return;
		}

		this.changingPasswordProcess(true);

		App.Ajax.sendExt(oParameters, this.onHelpdeskForgotChangePasswordResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskLoginViewModel.prototype.onHelpdeskForgotChangePasswordResponse = function (oResponse, oRequest)
{
	this.changingPasswordProcess(false);
	
	if (oResponse.Result === false)
	{
		if (oResponse.ErrorCode === Enums.Errors.NotDisplayedError)
		{
			oResponse.ErrorCode = Enums.Errors.DataTransferFailed;
		}

		App.Api.showErrorByCode(oResponse, Utils.i18n('HELPDESK/ERROR_CHANGEPASS_FAILED'));

		this.changepassNewpassFocus(true);
	}
	else
	{
		App.Storage.setData('ReportText', Utils.i18n('HELPDESK/INFO_CHANGEPASS_SUCCESSFULL'));
		
		this.backToLogin();
	}
};


/**
 * @constructor
 */
function CHelpdeskHeaderViewModel()
{
	this.sThreadsHash = App.Routing.buildHashFromArray([Enums.Screens.Helpdesk]);
	this.settingsHash = App.Routing.lastSettingsHash;
}

CHelpdeskHeaderViewModel.prototype.logout = function ()
{
	App.logout();
};
/**
 * @constructor
 */
function CHelpdeskViewModel()
{
	var
		self = this,
		fChangeStateHelper = function(state) {
			return function () {
				self.executeChangeState(state);
				self.isQuickReplyHidden(!self.bAgent);

				if (state === Enums.HelpdeskThreadStates.Resolved)
				{
					self.selectedItem(null);
					App.Routing.setHash([Enums.Screens.Helpdesk, '']);
				}
			};
		}
	;

	//use different ajax functions for different application
	this.bRtl = Utils.isRTL();

	this.iAutoCheckTimer = 0;

	this.bExtApp = bExtApp;
	this.ajaxSendFunc = this.bExtApp ? 'sendExt' : 'send';
	this.bAgent = AppData.User.IsHelpdeskAgent;
	this.singleMode = AppData.SingleMode;

	this.externalUrl = ko.observable(AppData.HelpdeskIframeUrl);

	this.signature = AppData.User.helpdeskSignature;
	this.signatureEnable = AppData.User.helpdeskSignatureEnable;
	this.isSignatureVisible = ko.computed(function () {
		return this.signature() !== '' && this.signatureEnable() === '1';
	}, this);

	this.loadingList = ko.observable(true);
	this.loadingViewPane = ko.observable(false);
	this.loadingMoreMessages = ko.observable(false);

	this.threads = ko.observableArray([]);
	this.posts = ko.observableArray([]);

	this.iPingInterval = -1;
	this.iPingStartTimer = -1;
	this.selectedItem = ko.observable(null);
	this.previousSelectedItem = ko.observable(null);
	this.postForDelete = ko.observable(null);
	this.state = ko.observable(0);
	this.selectedItem.subscribe(function (oItem) {
		this.state(oItem ? oItem.state() : 0);
		this.subject(this.selectedItem() ? (this.bExtApp ? this.selectedItem().sSubject : this.selectedItem().sFromFull) : '');
		this.internalNote(false);

		if (!this.bExtApp && this.selectedItem())
		{
			App.ContactsCache.getContactsByEmails([this.selectedItem().sEmail], this.onOwnerContactResponse, this);
		}

		clearInterval(this.iPingInterval);
		clearTimeout(this.iPingStartTimer);
		this.watchers([]);
		if (this.selectedItem())
		{
			this.iPingStartTimer = setTimeout(_.bind(function () {
				this.executeThreadPing(this.selectedItem().Id);

				clearInterval(this.iPingInterval);
				this.iPingInterval = setInterval(_.bind(function () {
					this.executeThreadPing(this.selectedItem().Id);
				}, this), 180000);
			}, this), 5000);
		}
	}, this);

	this.listFilter = ko.observable(this.bAgent ? Enums.HelpdeskFilters.Open : Enums.HelpdeskFilters.All);
	this.listFilter.subscribe(function () {
		this.requestThreadsList();
	}, this);
	this.prevListFilter = ko.observable('');

	this.hasMorePosts = ko.computed(function () {
		var oItem = this.selectedItem();
		return oItem && oItem.postsCount() > this.posts().length;
	}, this).extend({ throttle: 1 });

	//list selector
	this.selector = new CSelector(
		this.threads,
		_.bind(this.onItemSelect, this),
		_.bind(this.onItemDelete, this),
		null, null, null, false, false, false, true
	);

	this.checkStarted = ko.observable(false);

	this.checkAll = this.selector.koCheckAll();
	this.checkAllIncomplite = this.selector.koCheckAllIncomplete();

	this.ThreadsPerPage = 10;
	//TODO use own PerPage param
	this.oPageSwitcher = new CPageSwitcherViewModel(0, this.ThreadsPerPage);

	this.oPageSwitcher.currentPage.subscribe(function () {
		this.requestThreadsList();
	}, this);

	//search
	this.isSearchFocused = ko.observable(false);
	this.search = ko.observable('');

	this.searchText = ko.computed(function () {
		return Utils.i18n('HELPDESK/INFO_SEARCH_RESULT', {
			'SEARCH': this.search()
		});
	}, this);

	//commands
	this.deleteCommand = Utils.createCommand(this, this.executeDelete, this.isEnableListActions);

	this.openNewWindowCommand = Utils.createCommand(this, this.executeOpenNewWindow, this.isEnableListActions);

	this.checkCommand = Utils.createCommand(this, function () {
		this.requestThreadsList();
		this.requestPosts();
		this.startAutocheckmail();
	});

	this.closeCommand = Utils.createCommand(this, fChangeStateHelper(Enums.HelpdeskThreadStates.Resolved), this.isEnableListActions);
	this.waitCommand = Utils.createCommand(this, fChangeStateHelper(Enums.HelpdeskThreadStates.Waiting), this.isEnableListActions);
	this.pendingCommand = Utils.createCommand(this, fChangeStateHelper(Enums.HelpdeskThreadStates.Pending), this.isEnableListActions);
	this.deferCommand = Utils.createCommand(this, fChangeStateHelper(Enums.HelpdeskThreadStates.Deferred), this.isEnableListActions);
	this.answerCommand = Utils.createCommand(this, fChangeStateHelper(Enums.HelpdeskThreadStates.Answered), this.isEnableListActions);

	this.postCommand = Utils.createCommand(this, this.executePostCreate, function () {
		return !!this.selectedItem() &&
			!this.isQuickReplyPaneEmpty() &&
			this.allAttachmentsUploaded();
	});

	this.visibleNewThread = ko.observable(false);
	this.newThreadId = ko.observable(0);
	this.newThreadText = ko.observable('');
	this.newThreadCreating = ko.observable(false);
	this.domNewThreadTextarea = ko.observable(null);
	this.newThreadTextFocus = ko.observable(null);
	this.createThreadCommand = Utils.createCommand(this, this.executeThreadCreate, function () {
		return this.visibleNewThread() && this.newThreadText().length > 0 && !this.newThreadCreating();
	});
	this.createThreadButtonText = ko.computed(function () {
		return this.newThreadCreating() ?
			Utils.i18n('MAIN/BUTTON_SENDING') :
			Utils.i18n('HELPDESK/BUTTON_CREATE');
	}, this);

	this.commandGetOlderPosts = function () {
		var
			aList = this.posts(),
			iPostId  = aList[0] ? aList[0].Id : 0
		;

		this.requestPosts(null, iPostId);
	};

	this.externalContentUrl = ko.observable('');

	if (AppData.HelpdeskIframeUrl)
	{
		if (this.bAgent)
		{
			this.externalContentUrl = ko.computed(function () {

				var
					sEmail = '',
					oSelected = this.selectedItem()
				;

				if (oSelected)
				{
					sEmail = oSelected.Email();
				}

				if (sEmail)
				{
					return AppData.HelpdeskIframeUrl.replace(/\[EMAIL\]/g, sEmail);
				}

				return '';

			}, this);
		}
		else if (AppData.User.Email)
		{
			this.externalContentUrl = ko.computed(function () {
				return AppData.HelpdeskIframeUrl.replace(/\[EMAIL\]/g, AppData.User.Email);
			}, this);
		}
	}

	// view pane
	this.clientDetailsVisible = ko.observable(
		App.Storage.hasData('HelpdeskUserDetails') ? App.Storage.getData('HelpdeskUserDetails') : true);

	this.clientDetailsVisible.subscribe(function (value) {
		App.Storage.setData('HelpdeskUserDetails', value);
	}, this);

	this.subject = ko.observable('');
	this.watchers = ko.observableArray([]);
	this.ownerExistsInContacts = ko.observable(false);
	this.ownerContactInfoReceived = ko.observable(false);
	this.ownerContact = ko.observable(!this.bExtApp ? new CContactModel() : null);
	this.hasOwnerContact = ko.computed(function () {
		return !this.singleMode && this.ownerContactInfoReceived() && this.ownerExistsInContacts();
	}, this);
	this.visibleAddToContacts = ko.computed(function () {
		return !this.singleMode && this.ownerContactInfoReceived() && !this.ownerExistsInContacts();
	}, this);

	this.contactCardWidth = ko.observable(0);

	this.uploadedFiles = ko.observableArray([]);
	this.allAttachmentsUploaded = ko.computed(function () {
		var
			aNotUploadedFiles = _.filter(this.uploadedFiles(), function (oFile) {
				return !oFile.uploaded();
			})
		;

		return aNotUploadedFiles.length === 0;
	}, this);
	this.uploaderButton = ko.observable(null);
	this.uploaderButtonCompose = ko.observable(null);
	this.uploaderArea = ko.observable(null);
	this.bDragActive = ko.observable(false);//.extend({'throttle': 1});

	this.internalNote = ko.observable(false);

	this.ccbccVisible = ko.observable(false);
	/*this.ccbccVisible.subscribe(function () {
		_.defer(_.bind(function () {
			$(this.ccAddrDom()).inputosaurus('resizeInput');
			$(this.bccAddrDom()).inputosaurus('resizeInput');
		}, this));
	}, this);*/
	this.ccAddrDom = ko.observable();
	this.ccAddrDom.subscribe(function () {
		this.initInputosaurus(this.ccAddrDom, this.ccAddr, this.lockCcAddr, 'cc');
	}, this);
	this.lockCcAddr = ko.observable(false);
	this.ccAddr = ko.observable('').extend({'reversible': true});
	this.ccAddr.subscribe(function () {
		if (!this.lockCcAddr())
		{
			$(this.ccAddrDom()).val(this.ccAddr());
			$(this.ccAddrDom()).inputosaurus('refresh');
		}
	}, this);
	this.bccAddrDom = ko.observable();
	this.bccAddrDom.subscribe(function () {
		this.initInputosaurus(this.bccAddrDom, this.bccAddr, this.lockBccAddr, 'bcc');
	}, this);
	this.lockBccAddr = ko.observable(false);
	this.bccAddr = ko.observable('').extend({'reversible': true});
	this.bccAddr.subscribe(function () {
		if (!this.lockBccAddr())
		{
			$(this.bccAddrDom()).val(this.bccAddr());
			$(this.bccAddrDom()).inputosaurus('refresh');
		}
	}, this);

	this.preventFalseClick = ko.observable(false).extend({'autoResetToFalse': 500});

	this.isQuickReplyHidden = ko.observable(!this.bAgent);
	this.domQuickReply = ko.observable(null);
	this.domQuickReplyTextarea = ko.observable(null);
	this.replySendingStarted = ko.observable(false);
	this.replyPaneVisible = ko.observable(true);
	this.replyText = ko.observable('');
	this.replyTextFocus = ko.observable(false);
	this.isQuickReplyActive = ko.observable(false);
	this.replyTextFocus.subscribe(function (bFocus) {
		if (bFocus)
		{
			this.isQuickReplyActive(true);
			this.setSignature(this.replyText, this.domQuickReplyTextarea());
		}
	}, this);
	this.isQuickReplyActive.subscribe(function () {
		if (this.isQuickReplyActive())
		{
			this.replyTextFocus(true);
		}
	}, this);
	this.isQuickReplyPaneEmpty = ko.computed(function () {
		return (Utils.trim(this.replyText()) === this.signature() || this.replyText() === '');
	}, this);

	this.isNewThreadPaneEmpty = ko.computed(function () {
		return (Utils.trim(this.newThreadText()) === this.signature() || this.newThreadText() === '');
	}, this);

	// view pane //

	this.isSearch = ko.computed(function () {
		return '' !== this.search();
	}, this);

	this.isEmptyList = ko.computed(function () {
		return 0 === this.threads().length;
	}, this);

	if (this.bAgent)
	{
		this.dynamicEmptyListInfo = ko.computed(function () {
			return this.isEmptyList() && this.isSearch() ?
				Utils.i18n('HELPDESK/INFO_SEARCH_EMPTY') : Utils.i18n('HELPDESK/INFO_EMPTY_OPEN_THREAD_LIST_AGENT');
		}, this);
	}
	else
	{
		this.dynamicEmptyListInfo = ko.computed(function () {
			return this.isEmptyList() && this.isSearch() ?
				Utils.i18n('HELPDESK/INFO_SEARCH_EMPTY') : Utils.i18n('HELPDESK/INFO_EMPTY_THREAD_LIST');
		}, this);
	}

	this.simplePreviewPane = ko.computed(function () { //TODO on first load oItem is null therefore loaded the wrong template - Helpdesk_ViewThread
		var oItem = this.selectedItem();
		return oItem ? oItem.ItsMe : !this.bAgent;
	}, this);

	this.allowInternalNote = ko.computed(function () {
		return !this.simplePreviewPane();
	}, this);

	this.scrollToTopTrigger = ko.observable(false);
	this.scrollToBottomTrigger = ko.observable(false);

	this.allowDownloadAttachmentsLink = false;

	this.newThreadButtonWidth = ko.observable(0);

	this.focusedField = ko.observable();

	this.requestFromLogin();
}

/**
 * @param {Object} koAddrDom
 * @param {Object} koAddr
 * @param {Object} koLockAddr
 * @param {String} sFocusedField
 */
CHelpdeskViewModel.prototype.initInputosaurus = function (koAddrDom, koAddr, koLockAddr, sFocusedField)
{
	if (koAddrDom() && $(koAddrDom()).length > 0)
	{
		$(koAddrDom()).inputosaurus({
			width: 'auto',
			parseOnBlur: true,
			autoCompleteSource: _.bind(function (oData, fResponse) {
				this.autocompleteCallback(oData.term, fResponse);
			}, this),
			autoCompleteAppendTo : $(koAddrDom()).closest('td'),
			change : _.bind(function (ev) {
				koLockAddr(true);
				this.setRecipient(koAddr, ev.target.value);
				koLockAddr(false);
			}, this),
			copy: _.bind(function (sVal) {
				this.inputosaurusBuffer = sVal;
			}, this),
			paste: _.bind(function () {
				var sInputosaurusBuffer = this.inputosaurusBuffer || '';
				this.inputosaurusBuffer = '';
				return sInputosaurusBuffer;
			}, this),
			focus: _.bind(this.focusedField, this, sFocusedField),
			mobileDevice: bMobileDevice
		});
	}
};

/**
 * @param {string} sTerm
 * @param {Function} fResponse
 */
CHelpdeskViewModel.prototype.autocompleteCallback = function (sTerm, fResponse)
{
	var
		oParameters = {
			'Action': 'ContactSuggestions',
			'Search': sTerm
		}
		;

	App.Ajax.send(oParameters, function (oResponse) {

		var aList = [];
		if (oResponse && oResponse.Result && oResponse.Result && oResponse.Result.List)
		{
			aList = _.map(oResponse.Result.List, function (oItem) {

				var
					sLabel = '',
					sValue = oItem.Email
					;

				if (oItem.IsGroup)
				{
					if (oItem.Name && 0 < Utils.trim(oItem.Name).length)
					{
						sLabel = '"' + oItem.Name + '" (' + oItem.Email + ')';
					}
					else
					{
						sLabel = '(' + oItem.Email + ')';
					}
				}
				else
				{
					sLabel = Utils.Address.getFullEmail(oItem.Name, oItem.Email);
					sValue = sLabel;
				}

				return {'label': sLabel, 'value': sValue, 'frequency': oItem.Frequency};
			});

			aList = _.compact(aList);
		}

		fResponse(aList);

	}, this);
};

/**
 * @param {Object} koRecipient
 * @param {string} sRecipient
 */
CHelpdeskViewModel.prototype.setRecipient = function (koRecipient, sRecipient)
{
	if (koRecipient() === sRecipient)
	{
		koRecipient.valueHasMutated();
	}
	else
	{
		koRecipient(sRecipient);
	}
};

CHelpdeskViewModel.prototype.requestFromLogin = function ()
{
	if (this.bExtApp && App.Storage.getData('helpdeskQuestion'))
	{
		this.newThreadText(App.Storage.getData('helpdeskQuestion'));
		App.Storage.removeData('helpdeskQuestion');
		this.executeThreadCreate();
	}
};

CHelpdeskViewModel.prototype.cleanAll = function ()
{
	this.replyText('');
	this.replyTextFocus(false);
	this.newThreadText('');
	this.uploadedFiles([]);
	this.posts([]);
	this.internalNote(false);
	this.isQuickReplyActive(false);
	this.ccbccVisible(false);
	this.ccAddr('');
	this.bccAddr('');
	//this.setRecipient(this.ccAddr, '');
	//this.setRecipient(this.bccAddr, '');
};

/**
 * @param {Object} oContact
 */
CHelpdeskViewModel.prototype.onOwnerContactResponse = function (oContact)
{
	if (oContact)
	{
		this.ownerContact(oContact);
		this.ownerExistsInContacts(true);
	}
	else
	{
		this.ownerContact(new CContactModel());
		this.ownerExistsInContacts(false);
	}

	this.ownerContactInfoReceived(true);
};

CHelpdeskViewModel.prototype.updateOpenerWindow = function ()
{
	if (this.singleMode && window.opener && window.opener.App)
	{
		window.opener.App.updateHelpdesk();
	}
};

/**
 * @param {Object} oPost
 */
CHelpdeskViewModel.prototype.deletePost = function (oPost)
{
	if (oPost && oPost.itsMe())
	{
		App.Screens.showPopup(ConfirmPopup, [Utils.i18n('HELPDESK/CONFIRM_DELETE_THIS_POST'),
			_.bind(function (bResult) {
				if (bResult)
				{
					this.postForDelete(oPost);
					App.Ajax[this.ajaxSendFunc]({
						'Action': 'HelpdeskPostDelete',
						'PostId': oPost.Id,
						'ThreadId': oPost.IdThread,
						'IsExt': this.bExtApp ? 1 : 0
					}, this.onHelpdeskPostDeleteResponse, this);
				}
			}, this)
		]);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskViewModel.prototype.onHelpdeskPostDeleteResponse = function (oResponse, oRequest)
{
	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('HELPDESK/ERROR_COULDNT_DELETE_POST'));
	}
	else
	{
		this.posts.remove(this.postForDelete());
		App.Api.showReport(Utils.i18n('HELPDESK/REPORT_POST_HAS_BEEN_DELETED'));
	}

	this.requestPosts();
	this.updateOpenerWindow();
};

CHelpdeskViewModel.prototype.addToContacts = function ()
{
	if (this.selectedItem())
	{
		App.ContactsCache.addToContacts('', this.selectedItem().sEmail, this.onAddToContactsResponse, this);
	}
};

CHelpdeskViewModel.prototype.iHaveMoreToSay = function ()
{
	this.isQuickReplyHidden(false);
	_.delay(_.bind(function () {
		this.replyTextFocus(true);
	}, this), 300);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskViewModel.prototype.onAddToContactsResponse = function (oResponse, oRequest)
{
	if (oResponse.Result && this.selectedItem() && oRequest.HomeEmail !== '' && oRequest.HomeEmail === this.selectedItem().sEmail)
	{
		App.Api.showReport(Utils.i18n('CONTACTS/REPORT_CONTACT_SUCCESSFULLY_ADDED'));
		App.ContactsCache.clearInfoAboutEmail(this.selectedItem().sEmail);
		App.ContactsCache.getContactsByEmails([this.selectedItem().sEmail], this.onOwnerContactResponse, this);
	}
};

CHelpdeskViewModel.prototype.scrollPostsToBottom = function ()
{
	this.scrollToBottomTrigger(!this.scrollToBottomTrigger());
};

CHelpdeskViewModel.prototype.scrollPostsToTop = function ()
{
	this.scrollToTopTrigger(!this.scrollToTopTrigger());
};

CHelpdeskViewModel.prototype.showClientDetails = function ()
{
	this.clientDetailsVisible(true);
};

CHelpdeskViewModel.prototype.hideClientDetails = function ()
{
	this.clientDetailsVisible(false);
};

CHelpdeskViewModel.prototype.startAutocheckmail = function ()
{
	var self = this, iIntervalInMin = AppData && AppData.User ? AppData.User.AutoCheckMailInterval : 1;
	if (0 < iIntervalInMin)
	{
		clearTimeout(this.iAutoCheckTimer);
		this.iAutoCheckTimer = setTimeout(function () {
			self.checkCommand();
		}, iIntervalInMin * 60 * 1000);
	}
};

/**
 * @param {Object} $viewModel
 */
CHelpdeskViewModel.prototype.onApplyBindings = function ($viewModel)
{
	this.selector.initOnApplyBindings(
		'.items_sub_list .item',
		'.items_sub_list .selected.item',
		'.items_sub_list .item .custom_checkbox',
		$('.items_list', $viewModel),
		$('.threads_scroll.scroll-inner', $viewModel)
	);

	this.initUploader();

	$(this.domQuickReply()).on('click', _.bind(function (oEvent) {
		this.preventFalseClick(true);
	}, this));

	$(document.body).on('click', _.bind(function (oEvent) {
		if (App.Screens.currentScreen() === Enums.Screens.Helpdesk && this.isQuickReplyPaneEmpty() && !this.preventFalseClick())
		{
			this.replyText('');
			this.isQuickReplyActive(false);
		}
	}, this));

	if (App.registerHelpdeskUpdateFunction)
	{
		App.registerHelpdeskUpdateFunction(_.bind(this.checkCommand, this));
	}

	this.startAutocheckmail();
};

CHelpdeskViewModel.prototype.onShow = function ()
{
	this.newThreadButtonWidth.notifySubscribers();
	this.selector.useKeyboardKeys(true);

	this.oPageSwitcher.show();
	this.oPageSwitcher.perPage(this.ThreadsPerPage);
	this.oPageSwitcher.currentPage(1);

	this.requestThreadsList();
};

CHelpdeskViewModel.prototype.onHide = function ()
{
	this.selector.useKeyboardKeys(false);
	this.oPageSwitcher.hide();
};

CHelpdeskViewModel.prototype.requestThreadsList = function ()
{
	if (!this.newThreadCreating()) {
		this.loadingList(true);
		this.checkStarted(true);

		App.Ajax[this.ajaxSendFunc]({
			'Action': 'HelpdeskThreadsList',
			'IsExt': this.bExtApp ? 1 : 0,
			'Offset': (this.oPageSwitcher.currentPage() - 1) * this.ThreadsPerPage,
			'Limit': this.ThreadsPerPage,
			'Filter': this.listFilter(),
			'Search': this.search()
		}, this.onHelpdeskThreadsListResponse, this);

		this.requestThreadsPendingCount();
	}
};

CHelpdeskViewModel.prototype.requestThreadByIdOrHash = function (iThreadId, sThreadHash)
{
	App.Ajax[this.ajaxSendFunc]({
		'Action': 'HelpdeskThreadByIdOrHash',
		'IsExt': this.bExtApp ? 1 : 0,
		'ThreadId': iThreadId ? iThreadId : 0,
		'ThreadHash': sThreadHash ? sThreadHash : ''
	}, this.onThreadByIdOrHashResponse, this);
};

CHelpdeskViewModel.prototype.requestThreadsPendingCount = function ()
{
	App.Ajax[this.ajaxSendFunc]({
		'Action': 'HelpdeskThreadsPendingCount',
		'IsExt': this.bExtApp ? 1 : 0
	}, this.onHelpdeskThreadsPendingCountResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskViewModel.prototype.onHelpdeskThreadsListResponse = function (oResponse, oRequest)
{
	var
		iIndex = 0,
		iLen = 0,
		oSelectedItem = this.selectedItem(),
		sSelectedId = oSelectedItem ? Utils.pString(oSelectedItem.Id) : '',
		aList = [],
		oObject = null,
		oThreadForSelect = null,
		aThreadList = (oResponse.Result && _.isArray(oResponse.Result.List)) ? oResponse.Result.List : []
	;

	this.checkStarted(false);

	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse);
	}
	else
	{
		for (iLen = aThreadList.length; iIndex < iLen; iIndex++)
		{
			if (aThreadList[iIndex] && 'Object/CHelpdeskThread' === Utils.pExport(aThreadList[iIndex], '@Object', ''))
			{
				oObject = new CThreadListModel();
				oObject.parse(aThreadList[iIndex]);
				oObject.OwnerIsMe = Utils.pString(oObject.IdOwner);

				if (sSelectedId === Utils.pString(oObject.Id))
				{
					oSelectedItem.postsCount(oObject.postsCount());

					oObject.selected(true);
					this.selector.itemSelected(oObject);
				}

				aList.push(oObject);
			}
		}

		this.loadingList(false);

		if (this.newThreadId()) {
			var iThreadId = this.newThreadId();

			this.onItemSelect( _.find(this.threads().concat(aList), function(oItem){ return oItem.ItsMe && oItem.Id === iThreadId; }));
			this.newThreadId(null);
		}

		this.threads(aList);
		this.setUnseenCount();

		this.oPageSwitcher.setCount(Utils.pInt(oResponse.Result.ItemsCount));

		if (AppData.HelpdeskThreadId)
		{
			oThreadForSelect = _.find(aList, function (oThreadItem) {
				return oThreadItem.Id === AppData.HelpdeskThreadId;
			}, this);

			if (oThreadForSelect)
			{
				this.onItemSelect(oThreadForSelect);
			}
			else if (aList.length)
			{
				this.requestThreadByIdOrHash(AppData.HelpdeskThreadId);
			}
		}

		if (AppData.HelpdeskThreadAction)
		{
			if (AppData.HelpdeskThreadAction === 'add')
			{
				this.iHaveMoreToSay();
			}
			else if (AppData.HelpdeskThreadAction === 'close')
			{
				this.closeCommand();
			}
		}
	}
};

CHelpdeskViewModel.prototype.onHelpdeskThreadsPendingCountResponse = function (oResponse, oRequest)
{
	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse);
	}
	else
	{
		App.helpdeskPendingCount(oResponse.Result);
	}
};

/**
 * @param {Object=} oItem = undefined
 * @param {number=} iStartFromId = 0
 */
CHelpdeskViewModel.prototype.requestPosts = function (oItem, iStartFromId)
{
	var
		oSelectedThread = this.selectedItem(),
		iId = oItem ? oItem.Id : (oSelectedThread ? oSelectedThread.Id : 0),
		iFromId = iStartFromId ? iStartFromId : 0,
		oParameters = {}
	;

	if (iId)
	{
		oParameters = {
			'Action': 'HelpdeskThreadPosts',
			'IsExt': this.bExtApp ? 1 : 0,
			'ThreadId': iId,
			'StartFromId': iFromId,
			'Limit': 5
		};

		if (iFromId)
		{
			this.loadingMoreMessages(true);
		}

		App.Ajax[this.ajaxSendFunc](oParameters, this.onHelpdeskThreadPostsResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskViewModel.prototype.onHelpdeskThreadPostsResponse = function (oResponse, oRequest)
{
	var
		self = this,
		iIndex = 0,
		iLen = 0,
		aList = [],
		aPosts = [],
		oObject = null,
		aPostList = (oResponse.Result && _.isArray(oResponse.Result.List)) ? oResponse.Result.List : []
	;

	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse);
	}
	else
	{
		if (this.selectedItem() && oResponse.Result.ThreadId === this.selectedItem().Id)
		{
			this.selectedItem().postsCount(Utils.pInt(oResponse.Result.ItemsCount));

			for (iLen = aPostList.length; iIndex < iLen; iIndex++)
			{
				if (aPostList[iIndex] && 'Object/CHelpdeskPost' === Utils.pExport(aPostList[iIndex], '@Object', ''))
				{
					oObject = new CPostModel();
					oObject.parse(aPostList[iIndex]);

					aList.push(oObject);
				}
			}

			aPosts = this.posts();

			if (oResponse.Result.StartFromId)
			{
				_.each(aList, function (oItem, iIdx) {
					this.posts.unshift(oItem);
				}, this);

				this.loadingMoreMessages(false);
			}
			else
			{
				if (aPosts.length === 0 || aPosts[aPosts.length - 1].Id !== aList[0].Id) //check match last items
				{
					if (aPosts.length !== 0)
					{
						_.each(aList.reverse(), function (oItem, iIdx) {
							if (!_.find(aPosts, function(oPost){ return oPost.Id === oItem.Id; })) //remove duplicated posts from aList
							{
								this.posts.push(oItem); //push unique/new items to list
							}
						}, this);
					}
					else
					{
						this.posts(aList.reverse()); //first/initial occurrence
					}

					_.delay(function () {
						self.scrollPostsToBottom();
					}, 100);
				}
			}

			if (this.selectedItem().unseen())
			{
				this.executeThreadSeen(this.selectedItem().Id);
			}
		}
	}
};

/**
 * @param {Array} aParams
 */
CHelpdeskViewModel.prototype.onRoute = function (aParams)
{
	var
		sThreadHash = aParams[0],
		oItem = _.find(this.threads(), function (oThread) {
			return oThread.ThreadHash === sThreadHash;
		})
	;

	if (oItem)
	{
		oItem = /** @type {Object} */ oItem;
		this.onItemSelect(oItem);
	}
	else if (this.threads().length === 0 && this.loadingList() && this.threadSubscription === undefined && !AppData.SingleMode)
	{
		this.threadSubscription = this.threads.subscribe(function () {
			this.onRoute(aParams);
			this.threadSubscription.dispose();
			this.threadSubscription = undefined;
		}, this);
	}
	else if (sThreadHash)
	{
		this.requestThreadByIdOrHash(null, sThreadHash);
	}
	else
	{
		this.selectedItem(null);
		this.selector.itemSelected(null);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskViewModel.prototype.onThreadByIdOrHashResponse = function (oResponse, oRequest)
{
	var oItem = new CThreadListModel();

	if (oResponse.Result)
	{
		oItem.parse(oResponse.Result);
		oItem.OwnerIsMe = Utils.pString(oItem.IdOwner);
		this.onItemSelect(oItem);
	}
};

/**
 * @param {Object} oItem
 */
CHelpdeskViewModel.prototype.onItemSelect = function (oItem)
{
    this.previousSelectedItem(this.selectedItem());
	if (!this.selectedItem() || oItem && (this.selectedItem().ThreadHash !== oItem.ThreadHash || this.selectedItem().Id !== oItem.Id))
	{
		if (!this.replySendingStarted() && (!this.isQuickReplyPaneEmpty() || !this.isNewThreadPaneEmpty()))
		{
			App.Screens.showPopup(ConfirmPopup, [Utils.i18n('HELPDESK/CONFIRM_CANCEL_REPLY'),
					_.bind(function (bResult) {
					if (bResult)
					{
						this.selectItem(oItem);
					}
					else
					{
						this.replyTextFocus(true);
						this.isQuickReplyHidden(false);
						this.selector.itemSelected(this.previousSelectedItem());
					}
				}, this)]
			);
		}
		else
		{
			this.selectItem(oItem);
		}
	}
};

CHelpdeskViewModel.prototype.onItemDelete = function ()
{
	this.executeDelete();
};

CHelpdeskViewModel.prototype.selectItem = function (oItem)
{
	this.visibleNewThread(false);
	this.selector.listCheckedAndSelected(false);
	this.cleanAll();

	if (oItem) {
		this.selector.itemSelected(oItem);
		this.selectedItem(oItem);

		this.isQuickReplyHidden(oItem.ItsMe || !this.bAgent);
		this.requestPosts(oItem);

		if (!this.singleMode) {
			App.Routing.setHash([Enums.Screens.Helpdesk, oItem.ThreadHash]); //TODO this code causes a bug with switching to helpdesk when you on another screen
		}
		oItem.postsCount(0);
		this.posts([]);
	}
};

CHelpdeskViewModel.prototype.openNewThread = function ()
{
	this.selector.itemSelected(null);
	this.selectedItem(null);
	this.visibleNewThread(true);
	App.Routing.setHash([Enums.Screens.Helpdesk, '']);
	this.newThreadTextFocus(true);
	this.setSignature(this.newThreadText, this.domNewThreadTextarea());
};

CHelpdeskViewModel.prototype.cancelNewThread = function ()
{
	this.onItemSelect(this.previousSelectedItem());
};

CHelpdeskViewModel.prototype.isEnableListActions = function ()
{
	return !!this.selectedItem();
};

CHelpdeskViewModel.prototype.executeDelete = function ()
{
	var
		self = this,
		oSelectedItem = this.selectedItem()
	;

	if (oSelectedItem)
	{
		_.each(this.threads(), function (oItem) {
			if (oItem === oSelectedItem)
			{
				oItem.deleted(true);
			}
		});

		_.delay(function () {
			self.threads.remove(function (oItem) {
				return oItem.deleted();
			});
		}, 500);

		this.selectedItem(null);

		App.Ajax[this.ajaxSendFunc]({
			'Action': 'HelpdeskThreadDelete',
			'IsExt': this.bExtApp ? 1 : 0,
			'ThreadId': oSelectedItem.Id
		}, this.onHelpdeskThreadDeleteResponse, this);
		
		App.Routing.setHash([Enums.Screens.Helpdesk, '']);
	}
};

CHelpdeskViewModel.prototype.executeOpenNewWindow = function ()
{
	var sUrl = App.Routing.buildHashFromArray([Enums.Screens.SingleHelpdesk, this.selectedItem().ThreadHash]);

	Utils.WindowOpener.openTab(sUrl);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskViewModel.prototype.onHelpdeskThreadDeleteResponse = function (oResponse, oRequest)
{
	this.requestThreadsList();
	this.updateOpenerWindow();
};

/**
 * @param {number} iState
 */
CHelpdeskViewModel.prototype.executeChangeState = function (iState)
{
	var oSelectedItem = this.selectedItem();

	if (iState === undefined)
	{
		return;
	}

	//TODO can't delete thread with id = 0
	if (oSelectedItem)
	{
		oSelectedItem.state(iState);

		App.Ajax[this.ajaxSendFunc]({
			'Action': 'HelpdeskThreadChangeState',
			'IsExt': this.bExtApp ? 1 : 0,
			'ThreadId': oSelectedItem.Id,
			'Type': oSelectedItem.state()
		}, this.onHelpdeskThreadChangeStateResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskViewModel.prototype.onHelpdeskThreadChangeStateResponse = function (oResponse, oRequest)
{
	this.requestThreadsList();
	this.updateOpenerWindow();
};

/**
 * @param {number} iId
 */
CHelpdeskViewModel.prototype.executeThreadPing = function (iId)
{
	if (iId !== undefined)
	{
		App.Ajax[this.ajaxSendFunc]({
			'Action': 'HelpdeskThreadPing',
			'IsExt': this.bExtApp ? 1 : 0,
			'ThreadId': iId
		}, this.onThreadPingResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskViewModel.prototype.onThreadPingResponse = function (oResponse, oRequest)
{
	this.watchers(
		_.map(oResponse.Result, function (aWatcher) {
			var
				sName = (aWatcher.length > 0) ? aWatcher[0].replace(/"/g, "") : '',
				sEmail = (aWatcher.length > 0) ? aWatcher[1] : '',
				oRes = {
					name: sName,
					email: sEmail,
					text: sEmail,
					initial: sEmail.substr(0,2),
					icon: ''
				}
			;

			if (sEmail.length > 0 && sName.length > 0)
			{
				oRes.text = '"' + sName + '" <' + sEmail + '>';
				if (/\s/g.test(sName)) //check for whitespace
				{
					oRes.initial = this.getInitials(sName);
				}
				else
				{
					oRes.initial = sName.substr(0,2);
				}
			}
			else if (sEmail.length > 0)
			{
				oRes.text = sEmail;
				oRes.initial = sEmail.substr(0,2);
			}
			else if (sName.length > 0)
			{
				oRes.text = sName;
				oRes.initial = this.getInitials(sName);
			}

			return oRes;
		}, this)
	);
};

CHelpdeskViewModel.prototype.getInitials = function (sName)
{
	return _.reduce(sName.split(' ', 2), function(sMemo, sNamePath){ return sMemo + sNamePath.substr(0,1); }, ''); //get first letter from each of the two words
};

/**
 * @param {number} iId
 */
CHelpdeskViewModel.prototype.executeThreadSeen = function (iId)
{
	if (iId !== undefined)
	{
		App.Ajax[this.ajaxSendFunc]({
			'Action': 'HelpdeskThreadSeen',
			'IsExt': this.bExtApp ? 1 : 0,
			'ThreadId': iId
		}, this.onHelpdeskThreadSeenResponse, this);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskViewModel.prototype.onHelpdeskThreadSeenResponse = function (oResponse, oRequest)
{
	if(oResponse.Result && this.selectedItem())
	{
		this.selectedItem().unseen(false);
		this.setUnseenCount();
	}

	this.updateOpenerWindow();
};

CHelpdeskViewModel.prototype.executeThreadCreate = function ()
{
	var
		sNewThreadSubject = Utils.trim(this.newThreadText().replace(/[\n\r]/, ' ')),
		iFirstSpacePos = sNewThreadSubject.indexOf(' ', 40)
	;

	if (iFirstSpacePos >= 0)
	{
		sNewThreadSubject = sNewThreadSubject.substring(0, iFirstSpacePos);
	}

	this.newThreadCreating(true);

	this.sendHelpdeskPostCreate(0, sNewThreadSubject, this.newThreadText(), this.onThreadCreateResponse);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskViewModel.prototype.onThreadCreateResponse = function (oResponse, oRequest)
{
	//TODO change created post
	this.newThreadCreating(false);

	if (oResponse.Result && oRequest)
	{
		App.Api.showReport(Utils.i18n('HELPDESK/REPORT_THREAD_SUCCESSFULLY_CREATED'));

		if (oResponse.Result.ThreadIsNew)
		{
			this.newThreadId(oResponse.Result.ThreadId);
		}

		this.cleanAll();
		this.visibleNewThread(false);
	}

	this.requestThreadsList();
	this.updateOpenerWindow();
};

CHelpdeskViewModel.prototype.executePostCreate = function ()
{
	if (this.selectedItem())
	{
		this.replySendingStarted(true);
		this.sendHelpdeskPostCreate(this.selectedItem().Id, '', this.replyText(), this.onPostCreateResponse);
	}
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskViewModel.prototype.onPostCreateResponse = function (oResponse, oRequest)
{
	this.replySendingStarted(false);

	if (oResponse.Result && oRequest)
	{
		App.Api.showReport(Utils.i18n('HELPDESK/REPORT_POST_SUCCESSFULLY_ADDED'));
		this.cleanAll();
		this.requestPosts();
	}

	this.requestThreadsList();
	this.updateOpenerWindow();
};

/**
 * @param {number} iThreadId
 * @param {string} sSubject
 * @param {string} sText
 * @param {Function} fResponseHandler
 */
CHelpdeskViewModel.prototype.sendHelpdeskPostCreate = function (iThreadId, sSubject, sText, fResponseHandler)
{
	var
		aAttachments = {},
		oParameters = {}
	;

	_.each(this.uploadedFiles(), function (oItem) {
		aAttachments[oItem.tempName()] = oItem.hash();
	});

	oParameters = {
		'Action': 'HelpdeskPostCreate',
		'IsExt': this.bExtApp ? 1 : 0,
		'ThreadId': iThreadId,
		'IsInternal': this.internalNote() ? 1 : 0,
		'Subject': sSubject,
		'Text': sText,
		'Cc': this.ccAddr(),
		'Bcc': this.bccAddr(),
		'Attachments': aAttachments
	};

	App.Ajax[this.ajaxSendFunc](oParameters, fResponseHandler, this);
};

CHelpdeskViewModel.prototype.onShowThreadsByOwner = function ()
{
	this.search('owner:' + this.selectedItem().aOwner[0]);
	this.listFilter(Enums.HelpdeskFilters.All);
	this.requestThreadsList();
};

CHelpdeskViewModel.prototype.onSearch = function ()
{
	this.requestThreadsList();
};

CHelpdeskViewModel.prototype.onClearSearch = function ()
{
	this.search('');
	this.requestThreadsList();
};

/**
 * Initializes file uploader.
 */
CHelpdeskViewModel.prototype.initUploader = function ()
{
	this.oJua = this.createJuaObject(this.uploaderButton());
	this.oJuaCompose = this.createJuaObject(this.uploaderButtonCompose());
};

/**
 * @param {Object} oButton
 */
CHelpdeskViewModel.prototype.createJuaObject = function (oButton)
{
	if (oButton)
	{
		var oJua = new Jua({
			'action': '?/Upload/HelpdeskFile/',
			'name': 'jua-uploader',
			'queueSize': 2,
			'clickElement': oButton,
			'hiddenElementsPosition': Utils.isRTL() ? 'right' : 'left',
			'dragAndDropElement': oButton,
			'disableAjaxUpload': false,
			'disableFolderDragAndDrop': false,
			'disableDragAndDrop': false,
			'hidden': {
				'IsExt': this.bExtApp ? '1' : '0',
				'Token': AppData.Token,
				'TenantHash': this.bExtApp && AppData ? AppData.TenantHash : '',
				'AccountID': this.bExtApp ? 0 : AppData.Accounts.currentId()
			}
		});

		oJua
			.on('onProgress', _.bind(this.onFileUploadProgress, this))
			.on('onSelect', _.bind(this.onFileUploadSelect, this))
			.on('onStart', _.bind(this.onFileUploadStart, this))
			.on('onComplete', _.bind(this.onFileUploadComplete, this))
			.on('onBodyDragEnter', _.bind(this.bDragActive, this, true))
			.on('onBodyDragLeave', _.bind(this.bDragActive, this, false))
		;

		return oJua;
	}
	else
	{
		return null;
	}
};

/**
 * @param {string} sFileUID
 */
CHelpdeskViewModel.prototype.onFileRemove = function (sFileUID)
{
	var oAttach = this.getUploadedFileByUID(sFileUID);

	if (this.oJua)
	{
		this.oJua.cancel(sFileUID);
	}

	this.uploadedFiles.remove(oAttach);
};

/**
 * @param {string} sFileUID
 */
CHelpdeskViewModel.prototype.getUploadedFileByUID = function (sFileUID)
{
	return _.find(this.uploadedFiles(), function (oAttach) {
		return oAttach.uploadUid() === sFileUID;
	});
};

/**
 * @param {string} sFileUID
 * @param {Object} oFileData
 */
CHelpdeskViewModel.prototype.onFileUploadSelect = function (sFileUID, oFileData)
{
	var
		oAttach,
		sWarningCountLimit = Utils.i18n('HELPDESK/ERROR_UPLOAD_FILES_COUNT'),
		sButtonCountLimit = Utils.i18n('MAIN/BUTTON_CLOSE'),
		iAttachCount = this.uploadedFiles().length
	;

	if (iAttachCount >= 5)
	{
		App.Screens.showPopup(AlertPopup, [sWarningCountLimit, null, '', sButtonCountLimit]);
		return false;
	}

	if (App.Api.showErrorIfAttachmentSizeLimit(oFileData.FileName, oFileData.Size))
	{
		return false;
	}

	oAttach = new CHelpdeskAttachmentModel();

	oAttach.onUploadSelect(sFileUID, oFileData);

	this.uploadedFiles.push(oAttach);

	return true;
};

/**
 * @param {string} sFileUID
 * @param {number} iUploadedSize
 * @param {number} iTotalSize
 */
CHelpdeskViewModel.prototype.onFileUploadProgress = function (sFileUID, iUploadedSize, iTotalSize)
{
	var oAttach = this.getUploadedFileByUID(sFileUID);

	if (oAttach)
	{
		oAttach.onUploadProgress(iUploadedSize, iTotalSize);
	}
};

/**
 * @param {string} sFileUID
 */
CHelpdeskViewModel.prototype.onFileUploadStart = function (sFileUID)
{
	var oAttach = this.getUploadedFileByUID(sFileUID);

	if (oAttach)
	{
		oAttach.onUploadStart();
	}
};

/**
 * @param {string} sFileUID
 * @param {boolean} bResult
 * @param {Object} oResult
 */
CHelpdeskViewModel.prototype.onFileUploadComplete = function (sFileUID, bResult, oResult)
{
	var
		oAttach = this.getUploadedFileByUID(sFileUID),
		sThumbSessionUid = Date.now().toString()
		;

	if (oAttach)
	{
		oAttach.onUploadComplete(sFileUID, bResult, oResult);
		if (oAttach.type().substr(0, 5) === 'image')
		{
			oAttach.thumb(true);
			oAttach.getInThumbQueue(sThumbSessionUid);
		}
	}
};

CHelpdeskViewModel.prototype.setUnseenCount = function ()
{
	App.helpdeskUnseenCount(_.filter(this.threads(), function (oThreadList) {
		return oThreadList.unseen();
	}, this).length);
};

CHelpdeskViewModel.prototype.quoteText = function (sText)
{
	var sReplyText = this.replyText(),
		fDoingQuote = _.bind(function() {
			this.replyText(sReplyText === '' ? '>' + sText : sReplyText + '\n' + '>' + sText);
			this.replyTextFocus(true);
		},this);

	if(this.isQuickReplyHidden())
	{
		_.delay(function(){ fDoingQuote(); }, 300);
	}
	else
	{
		fDoingQuote();
	}
	this.isQuickReplyHidden(false);
};

CHelpdeskViewModel.prototype.setSignature = function (koText, domTextarea)
{
	if (koText && koText() === '' && this.isSignatureVisible())
	{
		koText("\r\n\r\n" + this.signature());
	}

	if (domTextarea) {
		setTimeout(function () {
			domTextarea = domTextarea[0];
			if (domTextarea.setSelectionRange)
			{
				domTextarea.focus();
				domTextarea.setSelectionRange(0, 0);
			}
			else if (domTextarea.createTextRange)
			{
				var range = domTextarea.createTextRange();

				range.moveStart('character', 0);
				range.select();
			}
		}.bind(this), 10);
	}
};

CHelpdeskViewModel.prototype.changeCcbccVisibility = function (koText, domTextarea)
{
	this.ccbccVisible(true);
	$(this.ccAddrDom()).inputosaurus('focus');
	//$(this.bccAddrDom()).inputosaurus('focus');
};
/**
 * @constructor
 */
function CHelpdeskSettingsViewModel()
{
	this.name = ko.observable(AppData.User.Name);
	this.language = ko.observable(AppData.User.DefaultLanguage);
	this.timeFormat = ko.observable(AppData.User.defaultTimeFormat());
	this.aDateFormats = Utils.getDateFormatsForSelector();
	this.dateFormat = ko.observable(AppData.User.DefaultDateFormat);
	this.hasPassword = ko.observable(AppData.User.HasPassword);
}

CHelpdeskSettingsViewModel.prototype.onShow = function ()
{
	this.name(AppData.User.Name);
	this.language(AppData.User.DefaultLanguage);
	this.timeFormat(AppData.User.defaultTimeFormat());
	this.dateFormat(AppData.User.DefaultDateFormat);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskSettingsViewModel.prototype.onResponse = function (oResponse, oRequest)
{
	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		if (oRequest.Language !== AppData.User.DefaultLanguage)
		{
			window.location.reload();
		}
		else
		{
			AppData.User.updateSettings(oRequest.Name, oRequest.Language,
				oRequest.TimeFormat, oRequest.DateFormat);

			App.Api.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
		}
	}
};

CHelpdeskSettingsViewModel.prototype.save = function ()
{
	var
		oParameters = {
			'Action': 'HelpdeskSettingsUpdate',
			'Name': this.name(),
			'Language': this.language(),
			'TimeFormat': this.timeFormat(),
			'DateFormat': this.dateFormat()
		}
	;
	
	App.Ajax.sendExt(oParameters, this.onResponse, this);
};

CHelpdeskSettingsViewModel.prototype.backToHelpdesk = function ()
{
	App.Routing.setHash([Enums.Screens.Helpdesk]);
};

CHelpdeskSettingsViewModel.prototype.onChangePasswordClick = function ()
{
	App.Screens.showPopup(ChangePasswordPopup, [true, true]);
};



/**
 * @constructor
 */
function CScreens()
{
	var $win = $(window);
	this.resizeAll = _.debounce(function () {
		$win.resize();
	}, 100);
	
	this.oScreens = {};

	this.currentScreen = ko.observable('');

	this.informationScreen = ko.observable(null);
	
	this.popups = [];
}

CScreens.prototype.initScreens = function () {};
CScreens.prototype.initLayout = function () {};

CScreens.prototype.init = function ()
{
	this.initScreens();
	
	this.initLayout();
	
	$('#pSevenContent').addClass('single_mode');
	
	_.defer(function () {
		if (!AppData.SingleMode)
		{
			$('#pSevenContent').removeClass('single_mode');
		}
	});
	
	this.informationScreen(this.showNormalScreen(Enums.Screens.Information));
};

CScreens.prototype.hasOpenedMinimizedPopups = function ()
{
	var bOpenedMinimizedPopups = false;
	
	_.each(this.popups, function (oPopup) {
		var vm = oPopup.__vm;
		if (vm.minimized && vm.minimized())
		{
			bOpenedMinimizedPopups = true;
		}
	});
	
	return bOpenedMinimizedPopups;
};

CScreens.prototype.hasOpenedMaximizedPopups = function ()
{
	var bOpenedMaximizedPopups = false;
	
	_.each(this.popups, function (oPopup) {
		var vm = oPopup.__vm;
		if (!vm.minimized || !vm.minimized())
		{
			bOpenedMaximizedPopups = true;
		}
	});
	
	return bOpenedMaximizedPopups;
};

CScreens.prototype.getCurrentScreenModel = function ()
{
	var
		oCurrentScreen = this.oScreens[this.currentScreen()],
		oCurrentModel = (typeof oCurrentScreen !== 'undefined') ? oCurrentScreen.Model : null
	;
	
	return oCurrentModel;
};

/**
 * @param {string} sScreen
 * @param {?=} mParams
 */
CScreens.prototype.showCurrentScreen = function (sScreen, mParams)
{
	var
		oCurrentScreen = this.oScreens[this.currentScreen()],
		oCurrentModel = (typeof oCurrentScreen !== 'undefined') ? oCurrentScreen.Model : null
	;
	
	if (this.currentScreen() !== sScreen)
	{
		if (oCurrentModel && oCurrentScreen.bInitialized)
		{
			oCurrentModel.hideViewModel();
		}
		this.currentScreen(sScreen);
	}
	
	this.showNormalScreen(sScreen, mParams);
	this.resizeAll();
};

/**
 * @param {string} sScreen
 * @param {?=} mParams
 * 
 * @return Object
 */
CScreens.prototype.showNormalScreen = function (sScreen, mParams)
{
	var
		sScreenId = sScreen,
		oScreen = this.oScreens[sScreenId]
	;

	if (oScreen)
	{
		oScreen.bInitialized = (typeof oScreen.bInitialized !== 'boolean') ? false : oScreen.bInitialized;
		if (!oScreen.bInitialized)
		{
			oScreen.Model = this.initViewModel(oScreen.Model, oScreen.TemplateName);
			oScreen.bInitialized = true;
		}

		oScreen.Model.showViewModel(mParams);
	}
	
	return oScreen ? oScreen.Model : null;
};

/**
 * @param {?} CViewModel
 * @param {string} sTemplateId
 * 
 * @return {Object}
 */
CScreens.prototype.initViewModel = function (CViewModel, sTemplateId)
{
	var
		oViewModel = null,
		$viewModel = null
	;

	oViewModel = new CViewModel();

	$viewModel = $('div[data-view-model="' + sTemplateId + '"]')
		.attr('data-bind', 'template: {name: \'' + sTemplateId + '\'}')
		.hide();

	oViewModel.$viewModel = $viewModel;
	oViewModel.bShown = false;
	oViewModel.showViewModel = function (mParams)
	{
		if (bMobileApp && (bIsWindowsPhone || App.browser.ie))
		{
			_.defer(_.bind(function () {
				this.$viewModel.show();
			}, this));
		}
		else
		{
			this.$viewModel.show();
		}
		if (typeof this.onRoute === 'function')
		{
			this.onRoute(mParams);
		}
		if (!this.bShown)
		{
			if (typeof this.onShow === 'function')
			{
				this.onShow(mParams);
			}
			if (AfterLogicApi.runPluginHook)
			{
				if (this.__name)
				{
					AfterLogicApi.runPluginHook('view-model-on-show', [this.__name, this]);
				}
			}
			
			this.bShown = true;
		}
	};
	oViewModel.hideViewModel = function ()
	{
		this.$viewModel.hide();
		if (typeof this.onHide === 'function')
		{
			this.onHide();
		}
		this.bShown = false;
	};
	ko.applyBindings(oViewModel, $viewModel[0]);

	if (typeof oViewModel.onApplyBindings === 'function')
	{
		oViewModel.onApplyBindings($viewModel);
	}

	return oViewModel;
};

/**
 * @param {?} CPopupViewModel
 * @param {Array=} aParameters
 */
CScreens.prototype.showPopup = function (CPopupViewModel, aParameters)
{
	if (CPopupViewModel)
	{
		if (!CPopupViewModel.__builded)
		{
			var
				oViewModelDom = null,
				oViewModel = new CPopupViewModel(),
				sTemplate = oViewModel.popupTemplate ? oViewModel.popupTemplate() : ''
			;

			if ('' !== sTemplate)
			{
				oViewModelDom = $('div[data-view-model="' + sTemplate + '"]')
					.attr('data-bind', 'template: {name: \'' + sTemplate + '\'}')
					.removeClass('visible').hide();

				if (oViewModelDom && 1 === oViewModelDom.length)
				{
					oViewModel.visibility = ko.observable(false);

					CPopupViewModel.__builded = true;
					CPopupViewModel.__vm = oViewModel;

					oViewModel.$viewModel = oViewModelDom;
					CPopupViewModel.__dom = oViewModelDom;

					oViewModel.showViewModel = Utils.createCommand(oViewModel, function () {
						if (App && App.Screens)
						{
							App.Screens.showPopup(CPopupViewModel);
						}
					});

					oViewModel.closeCommand = Utils.createCommand(oViewModel, function () {
						if (App && App.Screens)
						{
							App.Screens.hidePopup(CPopupViewModel);
						}
					});
					
					ko.applyBindings(oViewModel, oViewModelDom[0]);

					Utils.delegateRun(oViewModel, 'onApplyBindings', [oViewModelDom]);
				}
			}
		}

		if (CPopupViewModel.__vm && CPopupViewModel.__dom)
		{
			if (!CPopupViewModel.__vm.visibility())
			{
				CPopupViewModel.__dom.show();
				_.delay(function() {
					CPopupViewModel.__dom.addClass('visible');
				}, 50);
				CPopupViewModel.__vm.visibility(true);

				this.popups.push(CPopupViewModel);

				if (this.popups.length === 1)
				{
					this.keyupPopupBinded = _.bind(this.keyupPopup, this);
					$(document).on('keyup', this.keyupPopupBinded);
				}
			}
			
			Utils.delegateRun(CPopupViewModel.__vm, 'onShow', aParameters);
		}
	}
};

/**
 * @param {Object} oEvent
 */
CScreens.prototype.keyupPopup = function (oEvent)
{
	var oViewModel = (this.popups.length > 0) ? this.popups[this.popups.length - 1].__vm : null;
	
	if (oEvent && oViewModel && (!oViewModel.minimized || !oViewModel.minimized()))
	{
		var iKeyCode = window.parseInt(oEvent.keyCode, 10);
		if (Enums.Key.Esc === iKeyCode)
		{
			if (oViewModel.onEscHandler)
			{
				oViewModel.onEscHandler(oEvent);
			}
			else
			{
				oViewModel.closeCommand();
			}
		}

		if ((Enums.Key.Enter === iKeyCode || Enums.Key.Space === iKeyCode) && oViewModel.onEnterHandler)
		{
			oViewModel.onEnterHandler();
		}
	}
};

/**
 * @param {?} CPopupViewModel
 */
CScreens.prototype.hidePopup = function (CPopupViewModel)
{
	if (CPopupViewModel && CPopupViewModel.__vm && CPopupViewModel.__dom)
	{
		if (this.keyupPopupBinded && this.popups.length === 1)
		{
			$(document).off('keyup', this.keyupPopupBinded);
			this.keyupPopupBinded = undefined;
		}
		
		CPopupViewModel.__dom.removeClass('visible').hide();

		CPopupViewModel.__vm.visibility(false);

		Utils.delegateRun(CPopupViewModel.__vm, 'onHide');
		
		this.popups = _.without(this.popups, CPopupViewModel);
	}
};

/**
 * @param {string} sMessage
 */
CScreens.prototype.showLoading = function (sMessage)
{
	if (this.informationScreen())
	{
		this.informationScreen().showLoading(sMessage);
	}
};

CScreens.prototype.hideLoading = function ()
{
	if (this.informationScreen())
	{
		this.informationScreen().hideLoading();
	}
};

/**
 * @param {string} sMessage
 * @param {number=} iDelay
 */
CScreens.prototype.showReport = function (sMessage, iDelay)
{
	if (this.informationScreen())
	{
		this.informationScreen().showReport(sMessage, iDelay);
	}
};

/**
 * @param {string} sMessage
 * @param {boolean=} bHtml = false
 * @param {boolean=} bNotHide = false
 * @param {boolean=} bGray = false
 */
CScreens.prototype.showError = function (sMessage, bHtml, bNotHide, bGray)
{
	if (this.informationScreen())
	{
		this.informationScreen().showError(sMessage, bHtml, bNotHide, bGray);
	}
};

/**
 * @param {boolean=} bGray = false
 */
CScreens.prototype.hideError = function (bGray)
{
	if (this.informationScreen())
	{
		this.informationScreen().hideError(bGray);
	}
};

CScreens.prototype.initHelpdesk = function ()
{
	var oScreen = this.oScreens[Enums.Screens.Helpdesk];

	if (AppData.User.IsHelpdeskSupported && oScreen && !oScreen.bInitialized)
	{
		oScreen.Model = this.initViewModel(oScreen.Model, oScreen.TemplateName);
		oScreen.bInitialized = true;
	}
};
CScreens.prototype.initScreens = function ()
{
	this.oScreens[Enums.Screens.Information] = {
		'Model': CInformationViewModel,
		'TemplateName': 'Common_InformationViewModel'
	};
	this.oScreens[Enums.Screens.Login] = {
		'Model': CHelpdeskLoginViewModel,
		'TemplateName': 'Helpdesk_Login'
	};
	this.oScreens[Enums.Screens.Header] = {
		'Model': CHelpdeskHeaderViewModel,
		'TemplateName': 'Helpdesk_Header'
	};
	this.oScreens[Enums.Screens.Helpdesk] = {
		'Model': CHelpdeskViewModel,
		'TemplateName': 'Helpdesk_HelpdeskViewModel'
	};
	this.oScreens[Enums.Screens.Settings] = {
		'Model': CHelpdeskSettingsViewModel,
		'TemplateName': 'Helpdesk_SettingsExt'
	};
};

CScreens.prototype.initLayout = function ()
{
	$('#pSevenContent').append($('#HelpdeskLayout').html());
};


/**
 * @constructor
 */
function AbstractApp()
{
	this.browser = new CBrowser();
	
	try
	{
		this.favico = (!this.browser.ie8AndBelow && window.Favico) ? new window.Favico({
			'animation': 'none'
		}) : null;
	}
	catch (err)
	{
		this.favico = null;
	}

	this.Ajax = new CAjax();
	this.Screens = new CScreens();
	this.Api = new CApi();
	this.Storage = new CStorage();

	this.helpdeskUnseenCount = ko.observable(0);
	this.helpdeskPendingCount = ko.observable(0);
	this.mailUnseenCount = ko.observable(0);
	
	this.InternetConnectionError = false;
}

AbstractApp.prototype.init = function ()
{
	
};

AbstractApp.prototype.collectScreensData = function ()
{

};

AbstractApp.prototype.run = function ()
{

};

AbstractApp.prototype.momentDateTriggerCallback = function ()
{
	var oItem = ko.dataFor(this);
	if (oItem && oItem.updateMomentDate)
	{
		oItem.updateMomentDate();
	}
};

AbstractApp.prototype.fastMomentDateTrigger = function ()
{
	$('.moment-date-trigger-fast').each(this.momentDateTriggerCallback);
};

/**
 * @param {string=} sTitle
 */
AbstractApp.prototype.setTitle = function (sTitle)
{
	document.title = '.';
	document.title = sTitle || '';
};

AbstractApp.prototype.tokenProblem = function ()
{
	var
		sReloadFunc= 'window.location.reload(); return false;',
		sHtmlError = Utils.i18n('WARNING/TOKEN_PROBLEM_HTML', {'RELOAD_FUNC': sReloadFunc})
	;
	
	AppData.Auth = false;
	App.Api.showError(sHtmlError, true, true);
};


/**
 * @constructor
 */
function AppHelpDesk()
{
	AbstractApp.call(this);
	
	// for social in iframe
	if (window.opener && window.frameElement)
	{
		window.close();
		window.opener.location.reload();
	}

	this.Routing = new CRouting();
	this.Links = new CLinkBuilder();

	this.currentScreen = this.Screens.currentScreen;
	this.currentScreen.subscribe(this.setTitle, this);
	this.focused = ko.observable(true);
	this.focused.subscribe(this.setTitle, this);
	
	this.init();
	
	this.initHeaderInfo();
}

_.extend(AppHelpDesk.prototype, AbstractApp.prototype);

AppHelpDesk.prototype.init = function ()
{
	var
		oUserSettings = new CUserSettingsModel(),
		oAppSettings = new CAppSettingsModel()
	;
	
	oAppSettings.parse(AppData['App']);
	AppData.App = oAppSettings;

	oUserSettings.parse(AppData['User']);
	AppData.User = oUserSettings;
};

AppHelpDesk.prototype.logout = function ()
{
	App.Ajax.sendExt({'Action': 'HelpdeskLogout'}, this.onLogout, this);
};

AppHelpDesk.prototype.authProblem = function ()
{
	this.logout();
};

AppHelpDesk.prototype.onLogout = function ()
{
	window.location.reload();
};

AppHelpDesk.prototype.run = function ()
{
	this.Screens.init();
	
	if (AppData && AppData['Auth'])
	{
		this.Routing.init(Enums.Screens.Helpdesk);
	}
	else
	{
		this.Screens.showCurrentScreen(Enums.Screens.Login);
		if (AppData && AppData['LastErrorCode'] === Enums.Errors.AuthError)
		{
			this.Api.showError(Utils.i18n('WARNING/AUTH_PROBLEM'), false, true);
		}
		
		if (AppData && AppData['HelpdeskActivatedEmail'])
		{
			this.Api.showReport(Utils.i18n('HELPDESK/ACCOUNT_ACTIVATED'));
		}
	}
};

AppHelpDesk.prototype.initHeaderInfo = function ()
{
	if (this.browser.ie)
	{
		$(document)
			.bind('focusin', _.bind(this.onFocus, this))
			.bind('focusout', _.bind(this.onBlur, this))
		;
	}
	else
	{
		$(window)
			.bind('focus', _.bind(this.onFocus, this))
			.bind('blur', _.bind(this.onBlur, this))
		;
	}
};

AppHelpDesk.prototype.onFocus = function ()
{
	this.focused(true);
};

AppHelpDesk.prototype.onBlur = function ()
{
	this.focused(false);
};

/**
 * @param {string=} sTitle
 */
AppHelpDesk.prototype.setTitle = function (sTitle)
{
	document.title = '.';
	document.title = this.getTitleByScreen();
};

AppHelpDesk.prototype.getTitleByScreen = function ()
{
	var sTitle = '';
	
	switch (this.currentScreen())
	{
		case Enums.Screens.Login:
			sTitle = Utils.i18n('TITLE/HELPDESK', null, '');
			break;
		case Enums.Screens.Helpdesk:
			sTitle = Utils.i18n('TITLE/HELPDESK');
			break;
		case Enums.Screens.Settings:
			sTitle = Utils.i18n('TITLE/SETTINGS');
			break;
	}
	
	if (sTitle === '')
	{
		sTitle = AppData['HelpdeskSiteName'];
	}
	else if (AppData['HelpdeskSiteName'] !== '')
	{
		sTitle += ' - ' + AppData['HelpdeskSiteName'];
	}
	
	return sTitle;
};

AppHelpDesk.prototype.addScreenToHeader = function () {};

App = new AppHelpDesk();
window.App = App;

/**
 * AppData.IsMobile:
 *	-1 - first time, mobile is not determined
 *	0 - mobile is switched off
 *	1 - mobile is switched on
 */
if (AppData.AllowMobile && AppData.IsMobile === -1)
{
	/*jshint onevar: false*/
	var bMobile = !window.matchMedia('all and (min-width: 768px)').matches ? 1 : 0;
	/*jshint onevar: true*/

	window.App.Ajax.send({
		'Action': 'SystemSetMobile',
		'Mobile': bMobile
	}, function () {
		if (bMobile)
		{
			window.location.reload();
		}
		else
		{
			$(function () {
				_.defer(function () {
					App.run();
				});
			});
		}
	}, this);

}
else
{
	$(function () {
		_.defer(function () {
			App.run();
		});
	});
}

if (window.Modernizr && navigator)
{
	window.Modernizr.addTest('mobile', function() {
		return bMobileApp;
	});
}

window.AfterLogicApi = AfterLogicApi;

// export
window.Enums = Enums;

$html.removeClass('no-js').addClass('js');

if ($html.hasClass('pdf'))
{
	aViewMimeTypes.push('application/pdf');
	aViewMimeTypes.push('application/x-pdf');
}


}(jQuery, window, ko, crossroads, hasher));
