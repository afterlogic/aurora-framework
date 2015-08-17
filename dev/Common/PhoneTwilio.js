/**
 * @constructor
 */
function CPhoneTwilio()
{
	this.phone = App.Phone;
	this.action = App.Phone.action;

	this.device = null;
	this.connection = null;
	this.token = null;
	this.webSocket = null;
}

CPhoneTwilio.prototype.init = function ()
{
	App.Ajax.send( {'Action': 'TwilioGetToken'}, this.onTokenResponse, this);
};

CPhoneTwilio.prototype.onTokenResponse = function (oResult, oRequest)
{
	var	self = this;

	if (oResult && oResult.Result)
	{
		this.phone.log('*************** twilioToken_requestTrue');

		$.ajaxSettings.cache = true;
		$.getScript(
			"//static.twilio.com/libs/twiliojs/1.2/twilio.min.js",
			function(sData, sStatus, jqXHR)
			{
				self.phone.onGetScript(sStatus);
				if (sStatus === 'success')
				{
					self.token = oResult.Result;
					self.setupDevice(oResult.Result);
				}
			}
		);
	}
	else
	{
		self.phone.onGetScript();
	}
};

CPhoneTwilio.prototype.setupDevice = function (sToken)
{
	var self = this;

	this.phone.log('*************** setup');

	this.phone.phoneSupport(false, '10,1,000');
	this.device = Twilio.Device;
	this.device.setup(sToken, {
		//rtc: false,
		//debug: true
	});

	/*************** events ***************/
	this.device.ready(function (oDevice) {
		self.phone.log('*************** ready ', oDevice);

		self.webSocket = window.socket;
		self.action(Enums.PhoneAction.Online);
	});

	this.device.offline(function (oDevice) {
		self.phone.log('*************** offline ', oDevice);

		self.action(Enums.PhoneAction.Offline);
	});
	
	this.device.error(function (oError) {
		self.phone.log('*************** error ', oError);

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
		self.phone.log('*************** connect ', oConnection);

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
		self.phone.log('*************** disconnect ', oConnection);

		self.action(Enums.PhoneAction.Online);
	});

	this.device.incoming(function (oConnection) {
		self.phone.log('*************** incoming ', oConnection);
		
		self.connection = oConnection;

		self.phone.incomingCall(oConnection.parameters.From);
	});
	
	// This is triggered when an incoming connection is canceled by the caller before it is accepted by the device.
	this.device.cancel( function (oConnection) {
		self.phone.log('*************** cancel ', oConnection);

		self.action(Enums.PhoneAction.Online);
	});
	
	// Register a handler function to be called when availability state changes for any client currently associated with your Twilio account.
	this.device.presence( function ( presenceEvent) {
		self.phone.log('*************** presence ', presenceEvent);

	});
};

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
	if (this.connection) {
		if (this.connection.status && this.connection.status() === 'pending') {
			// for incoming call
			this.connection.reject();
		} else {
			this.connection.disconnect();
		}

		// in the first few seconds of the call connection not close
		if (this.connection.status() !== 'closed') {
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

		if (iInterval) {
			iIntervalId = setInterval(_.bind(function () {
				if (this.device && this.token) {
					this.device.setup(this.token);
				}
				else {
					this.init();
				}
			}, this), iInterval);
		}
	};
}());

CPhoneTwilio.prototype.getLogs = function (fResponseHandler, oContext)
{
	var oParameters = {
		'Action': 'TwilioGetLogs',
		//'Status': 'no-answer',
		'StartTime': moment().subtract(3, 'months').format("YYYY-MM-DD") //subtract 3 months from now
	};

	App.Ajax.send(oParameters, fResponseHandler, oContext);
};