
/**
 * @constructor
 */
function AppBase()
{
	AbstractApp.call(this);
	
	this.headerTabs = ko.observableArray([]);
	this.screensTitle = {};

	this.Phone = null;
	
	this.Routing = new CRouting();
	this.Links = new CLinkBuilder();
	this.MessageSender = new CMessageSender();
	this.Prefetcher = new CPrefetcher();
	this.MailCache = null;
	this.ContactsCache = new CContactsCache();
	this.CalendarCache = new CCalendarCache();

	this.currentScreen = this.Screens.currentScreen;
	this.currentScreen.subscribe(this.setTitle, this);
	this.focused = ko.observable(true);
	this.focused.subscribe(function() {
		if (!AppData.SingleMode && !window.opener)
		{
			this.setTitle();
		}
	}, this);

	this.filesRecievedAnim = ko.observable(false).extend({'autoResetToFalse': 500});

	this.init();
	
	this.newMessagesCount = this.MailCache.newMessagesCount;
	this.newMessagesCount.subscribe(this.setTitle, this);

	this.currentMessage = this.MailCache.currentMessage;
	this.currentMessage.subscribe(this.setTitle, this);

	this.notification = null;
	
	this.initHeaderInfo();
	
	this.sessionTimeoutFunctions = [];
	this.initSessionTimeout();
	
	this.sResetPassHash = Utils.Common.getRequestParam('reset-pass') || '';
}

_.extend(AppBase.prototype, AbstractApp.prototype);

// proto

AppBase.prototype.initPhone = function (bAllow)
{
	return null;
};

AppBase.prototype.init = function ()
{
	var
		oRawUserSettings = /** @type {Object} */ AppData['User'],
		oUserSettings = new CUserSettingsModel(),
		aRawAccounts = AppData['Accounts'],
		oAccounts = new CAccountListModel(),
		oRawAppSettings = /** @type {Object} */ AppData['App'],
		oAppSettings = new CAppSettingsModel(!!oRawAppSettings.AllowOpenPGP && this.Api.isPgpSupported())
	;
	
	oAppSettings.parse(oRawAppSettings);
	AppData.App = oAppSettings;

	oUserSettings.parse(oRawUserSettings);
	AppData.User = oUserSettings;

	oAccounts.parse(Utils.pInt(AppData['Default']), aRawAccounts);
	AppData.Accounts = oAccounts;

	this.MailCache = new CMailCache();
	this.Phone = this.initPhone(oUserSettings.AllowVoice && !this.browser.ie && !bMobileApp);

	this.useGoogleAnalytics();
	
	this.collectScreensData();
	
	$(window).unload(function() {
		if (!bMobileDevice)
		{
			Utils.WindowOpener.closeAll();
		}
	});

	this.nowDateNumber = ko.observable(moment().date());
	window.setInterval(function () {
		App.fastMomentDateTrigger();
		App.nowDateNumber(moment().date());
	}, 1000 * 60); // every minute
	this.nowDateNumber.subscribe(function () {
		this.MailCache.changeDatesInMessages();
	}, this);
	
	if (this.browser.ie8AndBelow)
	{
		$('body').css('overflow', 'hidden');
	}
};

AppBase.prototype.collectScreensData = function () {};

/**
 * @param {Function} fHelpdeskUpdate
 */
AppBase.prototype.registerHelpdeskUpdateFunction = function (fHelpdeskUpdate)
{
	this.fHelpdeskUpdate = fHelpdeskUpdate;
};

AppBase.prototype.updateHelpdesk = function ()
{
	if (this.fHelpdeskUpdate)
	{
		this.fHelpdeskUpdate();
	}
};

