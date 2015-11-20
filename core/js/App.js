'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	Modernizr = require('modernizr'),
	
	Utils = require('core/js/utils/Common.js'),
	Settings = require('core/js/Settings.js'),
	Screens = require('core/js/Screens.js'),
	Routing = require('core/js/Routing.js'),
	WindowOpener = require('core/js/WindowOpener.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	Browser = require('core/js/Browser.js'),
	Storage = require('core/js/Storage.js'),
	
	bMobileDevice = false
;

require('core/js/splitter.js'); // necessary in mail and contacts modules, not for mobile version
require('core/js/koBindings.js');
require('core/js/koExtendings.js');

if (!bMobileDevice)// && !this.bMobile)
{
	require('core/js/CustomTooltip.js');
	require('core/js/koBindingsNotMobile.js');
}

require('core/js/enums.js');

require('core/js/vendors/inputosaurus.js');

require('jquery.cookie');

function InitModernizr()
{
	if (Modernizr && navigator)
	{
		Modernizr.addTest('pdf', function() {
			return !!_.find(navigator.mimeTypes, function (oMimeType) {
				return 'application/pdf' === oMimeType.type;
			});
		});

		Modernizr.addTest('newtab', function() {
			return App.isNewTab();
		});
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
	this.checkMobile();
	
	if (this.bAuth && !this.bPublic)
	{
		var Accounts = require('modules/Mail/js/AccountList.js');
		this.currentAccountId = Accounts.currentId;
		this.defaultAccountId = Accounts.defaultId;
		this.hasAccountWithId = _.bind(Accounts.hasAccountWithId, Accounts);
		this.currentAccountId.valueHasMutated();
		
		this.currentAccountEmail = ko.computed(function () {
			var oAccount = Accounts.getAccount(this.currentAccountId());
			return oAccount ? oAccount.email() : '';
		}, this);
		
		this.defaultAccountEmail = ko.computed(function () {
			var oAccount = Accounts.getAccount(this.defaultAccountId());
			return oAccount ? oAccount.email() : '';
		}, this);
		this.defaultAccountFriendlyName = ko.computed(function () {
			var oAccount = Accounts.getAccount(this.defaultAccountId());
			return oAccount ? oAccount.friendlyName() : '';
		}, this);
		
		this.getAttendee = function (aAttendees) {
			return Accounts.getAttendee(
				_.map(aAttendees, function (mAttendee) {
					return Utils.isNonEmptyString(mAttendee) ? mAttendee : mAttendee.email;
				}, this)
			);
		};
	}
	
	InitModernizr();
	
	Screens.init();
	Routing.init();
	
	require('core/js/AppTab.js');
	
	Storage.setData('AuthToken', Storage.getData('AuthToken'));
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
	
	if (Utils.isNonEmptyString(Settings.CustomLogoutUrl))
	{
		window.location.href = Settings.CustomLogoutUrl;
	}
	else
	{
		Utils.clearAndReloadLocation(Browser.ie8AndBelow, true);
	}
};

CApp.prototype.checkMobile = function () {
	/**
	 * Settings.IsMobile:
	 *	-1 - first time, mobile is not determined
	 *	0 - mobile is switched off
	 *	1 - mobile is switched on
	 */
	if (Settings.AllowMobile && Settings.IsMobile === -1)
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
	}
};

var App = new CApp();

module.exports = App;