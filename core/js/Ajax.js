'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Types = require('core/js/utils/Types.js'),
	
	App = require('core/js/App.js'),
	Settings = require('core/js/Settings.js'),
	Screens = require('core/js/Screens.js')
;

/**
 * @constructor
 */
function CAjax()
{
	this.requests = ko.observableArray([]);
	
	this.aOnAllRequestsClosedHandlers = [];
	this.requests.subscribe(function () {
		if (this.requests().length === 0)
		{
			_.each(this.aOnAllRequestsClosedHandlers, function (fHandler) {
				if ($.isFunction(fHandler))
				{
					fHandler();
				}
			});
		}
	}, this);
	
	this.aAbortRequestHandlers = {};
	
	this.bAllowRequests = true;
	this.bInternetConnectionProblem = false;
}

/**
 * @param {string} sModule
 * @param {string} sMethod
 * @returns {object}
 */
CAjax.prototype.getOpenedRequest = function (sModule, sMethod)
{
	var oFoundReqData = _.find(this.requests(), function (oReqData) {
		return oReqData.Request.Module === sModule && oReqData.Request.Method === sMethod;
	});
	
	return oFoundReqData ? oFoundReqData.Request : null;
};

/**
 * @param {string=} sModule = ''
 * @param {string=} sMethod = ''
 * @returns {boolean}
 */
CAjax.prototype.hasOpenedRequests = function (sModule, sMethod)
{
	sModule = Types.pString(sModule);
	sMethod = Types.pString(sMethod);
	
	if (sMethod === '')
	{
		return this.requests().length > 0;
	}
	else
	{
		return !!_.find(this.requests(), function (oReqData) {
			if (oReqData)
			{
				var
					bComplete = oReqData.Xhr.readyState === 4,
					bAbort = oReqData.Xhr.readyState === 0 && oReqData.Xhr.statusText === 'abort',
					bSameMethod = oReqData.Request.Module === sModule && oReqData.Request.Method === sMethod
				;
				return !bComplete && !bAbort && bSameMethod;
			}
			return false;
		});
	}
};

/**
 * @param {string} sModule
 * @param {function} fHandler
 */
CAjax.prototype.registerAbortRequestHandler = function (sModule, fHandler)
{
	this.aAbortRequestHandlers[sModule] = fHandler;
};

/**
 * @param {function} fHandler
 */
CAjax.prototype.registerOnAllRequestsClosedHandler = function (fHandler)
{
	this.aOnAllRequestsClosedHandlers.push(fHandler);
};

/**
 * @param {string} sModule
 * @param {string} sMethod
 * @param {object} oParameters
 * @param {function=} fResponseHandler
 * @param {object=} oContext
 * @param {number=} iTimeout
 */
CAjax.prototype.send = function (sModule, sMethod, oParameters, fResponseHandler, oContext, iTimeout)
{
	if (this.bAllowRequests && !this.bInternetConnectionProblem)
	{
		var oRequest = {
			Module: sModule,
			Method: sMethod
		};
		
//		if (AfterLogicApi.runPluginHook)
//		{
//			AfterLogicApi.runPluginHook('ajax-default-request', [sModule, sMethod, oParameters]);
//		}

		if (oParameters)
		{
			oRequest.Parameters = JSON.stringify(oParameters);
		}
		
		if (oParameters && oParameters.AccountID)
		{
			oRequest.AccountID = oParameters.AccountID;
		}
		else if (App.isAuth() && App.defaultAccountId)
		{
			oRequest.AccountID = App.defaultAccountId();
		}
		else if (Settings.TenantHash)
		{
			oRequest.TenantHash = Settings.TenantHash;
		}
		
		if (Settings.CsrfToken)
		{
			oRequest.Token = Settings.CsrfToken;
		}
		
		this.abortRequests(oRequest);
	
		this.doSend(oRequest, fResponseHandler, oContext, iTimeout);
	}
};

/*************************private*************************************/

/**
 * @param {Object} oRequest
 * @param {Function=} fResponseHandler
 * @param {Object=} oContext
 * @param {number=} iTimeout
 */
CAjax.prototype.doSend = function (oRequest, fResponseHandler, oContext, iTimeout)
{
	var
		doneFunc = _.bind(this.done, this, oRequest, fResponseHandler, oContext),
		failFunc = _.bind(this.fail, this, oRequest, fResponseHandler, oContext),
		alwaysFunc = _.bind(this.always, this, oRequest),
		oXhr = null
	;
	
	oXhr = $.ajax({
		url: '?/Ajax/',
		type: 'POST',
		async: true,
		dataType: 'json',
		data: oRequest,
		success: doneFunc,
		error: failFunc,
		complete: alwaysFunc,
		timeout: iTimeout === undefined ? 50000 : iTimeout
	});
	
	this.requests().push({ Request: oRequest, Xhr: oXhr });
};

/**
 * @param {Object} oRequest
 */
