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

	this.action.subscribe(function(sAction) {
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

				App.Screens.hidePopup(PhonePopup);
				App.desktopNotify('hide');
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

				App.Screens.hidePopup(PhonePopup);
				App.desktopNotify('hide');
				this.provider.answer();
				break;
		}
	}, this);
}

CPhone.prototype.init = function ()
{
	$.ajaxSettings.cache = true;

	this.provider = AppData.User.VoiceProvider === 'sip' ? new CPhoneWebrtc() : new CPhoneTwilio();
	this.action(Enums.PhoneAction.OfflineInit);

	/*this.provider = new CPhoneTwilio(function (bResult) {
		self.voiceApp(bResult);
	});*/

};

CPhone.prototype.log = function ()
{
	if (false && window.console && window.console.log)
	{
		window.console.log.apply(window.console, arguments);
	}
};

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

CPhone.prototype.showError = function (iErrCode)
{
	if (1 === Utils.pInt(iErrCode))
	{
		App.Api.showError(Utils.i18n('PHONE/ERROR_SERVER_UNAVAILABLE'), false, true);
	}
};

CPhone.prototype.phoneSupport = function (bIsWebrtc, sFlashVersion)
{
	var fGetFlashVersion = function (){ // version format '00,0,000'
			try { //ie
				try { //avoid fp6 minor version lookup issues see: http://blog.deconcept.com/2006/01/11/getvariable-setvariable-crash-internet-explorer-flash-6/
					var axo = new ActiveXObject('ShockwaveFlash.ShockwaveFlash.6');

					try { axo.AllowScriptAccess = 'always'; }
					catch(eX) { return '6,0,0'; }
				} catch(eX) {}
				return new ActiveXObject('ShockwaveFlash.ShockwaveFlash').GetVariable('$version').replace(/\D+/g, ',').match(/^,?(.+),?$/)[1];
			} catch(eX) { //other browsers
				try {
					if(navigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin){
						return (navigator.plugins["Shockwave Flash 2.0"] || navigator.plugins["Shockwave Flash"]).description.replace(/\D+/g, ",").match(/^,?(.+),?$/)[1];
					}
				} catch(eXt) {}
			}
			return '0,0,0';
		};

	if (bIsWebrtc && !App.browser.chrome && !sFlashVersion && !bIsIosDevice)
	{
		this.log('*************** Browser not supported');
	}
	else if (bIsIosDevice)
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

CPhone.prototype.incomingCall = function (sNumber)
{
	var
		self,
		oParameters,
		fShowAll
	;

	this.action(Enums.PhoneAction.Incoming);

	if (sNumber)
	{
		self = this;
		oParameters = {
			'Action': 'ContactSuggestions',
			'Search': sNumber,
			'PhoneOnly': '1'
		};
		fShowAll = function (sText) {
			self.report(Utils.i18n('PHONE/INCOMING_CALL_FROM') + ' ' + sText);
			App.Screens.showPopup(PhonePopup, [{
				text: sText
			}]);
			App.desktopNotify({
				action: 'show',
				title: sText + ' calling...',
				body: 'Click here to answer.\r\n To drop the call, click End in the web interface.',
				callback: _.bind(function() {
					self.action(Enums.PhoneAction.IncomingConnect);
				}, self),
				timeout: 60000
			});
		};

		App.Ajax.send(oParameters, function (oData) {

			if (oData && oData.Result && oData.Result.List && oData.Result.List[0] && oData.Result.List[0].Phones)
			{
				var sUser = '';

				$.each(oData.Result.List[0].Phones, function (sKey, sUserPhone) {
					var
						oUser = oData.Result.List[0],
						regExp = /[()\s_\-]/g,
						sCleanedPhone = (sNumber.replace(regExp, '')),
						sCleanedUserPhone = (sUserPhone.replace(regExp, ''))
					;

					if(sCleanedPhone === sCleanedUserPhone)
					{
						sUser = oUser.Name === '' ? oUser.Email + ' ' + sUserPhone : oUser.Name + ' ' + sUserPhone;
						fShowAll(sUser);
						return false;
					}
				}, this);

				if(sUser === '')
				{
					fShowAll(sNumber);
				}
			}
			else
			{
				fShowAll(sNumber);
			}

		}, this);

		this.missedCalls(true);
	}
};

CPhone.prototype.getFormattedPhone = function (sPhone)
{
	sPhone = sPhone.toString();

	var
		oPrefixes = {
			'8': '7'
		},
		sCleanedPhone = (/#/g).test(sPhone) ? sPhone.split('#')[1] : sPhone.replace(/[()\s_\-+]/g, '')
	;

	_.each(oPrefixes, function(sVal, sKey){
		sCleanedPhone = sCleanedPhone.replace(new RegExp('^' + sKey, 'g'), sVal);
	});

	return sCleanedPhone;
};

CPhone.prototype.getLogs = function (fResponseHandler, oContext)
{
	this.missedCalls(false);
	this.provider.getLogs(fResponseHandler, oContext);
};

CPhone.prototype.getCleanedPhone = function (sPhone)
{
	sPhone = sPhone ? sPhone : '';
	//return sPhone.replace(/client:|default/g, '');
	return sPhone.replace('client:', '');
};