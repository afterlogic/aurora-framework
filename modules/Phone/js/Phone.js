'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('modules/Core/js/utils/Text.js'),
	Types = require('modules/Core/js/utils/Types.js'),
	Utils = require('modules/Core/js/utils/Common.js'),
	
	Browser = require('modules/Core/js/Browser.js'),
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	Screens = require('modules/Core/js/Screens.js'),
	
	Popups = require('modules/Core/js/Popups.js'),
	PhonePopup = require('modules/Phone/js/popups/PhonePopup.js'),
	
	Settings = require('modules/Phone/js/Settings.js')
;

/**
 * @constructor
 */
function CPhone()
{
	this.provider = null;

	this.report = ko.observable('');
	this.missedCalls = ko.observable(false);
	this.phoneToCall = ko.observable('');
	this.action = ko.observable(Enums.PhoneAction.Offline);

	this.action.subscribe(function (sAction) {
		switch (sAction)
		{
			case Enums.PhoneAction.Offline:
				this.provider.reconnect(60000);
				break;
			case Enums.PhoneAction.OfflineError:
				this.provider.reconnect(60000);
				break;
			case Enums.PhoneAction.OfflineInit:
				this.provider.init();
				break;
			case Enums.PhoneAction.OfflineActive:
				break;
			case Enums.PhoneAction.Online:
				Popups.hidePopup(PhonePopup);
				Utils.desktopNotify('hide');
				this.provider.reconnect();
				this.provider.hangup();
				break;
			case Enums.PhoneAction.OnlineActive:
				break;
			case Enums.PhoneAction.Outgoing:
				this.provider.call(this.getFormattedPhone(this.phoneToCall()));
				break;
			case Enums.PhoneAction.OutgoingConnect:
				break;
			case Enums.PhoneAction.Incoming:
				break;
			case Enums.PhoneAction.IncomingConnect:
				Popups.hidePopup(PhonePopup);
				Utils.desktopNotify('hide');
				this.provider.answer();
				break;
		}
	}, this);
}

CPhone.prototype.init = function ()
{
	this.provider = Settings.VoiceProvider === 'sip' ?
				require('modules/Phone/js/PhoneWebrtc.js') :
				require('modules/Phone/js/PhoneTwilio.js');
	
	this.action(Enums.PhoneAction.OfflineInit);
};

CPhone.prototype.log = function ()
{
	if (false && window.console && window.console.log)
	{
		window.console.log.apply(window.console, arguments);
	}
};

/**
 * @param {string} sStatus
 */
CPhone.prototype.onGetScript = function (sStatus)
{
	if (sStatus && sStatus === 'success')
	{
		this.log('*************** gettingScript_success');
	}
	else
	{
		this.log('*************** gettingScript_unknownError');
		this.action(Enums.PhoneAction.OfflineError);
	}
};

/**
 * @param {number} iErrCode
 */
CPhone.prototype.showError = function (iErrCode)
{
	if (1 === Types.pInt(iErrCode))
	{
		Screens.showError(TextUtils.i18n('PHONE/ERROR_SERVER_UNAVAILABLE'), false, true);
	}
};

/**
 * @param {boolean} bIsWebrtc
 * @param {string} sFlashVersion
 */
CPhone.prototype.phoneSupport = function (bIsWebrtc, sFlashVersion)
{
	var fGetFlashVersion = function () { // version format '00,0,000'
		try
		{ //ie
			try
			{ //avoid fp6 minor version lookup issues see: http://blog.deconcept.com/2006/01/11/getvariable-setvariable-crash-internet-explorer-flash-6/
				var axo = new ActiveXObject('ShockwaveFlash.ShockwaveFlash.6');

				try
				{
					axo.AllowScriptAccess = 'always';
				}
				catch(eX)
				{
					return '6,0,0';
				}
			}
			catch(eX) {}
			return new ActiveXObject('ShockwaveFlash.ShockwaveFlash').GetVariable('$version').replace(/\D+/g, ',').match(/^,?(.+),?$/)[1];
		}
		catch(eX)
		{ //other browsers
			try
			{
				if (navigator.mimeTypes['application/x-shockwave-flash'].enabledPlugin)
				{
					return (navigator.plugins['Shockwave Flash 2.0'] || navigator.plugins['Shockwave Flash']).description.replace(/\D+/g, ',').match(/^,?(.+),?$/)[1];
				}
			}
			catch(eXt) {}
		}
		return '0,0,0';
	};

	if (bIsWebrtc && !Browser.chrome && !sFlashVersion && !Browser.iosDevice)
	{
		this.log('*************** Browser not supported');
	}
	else if (Browser.iosDevice)
	{
		this.log('*************** Device not supported');
	}
	else if (sFlashVersion && fGetFlashVersion() === '0,0,0')
	{
		this.log('*************** Please install flash player');
	}
	else if (sFlashVersion && fGetFlashVersion() < sFlashVersion)
	{
		this.log('*************** Please reinstall flash player');
	}
};