CAjax.prototype.abortRequests = function (oRequest)
{
	var fHandler = this.aAbortRequestHandlers[oRequest.Module];
	
	if ($.isFunction(fHandler) && this.requests().length > 0)
	{
		_.each(this.requests(), _.bind(function (oReqData, iIndex) {
			var oOpenedRequest = oReqData.Request;
			if (oRequest.Module === oOpenedRequest.Module)
			{
				if (fHandler(oRequest, oOpenedRequest))
				{
					oReqData.Xhr.abort();
					this.requests()[iIndex] = undefined;
				}
			}
		}, this));
		
		this.requests(_.compact(this.requests()));
	}
};

CAjax.prototype.abortAllRequests = function ()
{
	_.each(this.requests(), function (oReqData) {
		if (oReqData)
		{
			oReqData.Xhr.abort();
		}
	}, this);
	
	this.requests([]);
};

/**
 * @param {Object} oRequest
 * @param {Function} fResponseHandler
 * @param {Object} oContext
 * @param {{Result:boolean}} oResponse
 * @param {string} sType
 * @param {Object} oXhr
 */
CAjax.prototype.done = function (oRequest, fResponseHandler, oContext, oResponse, sType, oXhr)
{
	var bDefaultAccount = App.isAuth() && !App.isPublic() && (oRequest.AccountID === App.defaultAccountId());
	
	if (oResponse && !oResponse.Result)
	{
		switch (oResponse.ErrorCode)
		{
			case Enums.Errors.InvalidToken:
				this.bAllowRequests = false;
				App.tokenProblem();
				break;
			case Enums.Errors.AuthError:
				if (bDefaultAccount)
				{
					this.bAllowRequests = false;
					this.abortAllRequests();
					App.authProblem();
				}
				break;
		}
	}

	this.executeResponseHandler(fResponseHandler, oContext, oResponse, oRequest);
};

/**
 * @param {Object} oRequest
 * @param {Function} fResponseHandler
 * @param {Object} oContext
 * @param {Object} oXhr
 * @param {string} sType
 * @param {string} sErrorText
 */
CAjax.prototype.fail = function (oRequest, fResponseHandler, oContext, oXhr, sType, sErrorText)
{
	var oResponse = { Result: false, ErrorCode: 0 };
	
	switch (sType)
	{
		case 'abort':
			oResponse = { Result: false, ErrorCode: Enums.Errors.NotDisplayedError };
			break;
		default:
		case 'error':
		case 'parseerror':
			if (sErrorText === '')
			{
				oResponse = { Result: false, ErrorCode: Enums.Errors.NotDisplayedError };
			}
			else
			{
				oResponse = { Result: false, ErrorCode: Enums.Errors.DataTransferFailed };
			}
			break;
	}
	
	this.executeResponseHandler(fResponseHandler, oContext, oResponse, oRequest);
};

/**
 * @param {Function} fResponseHandler
 * @param {Object} oContext
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAjax.prototype.executeResponseHandler = function (fResponseHandler, oContext, oResponse, oRequest)
{
	if (!oResponse)
	{
		oResponse = { Result: false, ErrorCode: 0 };
	}
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('ajax-default-response', [oRequest.Module, oRequest.Method, oData]);
//	}
	
	if ($.isFunction(fResponseHandler) && !oResponse.StopExecuteResponse)
	{
		fResponseHandler.apply(oContext, [oResponse, oRequest]);
	}
};

/**
 * @param {object} oXhr
 * @param {string} sType
 * @param {object} oRequest
 */
CAjax.prototype.always = function (oRequest, oXhr, sType)
{
	if (sType !== 'abort')
	{
		_.each(this.requests(), function (oReqData, iIndex) {
			if (oReqData && _.isEqual(oReqData.Request, oRequest))
			{
				this.requests()[iIndex] = undefined;
			}
		}, this);

		this.requests(_.compact(this.requests()));

		this.checkConnection(oRequest.Module, oRequest.Method, sType);
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

	return function (sModule, sMethod, sStatus)
	{
		clearTimeout(iTimer);
		if (sStatus !== 'error')
		{
			Ajax.bInternetConnectionProblem = false;
			Screens.hideError(true);
		}
		else
		{
			if (sModule === 'Ping' && sMethod === 'Ping')
			{
				Ajax.bInternetConnectionProblem = true;
				Screens.showError(TextUtils.i18n('WARNING/NO_INTERNET_CONNECTION'), false, true, true);
				iTimer = setTimeout(function () {
					Ajax.doSend({ Module: 'Ping', Method: 'Ping' });
				}, 60000);
			}
			else
			{
				Ajax.doSend({ Module: 'Ping', Method: 'Ping' });
			}
		}
	};
}());

var Ajax = new CAjax();

module.exports = {
	getOpenedRequest: _.bind(Ajax.getOpenedRequest, Ajax),
	hasInternetConnectionProblem: function () { return Ajax.bInternetConnectionProblem; },
	hasOpenedRequests: _.bind(Ajax.hasOpenedRequests, Ajax),
	registerAbortRequestHandler: _.bind(Ajax.registerAbortRequestHandler, Ajax),
	registerOnAllRequestsClosedHandler: _.bind(Ajax.registerOnAllRequestsClosedHandler, Ajax),
	send: _.bind(Ajax.send, Ajax)
};
