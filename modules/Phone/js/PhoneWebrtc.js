'use strict';

var
	ko = require('knockout'),	
	_ = require('underscore'),
	$ = require('jquery'),
	
	Utils = require('modules/Core/js/utils/Common.js'),
	
	Screens = require('modules/Core/js/Screens.js'),
	
	Phone = require('modules/Phone/js/Phone.js'),
	Settings = require('modules/Phone/js/Settings.js')
;

/**
 * @constructor
 */
function CPhoneWebrtc()
{
	this.stack = ko.observable(null);
	this.registerSession = ko.observable(null);
	this.callSession = ko.observable(null);

	this.stackConf = ko.observable(null);
	this.registerConf = ko.observable(null);
	this.hangupConf = ko.observable(null);

	//this.hasFatalError = ko.observable(false);
	this.isStarted = ko.observable(false);

	this.eventSessionBinded = this.eventSession.bind(this);
	//this.eventStackBinded = this.eventStack.bind(this);
	this.createStackErrorBinded = this.createStackError.bind(this);
	this.createStackBinded = this.createStack.bind(this);

	//this.videoLocal = document.getElementById('video_local');
	//this.videoRemote = document.getElementById('video_remote');
	this.audioRemote = document.getElementById('audio_remote');

	this.interval = 0;
}

CPhoneWebrtc.prototype.init = function ()
{
	var self = this;

	$.getScript('static/js/sipml.js', function(sData, sStatus, jqXHR) {
		Phone.onGetScript(sStatus);
		if (sStatus === 'success')
		{
			//self.voiceApp(true);
			/*tsk_utils_log_info = function (sMsg) {
				Phone.log('*************** ***************' + sMsg);
			};*/

			self.setConfigs();
			// Supported values: info, warn, error, fatal.
			SIPml.setDebugLevel('fatal');
			SIPml.init(self.createStackBinded, self.createStackErrorBinded);
		}
	});
};

CPhoneWebrtc.prototype.setConfigs = function ()
{
	this.stackConf({
		realm: Settings.SipRealm,
		//realm: '192.168.0.59',
		impi: Settings.SipImpi,
		//impi: '102',
		impu: 'sip:' + Settings.SipImpi + '@' + Settings.SipRealm,
		//impu: 'sip:102@asterisk.afterlogic.com',
		password: Settings.SipPassword,
		//password: 'user02',
		enable_rtcweb_breaker: true,
		//enable_click2call: true,
		websocket_proxy_url: Settings.SipWebsocketProxyUrl,
		//websocket_proxy_url: 'ws://192.168.0.59:8088/ws',
		//outbound_proxy_url: Settings.SipOutboundProxyUrl,
		//ice_servers: [{ url: 'stun:stun.l.google.com:19302'}, { url:'turn:user@numb.viagenie.ca', credential:'myPassword'}],
		ice_servers: '[{ url: "stun:stun.afterlogic.com:3478"}]',
		events_listener: {
			events: '*',
			listener: this.eventSessionBinded
		}
	});

	this.registerConf({
		audio_remote: this.audioRemote,
		expires: 3600,
		events_listener: {
			events: '*',
			listener: this.eventSessionBinded
		},
		sip_caps: [
			{ name: '+g.oma.sip-im', value: null },
			{ name: '+audio', value: null },
			{ name: '+sip.ice' },
			{ name: 'language', value: '\"en,fr\"' }
		]
	});

	this.hangupConf({
		events_listener: {
			events: '*',
			listener: this.eventSessionBinded
		}
	});
};

CPhoneWebrtc.prototype.createStack = function ()
{
	this.stack(new SIPml.Stack(this.stackConf()));

	this.stack().start();

	/*this.stack().addEventListener('*', function(e){
		console.log("************************ *********************'"+e.type+"' event fired");
	});*/
};

CPhoneWebrtc.prototype.createStackError = function ()
{
	Phone.log('*************** Failed to initialize the engine');
};

CPhoneWebrtc.prototype.register = function ()
{
	this.registerSession(this.stack().newSession('register', this.registerConf()));
	this.registerSession().register();
};

/**
 * @param {{newSession,type}} ev
 */
