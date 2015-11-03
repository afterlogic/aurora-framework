
/**
 * @constructor
 * @param {boolean} bAllowOpenPgp
 */
function CAppSettingsModel(bAllowOpenPgp)
{
	this.AllowWebMail  = true;

	// allows to edit common settings and calendar settings
	this.AllowUsersChangeInterfaceSettings = true;

	// allows to delete default account, allows to change default account properties
	this.AllowUsersChangeEmailSettings = true;

	// allows to add new accounts
	this.AllowUsersAddNewAccounts = true;
	
	this.SiteName = '';

	// list of available languages
	this.Languages = [
		{name: 'English', value: 'en'}
	];

	// list of available themes
	this.Themes = [
		'Default'
	];

	// list of available date formats
	this.DateFormats = [];
	
	this.DefaultLanguage = 'English';

	// maximum size of uploading attachment
	this.AttachmentSizeLimit = 10240000;
	this.ImageUploadSizeLimit = 10240000;
	
	this.FileSizeLimit = 10240000;

	// activate autosave
	this.AutoSave = true;
	this.AutoSaveIntervalSeconds = 60;
	this.IdleSessionTimeout = 0;
	
	// allows to insert an image to html-text in rich text editor
	this.AllowInsertImage = true;
	this.AllowBodySize = false;
	this.MaxBodySize = 600;
	this.MaxSubjectSize = 255;
	this.JoinReplyPrefixes = true;

	this.AllowAppRegisterMailto = true;
	this.AllowPrefetch = true;
	this.MaxPrefetchBodiesSize = 50000;

	this.LoginFormType = Enums.LoginFormType.Email;
	this.LoginAtDomainValue = '';
	this.AllowRegistration = false;
	this.AllowPasswordReset = false;
	this.RegistrationDomains = [];
	this.RegistrationQuestions = [];

	this.DemoWebMail = true;
	this.DemoWebMailLogin = '';
	this.DemoWebMailPassword = '';
	this.LoginDescription = '';
	this.GoogleAnalyticsAccount = '';
	this.ShowQuotaBar = false;
	this.ServerUseUrlRewrite = false;

	this.AllowLanguageOnLogin = false;
	this.FlagsLangSelect = false;
	
	this.CustomLoginUrl = '';
	this.CustomLogoutUrl = '';

	this.IosDetectOnLogin = false;
	
	this.AllowContactsSharing = false;

	this.DefaultLanguageShort = 'en';
	
	this.AllowOpenPgp = bAllowOpenPgp;
	
	this.DefaultTab = '';
	
	this.AllowIosProfile = true;
	
	this.PasswordMinLength = 0;
	this.PasswordMustBeComplex = false;
}
	
/**
 * Parses the application settings from the server.
 * 
 * @param {Object} oData
 */
CAppSettingsModel.prototype.parse = function (oData)
{
	this.AllowWebMail = !!oData.AllowWebMail;
	this.AllowUsersChangeInterfaceSettings = !!oData.AllowUsersChangeInterfaceSettings;
	this.AllowUsersChangeEmailSettings = !!oData.AllowUsersChangeEmailSettings;
	this.AllowUsersAddNewAccounts = !!oData.AllowUsersAddNewAccounts;
	this.SiteName = Utils.pString(oData.SiteName);
	this.Languages = oData.Languages;
	this.Themes = oData.Themes;
	this.DateFormats = oData.DateFormats;
	this.AttachmentSizeLimit = Utils.pInt(oData.AttachmentSizeLimit);
	this.ImageUploadSizeLimit = Utils.pInt(oData.ImageUploadSizeLimit);
	this.FileSizeLimit = Utils.pInt(oData.FileSizeLimit);
	this.AutoSave = !!oData.AutoSave;
	this.IdleSessionTimeout = Utils.pInt(oData.IdleSessionTimeout) * 60 * 1000; // converts minutes to milliseconds
	this.AllowInsertImage = !!oData.AllowInsertImage;
	this.AllowBodySize = !!oData.AllowBodySize;
	this.MaxBodySize = Utils.pInt(oData.MaxBodySize);
	this.MaxSubjectSize = Utils.pInt(oData.MaxSubjectSize);
	this.JoinReplyPrefixes = !!oData.JoinReplyPrefixes;
	this.AllowAppRegisterMailto = !!oData.AllowAppRegisterMailto;
	this.AllowPrefetch = !!oData.AllowPrefetch;

	this.LoginFormType = Utils.pInt(oData.LoginFormType);
	this.LoginSignMeType = Utils.pInt(oData.LoginSignMeType);
	this.LoginAtDomainValue = Utils.pString(oData.LoginAtDomainValue);
	this.AllowRegistration = !!oData.AllowRegistration;
	this.AllowPasswordReset = !!oData.AllowPasswordReset;
	this.RegistrationDomains = oData.RegistrationDomains;
	this.RegistrationQuestions = _.without(oData.RegistrationQuestions, '');
	
	this.DemoWebMail = !!oData.DemoWebMail;
	this.DemoWebMailLogin = Utils.pString(oData.DemoWebMailLogin);
	this.DemoWebMailPassword = Utils.pString(oData.DemoWebMailPassword);
	this.GoogleAnalyticsAccount = oData.GoogleAnalyticsAccount;
	this.ShowQuotaBar = !!oData.ShowQuotaBar;
	this.ServerUseUrlRewrite = !!oData.ServerUseUrlRewrite;

	this.AllowLanguageOnLogin = !bMobileApp && !!oData.AllowLanguageOnLogin;
	this.FlagsLangSelect = !!oData.FlagsLangSelect;

	this.DefaultLanguage = Utils.pString(oData.DefaultLanguage);
	this.LoginDescription = Utils.pString(oData.LoginDescription);
	
	this.CustomLoginUrl = Utils.pString(oData.CustomLoginUrl);
	this.CustomLogoutUrl = Utils.pString(oData.CustomLogoutUrl);

	this.IosDetectOnLogin = !!oData.IosDetectOnLogin;

	this.AllowContactsSharing = !!oData.AllowContactsSharing;

	if (oData.DefaultLanguageShort !== '')
	{
		this.DefaultLanguageShort = oData.DefaultLanguageShort;
	}
	this.DefaultTab = oData.DefaultTab;
	this.AllowIosProfile = !!oData.AllowIosProfile;
	this.PasswordMinLength = oData.PasswordMinLength;
	this.PasswordMustBeComplex = !!oData.PasswordMustBeComplex;
};