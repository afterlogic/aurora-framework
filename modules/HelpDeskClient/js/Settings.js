'use strict';

var
	ko = require('knockout'),
	
	Types = require('modules/Core/js/utils/Types.js')
;

module.exports = {
	ActivatedEmail: '', // todo: showReport(Utils.i18n('%MODULENAME%/ACCOUNT_ACTIVATED'));
	AllowEmailNotifications: false,
	AllowFacebookAuth: false,
	AllowGoogleAuth: false,
	AllowTwitterAuth: false,
	AfterThreadsReceivingAction: 'add', // add, close
	ClientDetailsUrl: '',
	ClientSiteName: '', // todo
	ForgotHash: '',
	IsAgent: false,
	LoginLogoUrl: '',
	SelectedThreadId: 0,
	signature: ko.observable(''),
	SocialEmail: '',
	SocialIsLoggedIn: false, // ???
	ThreadsPerPage: 10,
	UserEmail: '',
	useSignature: ko.observable(false),
	
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.ActivatedEmail = Types.pString(oAppDataSection.ActivatedEmail); // todo: showReport(Utils.i18n('%MODULENAME%/ACCOUNT_ACTIVATED'));
			this.AllowEmailNotifications = !!oAppDataSection.AllowEmailNotifications;
			this.AllowFacebookAuth = !!oAppDataSection.AllowFacebookAuth;
			this.AllowGoogleAuth = !!oAppDataSection.AllowGoogleAuth;
			this.AllowTwitterAuth = !!oAppDataSection.AllowTwitterAuth;
			this.AfterThreadsReceivingAction = Types.pString(oAppDataSection.AfterThreadsReceivingAction); // add, close
			this.ClientDetailsUrl = Types.pString(oAppDataSection.ClientDetailsUrl);
			this.ClientSiteName = Types.pString(oAppDataSection.ClientSiteName); // todo
			this.ForgotHash = Types.pString(oAppDataSection.ForgotHash);
			this.IsAgent = !!oAppDataSection.IsAgent;
			this.LoginLogoUrl = Types.pString(oAppDataSection.LoginLogoUrl);
			this.SelectedThreadId = Types.pInt(oAppDataSection.SelectedThreadId);
			this.signature(Types.pString(oAppDataSection.Signature));
			this.SocialEmail = Types.pString(oAppDataSection.SocialEmail);
			this.SocialIsLoggedIn = !!oAppDataSection.SocialIsLoggedIn; // ???
			this.ThreadsPerPage = oAppDataSection.ThreadsPerPage; // add to settings
			this.UserEmail = Types.pString(oAppDataSection.UserEmail);
			this.useSignature(!!oAppDataSection.UseSignature);
		}
	},
	
	update: function (sAllowEmailNotifications, sHelpdeskSignature, sHelpdeskSignatureEnable) {
		this.AllowEmailNotifications = sAllowEmailNotifications === '1';
		this.signature(sHelpdeskSignature);
		this.useSignature(sHelpdeskSignatureEnable === '1');
	}
};