CPhoneWebrtc.prototype.eventSession = function (ev)
{
	Phone.log('*************** ' + ev.type + ' (' + ev.description + ')');

	var sEvType = ev.type;

	//http://sipml5.org/docgen/symbols/SIPml.EventTarget.html

	switch (sEvType)
	{
		case 'starting':
			break;
		case 'started':
			Screens.hideError();
			this.isStarted(true);

			this.register();
			break;
		case 'stopping':
		case 'stopped':
			this.isStarted(false);
			this.createStack();
			break;
		case 'failed_to_stop':
			break;
		case 'failed_to_start':
			Phone.action(Enums.PhoneAction.OfflineError);
			this.isStarted(false);
			this.reconnect(30);
			break;
		case 'connecting':
			break;
		case 'connected':
			if(ev.description === 'Connected')
			{
				Phone.action(Enums.PhoneAction.Online);
			}
			else if(ev.description === 'In call')
			{
				Utils.desktopNotify('hide');
			}
			break;
		case 'terminating':
		case 'terminated':
			Phone.action(Enums.PhoneAction.Online);
			if(ev.description === 'Disconnected')
			{
				this.createStack();
			}
			break;
		case 'i_ao_request':
			/*if(ev.description === 'Ringing') {
			 var self = this;
			 $('body').on('click', function() {
			 //self.callSession().dtmf('#7002');
			 //self.callSession().dtmf('7002');
			 //self.callSession().dtmf('#');
			 //this.callSession().dtmf('*');
			 self.callSession().dtmf('7');
			 self.callSession().dtmf('0');
			 self.callSession().dtmf('0');
			 self.callSession().dtmf('2');
			 })
			 }*/
			break;
		case 'media_added':
			break;
		case 'media_removed':
			break;
		case 'm_stream_video_remote_added':
			break;
		case 'm_stream_audio_local_added':
			break;
		case 'm_stream_audio_remote_added':
			break;
		case 'i_request':
			break;
		case 'o_request':
			break;
		case 'sent_request':
			break;
		case 'cancelled_request':
			break;
		case 'i_new_call':

			this.callSession(ev.newSession);
			this.callSession().setConfiguration(this.registerConf());
			Phone.incomingCall(this.callSession()['getRemoteFriendlyName']());

			break;
		case 'i_new_message':
			break;
		case 'm_permission_requested':
			break;
		case 'm_permission_accepted':
			/*if(Phone.action() === 'incoming')
			{
				this.report(TextUtils.i18n('PHONE/INFO_INCOMING_CALL_FROM') + ' ' + this.callSession()['getRemoteFriendlyName']());
			}
			else
			{
				this.report(TextUtils.i18n('PHONE/INFO_CALL_IN_PROGRESS'));
			}*/
			break;
		case 'm_permission_refused':
			break;
		case 'transport_error':
			break;
		case 'global_error':
			break;
		case 'message_error':
			break;
		case 'webrtc_error':
			break;
	}
};

/**
 * @param {string} sPhone
 */
CPhoneWebrtc.prototype.call = function (sPhone)
{
	if (!this.isStarted())
	{
		//this.hasFatalError(false);
		this.createStack();
	}
	else
	{
		Phone.action('outgoing');
		this.callSession(this.stack().newSession('call-audio', this.registerConf()));
		this.callSession().call(sPhone);

		Phone.log('*************** ' + this.callSession()['getRemoteFriendlyName']());
	}
};

CPhoneWebrtc.prototype.answer = function ()
{
	if(this.callSession())
	{
		this.callSession().accept(this.registerConf());
	}
};

CPhoneWebrtc.prototype.hangup = function ()
{
	if (this.callSession())
	{
		this.callSession().hangup(this.hangupConf());
		this.callSession().hangup({events_listener: {events: '*', listener: this.eventSessionBinded}});
	}

	/*if (this.stack() && this.stack().o_stack.e_state)
	 {	//unregister
	 oRegisterSession = this.stack()['newSession']('register', {
	 expires: 0
	 });
	 oRegisterSession.register();
	 }
	 this.stack().stop();*/
};

CPhoneWebrtc.prototype.reconnect = (function ()
{
	var iIntervalId = 0;

	return function (iInterval) {
		clearInterval(iIntervalId);

		if (iInterval)
		{
			iIntervalId = setInterval(_.bind(function () {
				
			}, this), iInterval);
		}
	};
}());

/**
 * @param {function} fResponseHandler
 * @param {object} oContext
 */
CPhoneWebrtc.prototype.getLogs = function (fResponseHandler, oContext)
{
	fResponseHandler.call(oContext);
};

module.exports = new CPhoneWebrtc();
