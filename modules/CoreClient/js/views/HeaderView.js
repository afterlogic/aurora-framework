'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Types = require('modules/CoreClient/js/utils/Types.js'),
	
	Ajax = require('modules/CoreClient/js/Ajax.js'),
	App = require('modules/CoreClient/js/App.js'),
	Browser = require('modules/CoreClient/js/Browser.js'),
	ModulesManager = require('modules/CoreClient/js/ModulesManager.js'),
	Routing = require('modules/CoreClient/js/Routing.js'),
	Settings = require('modules/CoreClient/js/Settings.js'),
	Screens = require('modules/CoreClient/js/Screens.js'),
	
	CAbstractScreenView = require('modules/CoreClient/js/views/CAbstractScreenView.js')
;

/**
 * @constructor
 */
function CHeaderView()
{
	CAbstractScreenView.call(this);
	
	this.tabs = ModulesManager.getModulesTabs(false);
	
	ko.computed(function () {
		_.each(this.tabs, function (oTab) {
			if (oTab.isCurrent)
			{
				oTab.isCurrent(Screens.currentScreen() === oTab.sName);
				if (oTab.isCurrent() && Types.isNonEmptyString(Routing.currentHash()))
				{
					oTab.hash('#' + Routing.currentHash());
				}
			}
		});
	}, this).extend({ rateLimit: 50 });
	
	this.showLogout = App.getUserRole() !== Enums.UserRole.Anonymous && !App.isPublic();

	this.sLogoUrl = Settings.LogoUrl;
	
	this.mobileDevice = Browser.mobileDevice;
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
//	}
}

_.extendOwn(CHeaderView.prototype, CAbstractScreenView.prototype);

CHeaderView.prototype.ViewTemplate = App.isMobile() ? 'CoreClient_HeaderMobileView' : 'CoreClient_HeaderView';
CHeaderView.prototype.__name = 'CHeaderView';

CHeaderView.prototype.logout = function ()
{
	App.logout();
};

CHeaderView.prototype.switchToFullVersion = function ()
{
	Ajax.send('Core', 'SetMobile', {'Mobile': 0}, function (oResponse) {
		if (oResponse.Result)
		{
			window.location.reload();
		}
	}, this);
};

module.exports = new CHeaderView();
