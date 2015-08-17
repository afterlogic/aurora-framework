
/**
 * @constructor
 */
function AppHelpDesk()
{
	AbstractApp.call(this);
	
	// for social in iframe
	if (window.opener && window.frameElement)
	{
		window.close();
		window.opener.location.reload();
	}

	this.Routing = new CRouting();
	this.Links = new CLinkBuilder();

	this.currentScreen = this.Screens.currentScreen;
	this.currentScreen.subscribe(this.setTitle, this);
	this.focused = ko.observable(true);
	this.focused.subscribe(this.setTitle, this);
	
	this.init();
	
	this.initHeaderInfo();
}

_.extend(AppHelpDesk.prototype, AbstractApp.prototype);

AppHelpDesk.prototype.init = function ()
{
	var
		oUserSettings = new CUserSettingsModel(),
		oAppSettings = new CAppSettingsModel()
	;
	
	oAppSettings.parse(AppData['App']);
	AppData.App = oAppSettings;

	oUserSettings.parse(AppData['User']);
	AppData.User = oUserSettings;
};

AppHelpDesk.prototype.logout = function ()
{
	App.Ajax.sendExt({'Action': 'HelpdeskLogout'}, this.onLogout, this);
};

AppHelpDesk.prototype.authProblem = function ()
{
	this.logout();
};

AppHelpDesk.prototype.onLogout = function ()
{
	window.location.reload();
};

AppHelpDesk.prototype.run = function ()
{
	this.Screens.init();
	
	if (AppData && AppData['Auth'])
	{
		this.Routing.init(Enums.Screens.Helpdesk);
	}
	else
	{
		this.Screens.showCurrentScreen(Enums.Screens.Login);
		if (AppData && AppData['LastErrorCode'] === Enums.Errors.AuthError)
		{
			this.Api.showError(Utils.i18n('WARNING/AUTH_PROBLEM'), false, true);
		}
		
		if (AppData && AppData['HelpdeskActivatedEmail'])
		{
			this.Api.showReport(Utils.i18n('HELPDESK/ACCOUNT_ACTIVATED'));
		}
	}
};

AppHelpDesk.prototype.initHeaderInfo = function ()
{
	if (this.browser.ie)
	{
		$(document)
			.bind('focusin', _.bind(this.onFocus, this))
			.bind('focusout', _.bind(this.onBlur, this))
		;
	}
	else
	{
		$(window)
			.bind('focus', _.bind(this.onFocus, this))
			.bind('blur', _.bind(this.onBlur, this))
		;
	}
};

AppHelpDesk.prototype.onFocus = function ()
{
	this.focused(true);
};

AppHelpDesk.prototype.onBlur = function ()
{
	this.focused(false);
};

/**
 * @param {string=} sTitle
 */
AppHelpDesk.prototype.setTitle = function (sTitle)
{
	document.title = '.';
	document.title = this.getTitleByScreen();
};

AppHelpDesk.prototype.getTitleByScreen = function ()
{
	var sTitle = '';
	
	switch (this.currentScreen())
	{
		case Enums.Screens.Login:
			sTitle = Utils.i18n('TITLE/HELPDESK', null, '');
			break;
		case Enums.Screens.Helpdesk:
			sTitle = Utils.i18n('TITLE/HELPDESK');
			break;
		case Enums.Screens.Settings:
			sTitle = Utils.i18n('TITLE/SETTINGS');
			break;
	}
	
	if (sTitle === '')
	{
		sTitle = AppData['HelpdeskSiteName'];
	}
	else if (AppData['HelpdeskSiteName'] !== '')
	{
		sTitle += ' - ' + AppData['HelpdeskSiteName'];
	}
	
	return sTitle;
};

AppHelpDesk.prototype.addScreenToHeader = function () {};
