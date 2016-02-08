'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	modernizr = require('modernizr'),
	
	Types = require('core/js/utils/Types.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	Browser = require('core/js/Browser.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	Routing = require('core/js/Routing.js'),
	Screens = require('core/js/Screens.js'),
	Storage = require('core/js/Storage.js'),
	UserSettings = require('core/js/Settings.js'),
	WindowOpener = require('core/js/WindowOpener.js')
;

require('core/js/koBindings.js');
require('core/js/koExtendings.js');

require('core/js/enums.js');

require('core/js/vendors/inputosaurus.js');

require('jquery.cookie');

function InitNotMobileRequires()
{
	require('core/js/splitter.js'); // necessary in mail and contacts modules
	require('core/js/CustomTooltip.js');
	require('core/js/koBindingsNotMobile.js');
}

function InitModernizr()
{
	if (modernizr && navigator)
	{
		modernizr.addTest('pdf', function() {
			return !!_.find(navigator.mimeTypes, function (oMimeType) {
				return 'application/pdf' === oMimeType.type;
			});
		});

		modernizr.addTest('newtab', function() {
			return App.isNewTab();
		});

		modernizr.addTest('mobile', function() {
			return App.isMobile();
		});
		
		if (navigator)
		{
			modernizr.addTest('native-android-browser', function() {
				var ua = navigator.userAgent;
				return (ua.indexOf('Mozilla/5.0') > -1 && ua.indexOf('Android ') > -1 && ua.indexOf('534') > -1 && ua.indexOf('AppleWebKit') > -1);
				//return navigator.userAgent === 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/534.24 (KHTML, like Gecko) Chrome/11.0.696.34 Safari/534.24';
			});
		}
	}
}

function CApp()
{
	this.bAuth = window.pSevenAppData.Auth;
	this.bPublic = false;
	this.bNewTab = false;
	this.bMobile = false;
}

CApp.prototype.isAuth = function ()
{
	return this.bAuth;
};

CApp.prototype.setPublic = function ()
{
	this.bPublic = true;
};

CApp.prototype.isPublic = function ()
{
	return this.bPublic;
};

CApp.prototype.setNewTab = function ()
{
	this.bNewTab = true;
};

CApp.prototype.isNewTab = function ()
{
	return this.bNewTab;
};

CApp.prototype.setMobile = function ()
{
	this.bMobile = true;
};

CApp.prototype.isMobile = function ()
{
	return this.bMobile;
};

CApp.prototype.init = function ()
{
	ModulesManager.run('Auth', 'beforeAppRunning', [this.bAuth]);
	
	if (Browser.iosDevice && this.bAuth && UserSettings.SyncIosAfterLogin && UserSettings.AllowIosProfile)
	{
		window.location.href = '?ios';
	}
	
	if (this.bAuth && !this.bPublic)
	{
		var AccountList = require('modules/Mail/js/AccountList.js');
		this.currentAccountId = AccountList.currentId;
		this.defaultAccountId = AccountList.defaultId;
		this.hasAccountWithId = _.bind(AccountList.hasAccountWithId, AccountList);
		
		this.currentAccountEmail = ko.computed(function () {
			var oAccount = AccountList.getAccount(this.currentAccountId());
			return oAccount ? oAccount.email() : '';
		}, this);
		
		this.defaultAccount = ko.computed(function () {
			return AccountList.getAccount(this.defaultAccountId());
		}, this);
		this.defaultAccountEmail = ko.computed(function () {
			var oAccount = AccountList.getAccount(this.defaultAccountId());
			return oAccount ? oAccount.email() : '';
		}, this);
		this.defaultAccountFriendlyName = ko.computed(function () {
			var oAccount = AccountList.getAccount(this.defaultAccountId());
			return oAccount ? oAccount.friendlyName() : '';
		}, this);
		
		this.getAttendee = function (aAttendees) {
			return AccountList.getAttendee(
				_.map(aAttendees, function (mAttendee) {
					return Types.isString(mAttendee) ? mAttendee : mAttendee.email;
				}, this)
			);
		};
	}
	
	if (!this.bMobile)
	{
		InitNotMobileRequires();
	}
	InitModernizr();
	
	Screens.init();
	Routing.init();
	
	require('core/js/AppTab.js');
	if (!this.bNewTab)
	{
		require('core/js/Prefetcher.js');
	}
	
	Storage.setData('AuthToken', Storage.getData('AuthToken'));
	
	this.useGoogleAnalytics();

	if (!this.bMobile)
	{
		$(window).unload(function() {
			WindowOpener.closeAll();
		});
	}
	
	if (Browser.ie8AndBelow)
	{
		$('body').css('overflow', 'hidden');
	}
	
	this.checkCookies();
	
	this.showLastErrorOnLogin();
};

CApp.prototype.showLastErrorOnLogin = function ()
{
	if (!this.bAuth)
	{
		var iError = Types.pInt(Utils.getRequestParam('error'));

		if (iError !== 0)
		{
			Api.showErrorByCode({'ErrorCode': iError, 'ErrorMessage': ''}, '', true);
		}
		
		if (UserSettings.LastErrorCode === Enums.Errors.AuthError)
		{
			Screens.showError(Utils.i18n('WARNING/AUTH_PROBLEM'), false, true);
		}
	}
};

/**
 * @param {number=} iLastErrorCode
 */
CApp.prototype.logout = function (iLastErrorCode)
{
	ModulesManager.run('Auth', 'logout', [iLastErrorCode, this.onLogout, this]);
	
	this.bAuth = false;
};

CApp.prototype.authProblem = function ()
{
	this.logout(Enums.Errors.AuthError);
};

CApp.prototype.onLogout = function ()
{
	WindowOpener.closeAll();
	
	Routing.finalize();
	
	if (Types.isNonEmptyString(UserSettings.CustomLogoutUrl))
	{
		window.location.href = UserSettings.CustomLogoutUrl;
	}
	else
	{
		Utils.clearAndReloadLocation(Browser.ie8AndBelow, true);
	}
};

CApp.prototype.checkMobile = function () {
	/**
	 * UserSettings.IsMobile:
	 *	-1 - first time, mobile is not determined
	 *	0 - mobile is switched off
	 *	1 - mobile is switched on
	 */
	if (UserSettings.AllowMobile && UserSettings.IsMobile === -1)
	{
		var
			Ajax = require('core/js/Ajax.js'),
			bMobile = !window.matchMedia('all and (min-width: 768px)').matches ? 1 : 0
		;

		Ajax.send('Core', 'SetMobile', {'Mobile': bMobile}, function (oResponse) {
			if (bMobile && oResponse.Result)
			{
				window.location.reload();
			}
		}, this);
		
		return true;
	}
	
	return false;
};

CApp.prototype.useGoogleAnalytics = function ()
{
	var
		oGoogleAnalytics = null,
		oFirstScript = null
	;
	
	if (UserSettings.GoogleAnalyticsAccount && 0 < UserSettings.GoogleAnalyticsAccount.length)
	{
		window._gaq = window._gaq || [];
		window._gaq.push(['_setAccount', UserSettings.GoogleAnalyticsAccount]);
		window._gaq.push(['_trackPageview']);

		oGoogleAnalytics = document.createElement('script');
		oGoogleAnalytics.type = 'text/javascript';
		oGoogleAnalytics.async = true;
		oGoogleAnalytics.src = ('https:' === document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		oFirstScript = document.getElementsByTagName('script')[0];
		oFirstScript.parentNode.insertBefore(oGoogleAnalytics, oFirstScript);
	}
};

/**
 * @returns {Boolean}
 */
CApp.prototype.checkCookies = function ()
{
	$.cookie('checkCookie', '1', { path: '/' });
	var bResult = $.cookie('checkCookie') === '1';
	if (!bResult)
	{
		App.Screens.showError(Utils.i18n('WARNING/COOKIES_DISABLED'), false, true);
	}

	return bResult;
};

CApp.prototype.getCommonRequestParameters = function ()
{
	var oParameters = {
		AuthToken: Storage.getData('AuthToken')
	};
	
	if (UserSettings.TenantHash)
	{
		oParameters.TenantHash = UserSettings.TenantHash;
	}
	
	return oParameters;
};

var App = new CApp();

module.exports = App;
