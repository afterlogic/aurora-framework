'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	
	Settings = require('core/js/Settings.js'),
	Screens = require('core/js/Screens.js'),
	Routing = require('core/js/Routing.js'),
	
	bMobileDevice = false,
	bMobileApp = false
;

require('core/js/splitter.js'); // necessary in mail and contacts modules, not for mobile version
require('core/js/knockoutBindings.js');

if (!bMobileDevice && !bMobileApp)
{
	require('core/js/customTooltip.js');
}

require('core/js/enums.js');

function CApp()
{
	this.oModules = {};
	this.bSingleMode = false;
	this.oHeaderScreen = null;
}

CApp.prototype.init = function (oAvaliableModules)
{
	this.initModules(oAvaliableModules);
	
	var Accounts = require('modules/Mail/js/AccountList.js');
	
	this.currentAccountId = Accounts.currentId;
	this.defaultAccountId = Accounts.defaultId;
	this.hasAccountWithId = _.bind(Accounts.hasAccountWithId, Accounts);
	this.currentAccountId.valueHasMutated();
	
	this.initScreens();
	
	Routing.init();
};

CApp.prototype.initModules = function (oAvaliableModules)
{
	var oModules = {};
	
	_.each(Settings.Modules, function (sModuleName) {
		oModules[sModuleName] = oAvaliableModules[sModuleName]();
	});
	
	this.oModules = oModules;
};

CApp.prototype.initScreens = function ()
{
	Screens.addToScreenList('', require('core/js/screenList.js'));
	
	_.each(this.oModules, function (oModule, sModuleName) {
		Screens.addToScreenList(sModuleName, oModule.ScreenList);
	});
	
	if (!this.bSingleMode)
	{
		this.oHeaderScreen = Screens.showNormalScreen('header');
	}
	
	Screens.initInformation();
};

CApp.prototype.route = function (sHash, aParams)
{
	if (this.oHeaderScreen)
	{
		this.oHeaderScreen.onRoute(sHash, aParams);
	}
};

CApp.prototype.getModulesTabs = function ()
{
	var
		aTabs = []
	;
	
	_.each(this.oModules, function (oModule, sName) {
		oModule.HeaderItem.setName(sName);
		aTabs.push(oModule.HeaderItem);
	});
	
	return aTabs;
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
	return true;
};

CApp.prototype.isModuleIncluded = function (sName)
{
	return this.oModules[sName] !== undefined;
};

var App = new CApp();

module.exports = App;