AppBase.prototype.useGoogleAnalytics = function ()
{
	var
		ga = null,
		s = null
	;
	
	if (AppData.App.GoogleAnalyticsAccount && 0 < AppData.App.GoogleAnalyticsAccount.length)
	{
		window._gaq = window._gaq || [];
		window._gaq.push(['_setAccount', AppData.App.GoogleAnalyticsAccount]);
		window._gaq.push(['_trackPageview']);

		ga = document.createElement('script');
		ga.type = 'text/javascript';
		ga.async = true;
		ga.src = ('https:' === document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		s = document.getElementsByTagName('script')[0];
		s.parentNode.insertBefore(ga, s);
	}
};

/**
 * @param {number=} iLastErrorCode
 */
AppBase.prototype.logout = function (iLastErrorCode)
{
	var oParameters = {'Action': 'SystemLogout'};
	
	if (iLastErrorCode)
	{
		oParameters.LastErrorCode = iLastErrorCode;
	}
	
	App.Ajax.send(oParameters, this.onLogout, this);
	
	AppData.Auth = false;
};

AppBase.prototype.authProblem = function ()
{
	this.logout(Enums.Errors.AuthError);
};

AppBase.prototype.onLogout = function ()
{
	Utils.WindowOpener.closeAll();
	
	App.Routing.finalize();
	
	if (AppData.App.CustomLogoutUrl !== '')
	{
		window.location.href = AppData.App.CustomLogoutUrl;
	}
	else
	{
		Utils.Common.clearAndReloadLocation(App.browser.ie8AndBelow, true);
	}
};

AppBase.prototype.getAccounts = function ()
{
	return AppData.Accounts;
};

AppBase.prototype.run = function ()
{
	this.Screens.init();

	Utils.checkCookies();

	if (bIsIosDevice && AppData && AppData['Auth'] && AppData.App.IosDetectOnLogin && AppData.App.AllowIosProfile)
	{
		window.location.href = '?ios';
	}
	else if (AppData && AppData['Auth'])
	{
		AppData.SingleMode = this.Routing.isSingleMode();
		
		if (AppData.SingleMode && window.opener)
		{
			AppData.Accounts.populateIdentitiesFromSourceAccount(window.opener.App.getAccounts());
		}
		
		if (AppData.App.AllowWebMail)
		{
			this.MailCache.init();
		}
		
		if (AppData.HelpdeskRedirect && this.Routing.currentScreen !== Enums.Screens.Helpdesk)
		{
			this.Routing.setHash([Enums.Screens.Helpdesk]);
		}
		
		this.initRouting();
	}
	else if (AppData && AppData.App.CustomLoginUrl !== '')
	{
		window.location.href = AppData.App.CustomLoginUrl;
	}
	else
	{
		this.Screens.showCurrentScreen(Enums.Screens.Login);
		this.checkLoginScreenStartError();
	}

	this.phoneInOneTab();

	if (AppData.App.AllowAppRegisterMailto)
	{
		Utils.registerMailto(this.browser.firefox);
	}
	
	AppData.Accounts.unlockEditedWhenCurrentChange();
	
	this.startResetPass();
	
	this.displaySocialWelcome();
};

AppBase.prototype.startResetPass = function ()
{
	if (AppData.Auth && this.sResetPassHash !== '')
	{
		App.Api.showChangeDefaultAccountPasswordPopup();
	}
};

AppBase.prototype.displaySocialWelcome = function ()
{
	var
		oDefaultAccount = AppData.Accounts.getDefault(),
		bHasMailAccount = AppData.Accounts.hasMailAccount()
	;
	
	if (!bHasMailAccount && oDefaultAccount && !App.Storage.hasData('SocialWelcomeShowed' + oDefaultAccount.id()) && AppData.User.SocialName !== '')
	{
		App.Screens.showPopup(ConfirmPopup, [
			Utils.i18n('MAILBOX/INFO_SOCIAL_WELCOME', {
				'SOCIALNAME': AppData.User.SocialName,
				'SITENAME': AppData.App.SiteName,
				'EMAIL': oDefaultAccount.email()
			}),
			function (bConfigureMail) {
				if (bConfigureMail)
				{
					App.Api.showConfigureMailPopup();
				}
			},
			'',
			Utils.i18n('MAILBOX/BUTTON_CONFIGURE_MAIL'),
			Utils.i18n('MAIN/BUTTON_CLOSE')
		]);
		
		App.Storage.setData('SocialWelcomeShowed' + oDefaultAccount.id(), '1');
	}
};

AppBase.prototype.checkLoginScreenStartError = function ()
{
	var iError = Utils.pInt(Utils.Common.getRequestParam('error'));
	
	if (iError !== 0)
	{
		App.Api.showErrorByCode({'ErrorCode': iError, 'ErrorMessage': ''}, '', true);
	}

	if (AppData && AppData['LastErrorCode'] === Enums.Errors.AuthError)
	{
		this.Api.showError(Utils.i18n('WARNING/AUTH_PROBLEM'), false, true);
	}
};

AppBase.prototype.initRouting = function ()
{
	var bDefaultTabInEnum = !!_.find(Enums.Screens, function (sScreenInEnum) {
		return sScreenInEnum === AppData.App.DefaultTab;
	});

	if (bDefaultTabInEnum && (AppData.App.AllowWebMail || AppData.App.DefaultTab !== Enums.Screens.Mailbox))
	{
		this.Routing.init(AppData.App.DefaultTab);
	}
	else if (AppData.App.AllowWebMail)
	{
		this.Routing.init(Enums.Screens.Mailbox);
	}
	else if (this.headerTabs().length > 0)
	{
		this.Routing.init(this.headerTabs()[0].name);
	}
};

/**
 * @param {string} sName
 * @return {string}
 */
AppBase.prototype.getHelpLink = function (sName)
{
	return AppData && AppData['Links'] && AppData['Links'][sName] ? AppData['Links'][sName] : '';
};

/**
 * @param {string} sName
 * @param {string} sHeaderTitle
 * @param {string} sDocumentTitle
 * @param {string} sTemplateName
 * @param {Object} oViewModelClass
 * @param {boolean} koVisibleTab = undefined
 * @param {Object=} koRecivedAnim = undefined
 */
AppBase.prototype.addScreenToHeader = function (sName, sHeaderTitle, sDocumentTitle, sTemplateName,
	oViewModelClass, koVisibleTab, koRecivedAnim)
{
	var
		mHash = this.Routing.buildHashFromArray([sName]),
		oApp = this
	;
	
	if (sName === Enums.Screens.Helpdesk)
	{
		mHash = ko.computed(function () {
			return '#' + oApp.Routing.lastHelpdeskHash();
		});
	}
	
	Enums.Screens[sName] = sName;
	
	this.Screens.oScreens[sName] = {
		'Model': oViewModelClass,
		'TemplateName': sTemplateName
	};
	this.headerTabs.push({
		'name': sName,
		'title': sHeaderTitle,
		'hash': mHash,
		'koVisibleTab': koVisibleTab,
		'koRecivedAnim': koRecivedAnim
	});
	this.screensTitle[sName] = sDocumentTitle;
};

AppBase.prototype.registerSessionTimeoutFunction = function (oSessionTimeoutFunction)
{
	this.sessionTimeoutFunctions.push(oSessionTimeoutFunction);
};

AppBase.prototype.initSessionTimeout = function ()
{
	this.setSessionTimeout();
	$('body')
		.on('click', _.bind(this.setSessionTimeout, this))
		.on('keydown', _.bind(this.setSessionTimeout, this))
	;
};

AppBase.prototype.setSessionTimeout = function ()
{
	clearTimeout(this.iSessionTimeout);
	if (AppData && AppData.Auth && AppData.App.IdleSessionTimeout)
	{
		this.iSessionTimeout = setTimeout(_.bind(this.logoutBySessionTimeout, this), AppData.App.IdleSessionTimeout);
	}
};

AppBase.prototype.logoutBySessionTimeout = function ()
{
	if (AppData.Auth)
	{
		_.each(this.sessionTimeoutFunctions, function (oFunc) {
			oFunc();
		});
		_.delay(_.bind(this.logout, this), 500);
	}
};

AppBase.prototype.initHeaderInfo = function ()
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

AppBase.prototype.onFocus = function ()
{
	this.focused(true);
};

AppBase.prototype.onBlur = function ()
{
	this.focused(false);
};

/**
 * @param {string=} sTitle
 */
AppBase.prototype.setTitle = function (sTitle)
{
	var 
		sNewMessagesCount = this.newMessagesCount(),
		sTemp = ''
	;
	
	sTitle = sTitle || '';
	
	if (this.focused() || sNewMessagesCount === 0 || !AppData.App.AllowWebMail)
	{
		sTitle = this.getTitleByScreen();
	}
	else
	{
		sTitle = Utils.i18n('TITLE/HAS_UNSEEN_MESSAGES_PLURAL', {'COUNT': sNewMessagesCount}, null, sNewMessagesCount) + ' - ' + AppData.Accounts.getEmail();
	}

	if (this.favico)
	{
		sTemp = 99 < sNewMessagesCount ? '99+' : sNewMessagesCount;
		if (this.favico._cachevalue !== sTemp)
		{
			this.favico._cachevalue = sTemp;
			this.favico.badge(sTemp);
		}
	}

	document.title = '.';
	document.title = sTitle;
};

AppBase.prototype.getTitleByScreen = function ()
{
	var
		sTitle = '',
		sSubject = ''
	;
	
	try
	{
		if (this.MailCache.currentMessage())
		{
			sSubject = this.MailCache.currentMessage().subject();
		}
	}
	catch (oError) {}
	
	switch (this.currentScreen())
	{
		case Enums.Screens.Login:
			sTitle = Utils.i18n('TITLE/LOGIN', null, '');
			break;
		case Enums.Screens.Mailbox:
			sTitle = AppData.Accounts.getEmail() + ' - ' + Utils.i18n('TITLE/MAILBOX');
			break;
		case Enums.Screens.Compose:
		case Enums.Screens.SingleCompose:
			sTitle = AppData.Accounts.getEmail() + ' - ' + Utils.i18n('TITLE/COMPOSE');
			break;
		case Enums.Screens.SingleMessageView:
			sTitle = AppData.Accounts.getEmail() + ' - ' + Utils.i18n('TITLE/VIEW_MESSAGE');
			if (sSubject)
			{
				sTitle = sSubject + ' - ' + sTitle;
			}
			break;
		default:
			if (this.screensTitle[this.currentScreen()])
			{
				sTitle = this.screensTitle[this.currentScreen()];
			}
			break;
	}
	
	if (sTitle === '')
	{
		sTitle = AppData.App.SiteName;
	}
	else
	{
		sTitle += (AppData.App.SiteName && AppData.App.SiteName !== '') ? ' - ' + AppData.App.SiteName : '';
	}

	return sTitle;
};

/**
 * @param {string} sAction
 * @param {string=} sTitle
 * @param {string=} sBody
 * @param {string=} sIcon
 * @param {Function=} fnCallback
 * @param {number=} iTimeout
 */
AppBase.prototype.desktopNotify = function (sAction, sTitle, sBody, sIcon, fnCallback, iTimeout)
{
	Utils.desktopNotify(sAction, sTitle, sBody, sIcon, fnCallback, iTimeout);
};

AppBase.prototype.phoneInOneTab = function ()
{
	// prevent load phone in other tabs
	if (this.Phone && window.localStorage)
	{
		var self = this;
		$(window).on('storage', function(e) {
			if (window.localStorage.getItem('p7phoneLoad') !== 'false')
			{
				window.localStorage.setItem('p7phoneLoad', 'false'); //triggering from other tabs
			}
		});

		window.localStorage.setItem('p7phoneLoad', (Math.floor(Math.random() * (1000 - 100) + 100)).toString()); //random - storage event triggering only if key has been changed
		window.setTimeout(function() { //wait until the triggering storage event
			if (!AppData.SingleMode && self.Phone && (window.localStorage.getItem('p7phoneLoad') !== 'false' || window.sessionStorage.getItem('p7phoneTab')))
			{
				self.Phone.init();
				window.sessionStorage.setItem('p7phoneTab', 'true'); //for phone tab detection, live only one session
			}
		}, 1000);
	}
};
