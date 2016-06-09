'use strict';

var
	_ = require('underscore'),
	
	Types = require('modules/CoreClient/js/utils/Types.js')
;

module.exports = {
	ServerModuleName: 'Auth',
	HashModuleName: 'auth',
	
	AllowChangeLanguage: false,
	AllowRegistration: false,
	AllowResetPassword: false,
	CustomLoginUrl: '',
	CustomLogoUrl: '',
	DemoLogin: '',
	DemoPassword: '',
	InfoText: '',
	LoginAtDomain: '',
	LoginFormType: 0, // 0 - email, 3 - login, 4 - both
	LoginSignMeType: 0, // 0 - off, 1 - on, 2 - don't use
	RegistrationDomains: [],
	RegistrationQuestions: [],
	UseFlagsLanguagesView: false,
	
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.AllowChangeLanguage = !!oAppDataSection.AllowChangeLanguage;
			this.AllowRegistration = !!oAppDataSection.AllowRegistration;
			this.AllowResetPassword = !!oAppDataSection.AllowResetPassword;
			this.CustomLoginUrl = Types.pString(oAppDataSection.CustomLoginUrl);
			this.CustomLogoUrl = Types.pString(oAppDataSection.CustomLogoUrl);
			this.DemoLogin = Types.pString(oAppDataSection.DemoLogin);
			this.DemoPassword = Types.pString(oAppDataSection.DemoPassword);
			this.InfoText = Types.pString(oAppDataSection.InfoText);
			this.LoginAtDomain = Types.pString(oAppDataSection.LoginAtDomain);
			this.LoginFormType = Types.pInt(oAppDataSection.LoginFormType);
			this.LoginSignMeType = Types.pInt(oAppDataSection.LoginSignMeType);
			this.RegistrationDomains = _.isArray(oAppDataSection.RegistrationDomains) ? oAppDataSection.RegistrationDomains : [];
			this.RegistrationQuestions = _.isArray(oAppDataSection.RegistrationQuestions) ? oAppDataSection.RegistrationQuestions : [];
			this.UseFlagsLanguagesView = !!oAppDataSection.UseFlagsLanguagesView;
		}
	}
};