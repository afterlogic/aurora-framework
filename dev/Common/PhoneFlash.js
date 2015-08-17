/**
 * @constructor
 */
function CPhoneFlash()
{
	this.flash = null;
	this.jqFlash = null;

	this.phone = App.Phone;
	this.voiceApp = App.Phone.voiceApp;
	this.report = App.Phone.report;
	this.action = App.Phone.action;

	this.voiceImpi = AppData.User.VoiceImpi;
	this.voicePassword = AppData.User.VoicePassword;
//	this.voiceUrl = AppData.User.VoiceWebsocketProxyUrl;

	this.sessionid = ko.observable('');
	this.callState = ko.observable('');
	this.initStatus = ko.observable(false);
	this.connectStatus = ko.observable(false);

	this.init();
}

CPhoneFlash.prototype.init = function ()
{
	var self = this;

	$.getScript("static/js/swfobject.js", function(sData, sStatus, jqXHR)
	{
		if (sStatus === 'success')
		{
			self.voiceApp(true);

			swfobject.embedSWF(
				"static/freeswitch.swf", //swf url
				"flash", //id
				"214", //width
				"137", //height
				"9.0.0", //required Flash player version
				"expressInstall.swf", //express install swf url
				{rtmp_url: 'rtmp://217.199.220.26/phone'}, //flashvars
				{allowScriptAccess: 'always', bgcolor: '#ece9e0'}, //params
				[], //attributes
				false //callback fn
			);

			self.callbacks();
		}
	});
};

CPhoneFlash.prototype.log = function (sDesc)
{
};

CPhoneFlash.prototype.showPrivacy = function ()
{
//	this.action(Enums.PhoneAction.Settings);
	App.Screens.showPopup(PhonePopup, [{
		Action: Enums.PhoneAction.Settings,
		Callback: this.launchFlash.bind(this)
	}]);

	var fake_flash = $("#fake_flash"),
		oOffset = fake_flash.offset(),
		iWidth = fake_flash.width()
	;

	this.jqFlash.css("left", oOffset.left + (iWidth/2) - 107);// 107 - initial width of freeswitch.swf divided in half
	this.jqFlash.css("top", oOffset.top);
	this.jqFlash.css("visibility", "visible");
	this.flash.showPrivacy();
};

CPhoneFlash.prototype.checkMic = function ()
{
	return this.flash.isMuted();
//	return true;
};


CPhoneFlash.prototype.login = function (sName, sPassword)
{
	this.flash.login(sName, sPassword);
};

CPhoneFlash.prototype.newCall = function ()
{
//	$("#callout").data('account', account);
};

CPhoneFlash.prototype.call = function (sPhone)
{
//	$("#flash")[0].makeCall('sip:' + sPhone + '@217.199.220.26', '7003@217.199.220.26', []); // number@217.199.220.26, 7003@217.199.220.26 ,[]
	this.flash.makeCall('sip:7002@217.199.220.24', '7003@217.199.220.26', []);
};

CPhoneFlash.prototype.answer = function (uuid)
{
	this.flash.answer(uuid);
};

CPhoneFlash.prototype.hangup = function (uuid)
{
	this.flash.hangup(uuid);
};

CPhoneFlash.prototype.addCall = function (uuid, name, number, account)
{

};

CPhoneFlash.prototype.launchFlash = function (uuid, name, number, account)
{
	this.jqFlash.css("top", '-200px');
};

CPhoneFlash.prototype.callbacks = function ()
{
	var self = this;

	window.onInit = function ()
	{
		self.log('**************** onInit');

		self.initStatus(true);
	};

	window.onConnected = function (sessionid)
	{
		self.log('**************** onConnected ' + '(' + sessionid + ')');

		self.connectStatus(true);
		self.sessionid(sessionid);

		self.jqFlash = $("#flash");
		self.flash = self.jqFlash[0];

		if (self.checkMic()) {
			self.showPrivacy();
		}

		self.login('7003@217.199.220.26', '7003voippassword');
//		self.login('7003@217.199.220.24', '7003voippassword');
	};

	window.onDisconnected = function ()
	{
		self.log('**************** onDisconnected');

	};

	window.onEvent = function (data)
	{
		self.log('**************** onEvent ' + '(' + data + ')');

	};

	window.onLogin = function (status, user, domain)
	{
		self.log('**************** onLogin ' + '(' + status + ', ' + user + ', ' + domain + ')');
//		$("#flash")[0].register('7003@217.199.220.26', user);
//		$('#flash')[0].setMic(0);

//		self.showPrivacy();
//		self.call();
	};

	window.onLogout = function (user, domain)
	{
		self.log('**************** onLogout ' + '(' + user + ', ' + domain + ')');

	};

	window.onMakeCall = function (uuid, number, account)
	{
		self.log('**************** onMakeCall ' + '(' + uuid + ', ' + number + ', ' + account + ')');

	};

	window.onHangup = function (uuid, cause)
	{
		self.log('**************** onHangup ' + '(' + uuid + ', ' + cause + ')');

	};

	window.onIncomingCall = function (uuid, name, number, account, evt)
	{
		self.log('**************** onIncomingCall ' + '(' + uuid + ', ' + name + ', ' + number + ', ' + account + ', ' + evt + ')');

		self.addCall(uuid, name, number);
	};

	window.onDisplayUpdate = function (uuid, name, number)
	{
		self.log('**************** onDisplayUpdate ' + '(' + uuid + ', ' + name + ', ' + number + ')');

	};

	window.onCallState = function (uuid, state)
	{
		self.log('**************** onCallState ' + '(' + uuid + ', ' + state + ')');

		self.callState(state);
	};

	window.onDebug = function (message)
	{
		self.log('**************** onDebug ' + '(' + message + ')');

	};

	window.onAttach = function (uuid)
	{
		self.log('**************** onAttach ' + '(' + uuid + ')');

	};
};