/**
 * @param {string} sNumber
 */
CPhone.prototype.incomingCall = function (sNumber)
{
	this.action(Enums.PhoneAction.Incoming);

	if (Types.isNonEmptyString(sNumber))
	{
		var fShowAll = _.bind(function (sText) {
			this.report(TextUtils.i18n('PHONE/INFO_INCOMING_CALL_FROM') + ' ' + sText);
			
			Popups.showPopup(PhonePopup, [sText]);
			
			Utils.desktopNotify({
				action: 'show',
				title: TextUtils.i18n('PHONE/INFO_USER_CALLING', {'USER' : sText}),
				body: TextUtils.i18n('PHONE/INFO_CLICK_TO_ANSWER'),
				callback: _.bind(function() {
					this.action(Enums.PhoneAction.IncomingConnect);
				}, this),
				timeout: 60000
			});
		}, this);
		
		if (ModulesManager.isModuleIncluded('Contacts'))
		{
			ModulesManager.run('Contacts', 'requestUserByPhone', function (sUser) {
				if (Types.isNonEmptyString(sUser))
				{
					fShowAll(sUser);
				}
				else
				{
					fShowAll(sNumber);
				}
			});
		}
		else
		{
			fShowAll(sNumber);
		}

		this.missedCalls(true);
	}
};

/**
 * @param {string} sPhone
 */
CPhone.prototype.getFormattedPhone = function (sPhone)
{
	sPhone = sPhone.toString();

	var
		oPrefixes = {
			'8': '7'
		},
		sCleanedPhone = (/#/g).test(sPhone) ? sPhone.split('#')[1] : sPhone.replace(/[()\s_\-+]/g, '')
	;

	_.each(oPrefixes, function (sVal, sKey) {
		sCleanedPhone = sCleanedPhone.replace(new RegExp('^' + sKey, 'g'), sVal);
	});

	return sCleanedPhone;
};

/**
 * @param {function} fResponseHandler
 * @param {object} oContext
 */
CPhone.prototype.getLogs = function (fResponseHandler, oContext)
{
	this.missedCalls(false);
	this.provider.getLogs(fResponseHandler, oContext);
};

/**
 * @param {string} sPhone
 */
CPhone.prototype.getCleanedPhone = function (sPhone)
{
	sPhone = sPhone ? sPhone : '';
	//return sPhone.replace(/client:|default/g, '');
	return sPhone.replace('client:', '');
};

var Phone = new CPhone();

// prevent load phone in other tabs
if (window.localStorage)
{
	$(window).on('storage', function () {
		if (window.localStorage.getItem('p7phoneLoad') !== 'false')
		{
			window.localStorage.setItem('p7phoneLoad', 'false'); //triggering from other tabs
		}
	});

	window.localStorage.setItem('p7phoneLoad', (Math.floor(Math.random() * (1000 - 100) + 100)).toString()); //random - storage event triggering only if key has been changed
	window.setTimeout(function () { //wait until the triggering storage event
		if (Phone && (window.localStorage.getItem('p7phoneLoad') !== 'false' || window.sessionStorage.getItem('p7phoneTab')))
		{
			Phone.init();
			window.sessionStorage.setItem('p7phoneTab', 'true'); //for phone tab detection, live only one session
		}
	}, 1000);
}

module.exports = Phone;
