'use strict';

var _ = require('underscore');

module.exports = {
	init: function (oSettings) {
		_.extendOwn(this, oSettings);
	},
	update: function (sAllowHelpdeskNotifications, sHelpdeskSignature, sHelpdeskSignatureEnable) {
		this.AllowHelpdeskNotifications = sAllowHelpdeskNotifications === '1';
		this.helpdeskSignature(sHelpdeskSignature);
		this.helpdeskSignatureEnable(sHelpdeskSignatureEnable === '1');
	}
};