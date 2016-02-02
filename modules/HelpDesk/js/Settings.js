'use strict';

var _ = require('underscore');

module.exports = {
	init: function (oSettings) {
		_.extendOwn(this, oSettings);
	},
	update: function (sAllowEmailNotifications, sHelpdeskSignature, sHelpdeskSignatureEnable) {
		this.AllowEmailNotifications = sAllowEmailNotifications === '1';
		this.signature(sHelpdeskSignature);
		this.useSignature(sHelpdeskSignatureEnable === '1');
	}
};
