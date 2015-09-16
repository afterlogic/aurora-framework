'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	Settings = require('core/js/Settings.js'),
	Screens = require('core/js/Screens.js'),
	Routing = require('core/js/Routing.js'),
	WindowOpener = require('core/js/WindowOpener.js'),
	Browser = require('core/js/Browser.js'),
	
	bMobileDevice = false,
	bMobileApp = false
;

require('core/js/splitter.js'); // necessary in mail and contacts modules, not for mobile version
require('core/js/koBindings.js');
require('core/js/koExtendings.js');

if (!bMobileDevice && !bMobileApp)
{
	require('core/js/customTooltip.js');
	require('core/js/koBindingsNotMobile.js');
}

require('core/js/enums.js');

function CApp()
{
	this.oModules = {};
	this.bSingleMode = false;
	this.oHeaderScreen = null;
	this.bAuth = window.pSevenAppData.Auth;
}

CApp.prototype.init = function (oAvaliableModules)
{
	this.initModules(oAvaliableModules);
	
	if (this.bAuth)
	{
		var Accounts = require('modules/Mail/js/AccountList.js');
		this.currentAccountId = Accounts.currentId;
		this.defaultAccountId = Accounts.defaultId;
		this.hasAccountWithId = _.bind(Accounts.hasAccountWithId, Accounts);
		this.currentAccountId.valueHasMutated();
	}
	
	this.initScreens();
	
	Routing.init();
};

CApp.prototype.initModules = function (oAvaliableModules)
{
	var oModules = {};
	
	_.each(Settings.Modules, function (sModuleName) {
		if (App.isAuth())
		{
			if (sModuleName !== 'auth')
			{
				oModules[sModuleName] = oAvaliableModules[sModuleName]();
			}
		}
		else
		{
			if (sModuleName === 'auth')
			{
				oModules[sModuleName] = oAvaliableModules[sModuleName]();
			}
		}
	});
	
	this.oModules = oModules;
};

CApp.prototype.initScreens = function ()
{
	_.each(this.oModules, function (oModule, sModuleName) {
		Screens.addToScreenList(sModuleName, oModule.ScreenList);
	});
	
	Screens.addToScreenList('', require('core/js/screenList.js'));
	
	if (!this.bSingleMode && this.isAuth())
	{
		this.oHeaderScreen = Screens.showNormalScreen('header');
	}
	
	Screens.initInformation();
};

CApp.prototype.getModulesTabs = function ()
{
	var aTabs = [];
	
	_.each(this.oModules, function (oModule, sName) {
		oModule.HeaderItem.setName(sName);
		aTabs.push(oModule.HeaderItem);
	});
	
	return aTabs;
};

CApp.prototype.getModulesPrefetchers = function ()
{
	var aPrefetchers = [];
	
	_.each(this.oModules, function (oModule, sName) {
		if (oModule.Prefetcher)
		{
			aPrefetchers.push(oModule.Prefetcher);
		}
	});
	
	return aPrefetchers;
};

CApp.prototype.getCurrentModuleBrowserTitle = function (bBrowserFocused)
{
	var
		oModule = this.oModules[Screens.currentScreen()],
		sTitle = ''
	;
	
	if (oModule && $.isFunction(oModule.getBrowserTitle))
	{
		sTitle = oModule.getBrowserTitle(bBrowserFocused);
	}
	
	return sTitle;
};

CApp.prototype.isAuth = function ()
{
	return this.bAuth;
};

/**
 * @param {number=} iLastErrorCode
 */
CApp.prototype.logout = function (iLastErrorCode)
{
	var
		Ajax = require('core/js/Ajax.js'),
		oParameters = {'Action': 'SystemLogout'}
	;
	
	if (iLastErrorCode)
	{
		oParameters.LastErrorCode = iLastErrorCode;
	}
	
	Ajax.send(oParameters, this.onLogout, this);
	
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
	
	if (Utils.isNonEmptyString(Settings.CustomLogoutUrl))
	{
		window.location.href = Settings.CustomLogoutUrl;
	}
	else
	{
		Utils.clearAndReloadLocation(Browser.ie8AndBelow, true);
	}
};

CApp.prototype.isModuleIncluded = function (sName)
{
	return this.oModules[sName] !== undefined;
};

var App = new CApp();

module.exports = App;