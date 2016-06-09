'use strict';

var Types = require('modules/CoreClient/js/utils/Types.js');

module.exports = {
	SipImpi: '102',
	SipOutboundProxyUrl: '',
	SipPassword: 'user02',
	SipRealm: '192.168.0.59',
	SipWebsocketProxyUrl: 'ws://192.168.0.59:8088/ws',
	VoiceProvider: '',
	
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.SipImpi = Types.pString(oAppDataSection.SipImpi);
			this.SipOutboundProxyUrl = Types.pString(oAppDataSection.SipOutboundProxyUrl);
			this.SipPassword = Types.pString(oAppDataSection.SipPassword);
			this.SipRealm = Types.pString(oAppDataSection.SipRealm);
			this.SipWebsocketProxyUrl = Types.pString(oAppDataSection.SipWebsocketProxyUrl);
			this.VoiceProvider = Types.pString(oAppDataSection.VoiceProvider);
		}
	}
};