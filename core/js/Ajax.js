'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	App = require('core/js/App.js'),
	Settings = require('core/js/Settings.js'),
	Screens = require('core/js/Screens.js')
;

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
	
	this.aActionsWithoutAuthForSend = ['SystemLogin', 'SystemUpdateLanguageOnLogin', 'Logout', 'AccountCreate', 
		'SystemSetMobile', 'AccountRegister', 'AccountGetForgotQuestion', 'AccountValidateForgotQuestion', 'AccountChangeForgotPassword'];
	
	this.aActionsWithoutAuthForSendExt = ['SocialRegister', 'HelpdeskRegister', 'HelpdeskForgot', 
			'HelpdeskLogin', 'HelpdeskForgotChangePassword', 'Logout', 'GetCalendars',
			'GetEvents', 'GetPublicFiles'];
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
	return sAction === 'SocialRegister' || sAction === 'HelpdeskRegister' || sAction === 'HelpdeskForgot' || sAction === 'HelpdeskLogin' || sAction === 'Logout';
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
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('ajax-default-request', [oParameters.Action, oParameters]);
//	}
	
	if (Settings.CsrfToken)
	{
		oParameters.Token = Settings.CsrfToken;
	}

	this.abortRequests(oParameters);
	
//	Utils.log('Ajax request send', oParameters.Action, oParameters);
	
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
		bAccountExists = bCurrentAccountId || App.isAuth() && App.hasAccountWithId(oParameters.AccountID)
	;
	
	if (oParameters && (App.isAuth() && bAccountExists || this.isAllowedActionWithoutAuth(oParameters.Action)))
	{
		if (bCurrentAccountId && App.isAuth())
		{
			oParameters.AccountID = App.currentAccountId();
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
	
	if (oParameters && (App.isAuth() || bAllowWithoutAuth))
	{
		if (Settings.TenantHash)
		{
			oParameters.TenantHash = Settings.TenantHash;
		}
		
		this.doSend(oParameters, fResponseHandler, oContext, this.doneExt);
	}
};

/**
 * @param {Object} oParameters
 */
CAjax.prototype.abortRequests = function (oParameters)
{
	switch (oParameters.Action || oParameters.Method)
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
		case 'UpdateEvent':
			this.abortRequestByActionName('UpdateEvent', {'calendarId': oParameters.calendarId, 'uid': oParameters.uid});
			break;
		case 'GetCalendars':
			this.abortRequestByActionName('GetCalendars');
			break;
		case 'GetEvents':
			this.abortRequestByActionName('GetEvents');
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
				case 'UpdateEvent':
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
		bAccountExists = App.isAuth() && App.hasAccountWithId(oParameters.AccountID),
		bDefaultAccount = App.isAuth() && (oParameters.AccountID === App.defaultAccountId())
	;
	
//	Utils.log('Ajax request done', oParameters.Action, sType, Utils.getAjaxDataForLog(oParameters.Action, oData), oParameters);
	
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
	
//	Utils.log('Ajax request fail', oParameters.Action, sType, oParameters);
	
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
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('ajax-default-response', [oParameters.Action, oData]);
//	}
	
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

		this.checkConnection(oParameters.Action, sType);
		
		var oPrefetcher = require('core/js/Prefetcher.js');
		if (oPrefetcher && sType !== 'parsererror' && !this.hasOpenedRequests())
		{
			oPrefetcher.start();
		}
	}
};

CAjax.prototype.checkConnection = (function () {

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
			Screens.hideError(true);
		}
	}, 5000);

	return function (sAction, sStatus)
	{
		clearTimeout(iTimer);
		if (sStatus !== 'error')
		{
			Ajax.InternetConnectionError = false;
			Screens.hideError(true);
		}
		else
		{
			if (sAction === 'SystemPing')
			{
				Ajax.InternetConnectionError = true;
				Screens.showError(Utils.i18n('WARNING/NO_INTERNET_CONNECTION'), false, true, true);
				iTimer = setTimeout(function () {
					Ajax.send({'Action': 'SystemPing'});
				}, 60000);
			}
			else
			{
				Ajax.send({'Action': 'SystemPing'});
			}
		}
	};
}());

var Ajax = new CAjax();

module.exports = Ajax;
