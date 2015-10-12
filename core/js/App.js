'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	Settings = require('core/js/Settings.js'),
	Screens = require('core/js/Screens.js'),
	Routing = require('core/js/Routing.js'),
	WindowOpener = require('core/js/WindowOpener.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
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

require('core/js/vendors/inputosaurus.js');

function CApp()
{
	this.bAuth = window.pSevenAppData.Auth;
	this.bPublic = false;
}

CApp.prototype.init = function (bPublic)
{
	this.bPublic = bPublic;
	
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
				_.map(aAttendees, function (oAttendee) {
					return oAttendee.email;
				}, this)
			);
		};
	}
	
	Screens.init(!this.bAuth && !bPublic);
	Routing.init();
	
	require('core/js/AppTab.js');
};

CApp.prototype.isAuth = function ()
{
	return this.bAuth;
};

CApp.prototype.isPublic = function ()
{
	return this.bPublic;
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

var App = new CApp();

module.exports = App;