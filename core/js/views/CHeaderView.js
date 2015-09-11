'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	App = require('core/js/App.js'),
	Settings = require('core/js/Settings.js')
;

/**
 * @constructor
 */
function CHeaderView()
{
//	var self = this;
//	this.mobileApp = bMobileApp;
//	this.mobileDevice = AppData.AllowMobile && bMobileDevice;
//
//	this.allowWebMail = AppData.App.AllowWebMail;
//	this.currentAccountId = AppData.Accounts.currentId;
//	this.currentAccountId.subscribe(function () {
//		_.delay(function () {
//			self.changeMailLinkText();
//		}, 300);
//	}, this);
//	
	this.tabs = App.getModulesTabs();
//
//	this.mailLinkText = ko.observable('');
//
//	this.accounts = ko.computed(function () {
//		if (AppData.Accounts.collection().length === 1)
//		{
//			return AppData.Accounts.collection();
//		}
//		else
//		{
//			return _.filter(AppData.Accounts.collection(), function (oAccount) {
//				return oAccount.allowMail();
//			});
//		}
//	}, this);
	
//	this.mailboxHash = App.Routing.lastMailboxHash;
//	this.settingsHash = App.Routing.lastSettingsHash;
//	
//	this.contactsRecivedAnim = App.ContactsCache.recivedAnim;
//	this.calendarRecivedAnim = App.CalendarCache.recivedAnim;

	this.appCustomLogo = Settings.CustomLogo;
	
//	if (AfterLogicApi.runPluginHook)
//	{
//		AfterLogicApi.runPluginHook('view-model-defined', [this.__name, this]);
//	}
}

CHeaderView.prototype.__name = 'CHeaderView';

CHeaderView.prototype.onRoute = function (sHash, aParams)
{
	var sScreen = aParams[0] || '';
	
	_.each(this.tabs, function (oTab) {
		oTab.isCurrent(sScreen === oTab.sName);
		if (oTab.isCurrent())
		{
			oTab.hash(sHash);
		}
	});
};

CHeaderView.prototype.changeMailLinkText = function ()
{
	var oCurrAccount = AppData.Accounts.getCurrent();
	if (oCurrAccount.allowMail())
	{
		this.mailLinkText(AppData.Accounts.getEmail());
	}
	else
	{
		this.mailLinkText(Utils.i18n('TITLE/MAILBOX_TAB'));
	}
};

CHeaderView.prototype.logout = function ()
{
	App.logout();
};

CHeaderView.prototype.switchToFullVersion = function ()
{
	App.Ajax.send({
		'Action': 'SystemSetMobile',
		'Mobile': 0
	}, function () {
		window.location.reload();
	}, this);
};

module.exports = CHeaderView;