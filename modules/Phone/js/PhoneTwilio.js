'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	
	Settings = require('modules/Phone/js/Settings.js'),
	Ajax = require('modules/Phone/js/Ajax.js'),
	Phone = require('modules/Phone/js/Phone.js')
;

/**
 * @constructor
 */
function CPhoneTwilio()
{
	this.device = null;
	this.connection = null;
	this.token = null;
	this.webSocket = null;
}

CPhoneTwilio.prototype.init = function ()
{
	Ajax.send('GetToken', null, this.onGetTokenResponse, this);
};

/**
 * @param {object} oResponse
 * @param {object} oRequest
 */
CPhoneTwilio.prototype.onGetTokenResponse = function (oResponse, oRequest)
{
	var
		self = this,
		oResult = oResponse.Result
	;

	if (oResult)
	{
		Phone.log('*************** twilioToken_requestTrue');

//		$.ajaxSettings.cache = true;//todo
		$.getScript(
			"//static.twilio.com/libs/twiliojs/1.2/twilio.min.js",
			function(sData, sStatus, jqXHR)
			{
				Phone.onGetScript(sStatus);
				if (sStatus === 'success')
				{
					self.token = oResult;
					self.setupDevice(oResult);
				}
			}
		);
	}
	else
	{
		Phone.onGetScript();
	}
};

/**
 * @param {string} sToken
 */
CPhoneTwilio.prototype.setupDevice = function (sToken)
{
	var self = this;

	Phone.log('*************** setup');

	Phone.phoneSupport(false, '10,1,000');
	this.device = Twilio.Device;
	this.device.setup(sToken, {
		//rtc: false,
		//debug: true
	});

	/*************** events ***************/
	this.device.ready(function (oDevice) {
		Phone.log('*************** ready ', oDevice);

		self.webSocket = window.socket;
		self.action(Enums.PhoneAction.Online);
	});

	this.device.offline(function (oDevice) {
		Phone.log('*************** offline ', oDevice);

		self.action(Enums.PhoneAction.Offline);
	});
	
	this.device.error(function (oError) {
		Phone.log('*************** error ', oError);

		switch (oError.message)
		{
			case 'This AccessToken is no longer valid':
			case 'Cannot register. Token not validated':
				self.token = null;
				self.device.destroy();
				break;
		}

		self.action(Enums.PhoneAction.OfflineError);
	});
	
	this.device.connect(function (oConnection) { //This is triggered when a connection is opened (incoming||outgoing)
		Phone.log('*************** connect ', oConnection);

		if (oConnection.message.Direction === "outbound")
		{
			self.action(Enums.PhoneAction.Outgoing);
		}
		else if (oConnection.message.Direction === "inbound")
		{
			self.action(Enums.PhoneAction.Incoming);
		}
	});

	this.device.disconnect(function (oConnection) {
		Phone.log('*************** disconnect ', oConnection);

		self.action(Enums.PhoneAction.Online);
	});

	this.device.incoming(function (oConnection) {
		Phone.log('*************** incoming ', oConnection);
		
		self.connection = oConnection;

		Phone.incomingCall(oConnection.parameters.From);
	});
	
	// This is triggered when an incoming connection is canceled by the caller before it is accepted by the device.
	this.device.cancel( function (oConnection) {
		Phone.log('*************** cancel ', oConnection);

		self.action(Enums.PhoneAction.Online);
	});
	
	// Register a handler function to be called when availability state changes for any client currently associated with your Twilio account.
	this.device.presence( function ( presenceEvent) {
		Phone.log('*************** presence ', presenceEvent);

	});
};

/**
 * @param {string} sPhoneNumber
 */
CPhoneTwilio.prototype.call = function (sPhoneNumber)
{
	this.connection = this.device.connect({
		"PhoneNumber": sPhoneNumber,
		"AfterlogicCall": 1
	});
};

CPhoneTwilio.prototype.answer = function ()
{
	this.connection.accept();
};

CPhoneTwilio.prototype.hangup = function ()
{
	if (this.connection)
	{
		if (this.connection.status && this.connection.status() === 'pending')
		{
			// for incoming call
			this.connection.reject();
		}
		else
		{
			this.connection.disconnect();
		}

		// in the first few seconds of the call connection not close
		if (this.connection.status() !== 'closed')
		{
			_.delay(_.bind(function () {
				this.hangup();
			}, this), 1000);
		}
	}
};

CPhoneTwilio.prototype.reconnect = (function ()
{
	var iIntervalId = 0;

	return function (iInterval)
	{
		clearInterval(iIntervalId);

		if (iInterval)
		{
			iIntervalId = setInterval(_.bind(function () {
				if (this.device && this.token)
				{
					this.device.setup(this.token);
				}
				else
				{
					this.init();
				}
			}, this), iInterval);
		}
	};
}());

/**
 * @param {function} fResponseHandler
 * @param {object} oContext
 */
CPhoneTwilio.prototype.getLogs = function (fResponseHandler, oContext)
{
	var oParameters = {
		//'Status': 'no-answer',
		'StartTime': moment().subtract(3, 'months').format("YYYY-MM-DD") //subtract 3 months from now
	};

	Ajax.send('GetLogs', oParameters, fResponseHandler, oContext);
};

module.exports = new CPhoneTwilio();