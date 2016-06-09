'use strict';

var
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
	
	Ajax = require('modules/CoreClient/js/Ajax.js'),
	Api = require('modules/CoreClient/js/Api.js'),
	App = require('modules/CoreClient/js/App.js'),
	Browser = require('modules/CoreClient/js/Browser.js'),
	ModulesManager = require('modules/CoreClient/js/ModulesManager.js'),
	UserSettings = require('modules/CoreClient/js/Settings.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * @constructor
 */
function CMobileSyncSettingsPaneView()
{
	
	this.oFilesMobileSyncSettingsView = ModulesManager.run('FilesClient', 'getMobileSyncSettingsView');
	this.oCalendarMobileSyncSettingsView = ModulesManager.run('CalendarClient', 'getMobileSyncSettingsView');
	this.oContactsMobileSyncSettingsView = ModulesManager.run('ContactsClient', 'getMobileSyncSettingsView');
	
	this.oResetPasswordViewModel = ModulesManager.run('ChangePasswordClient', 'getResetPasswordView');
	
	this.enableDav = ko.observable(false);
	
	this.davServer = ko.observable('');
	
	this.bIosDevice = Browser.iosDevice;
	this.bDemo = UserSettings.IsDemo;
	
	this.visibleDavViaUrls = ko.computed(function () {
		return !!this.oCalendarMobileSyncSettingsView && this.oCalendarMobileSyncSettingsView.visible() || !!this.oContactsMobileSyncSettingsView;
	}, this);

	this.credentialsHintText = ko.observable(TextUtils.i18n('CORE/INFO_MOBILE_CREDENTIALS', {'EMAIL': App.defaultAccountEmail()}));
}

CMobileSyncSettingsPaneView.prototype.ViewTemplate = '%ModuleName%_MobileSyncSettingsPaneView';

CMobileSyncSettingsPaneView.prototype.onRoute = function ()
{
	Ajax.send(Settings.ServerModuleName, 'GetInfo', this.onGetInfoResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CMobileSyncSettingsPaneView.prototype.onGetInfoResponse = function (oResponse, oRequest)
{
	var
		oResult = oResponse.Result,
		oDav = !!oResult.EnableDav ? oResult.Dav : null
	;
	
	if (!oResult)
	{
		Api.showErrorByCode(oResponse);
	}
	else
	{
		this.enableDav(!!oResult.EnableDav);

		if (this.enableDav() && oDav)
		{
			this.davServer(oDav.Server);
			if (this.oFilesMobileSyncSettingsView && $.isFunction(this.oFilesMobileSyncSettingsView.populate))
			{
				this.oFilesMobileSyncSettingsView.populate(oDav);
			}
			if (this.oCalendarMobileSyncSettingsView && $.isFunction(this.oCalendarMobileSyncSettingsView.populate))
			{
				this.oCalendarMobileSyncSettingsView.populate(oDav);
			}
			if (this.oContactsMobileSyncSettingsView && $.isFunction(this.oContactsMobileSyncSettingsView.populate))
			{
				this.oContactsMobileSyncSettingsView.populate(oDav);
			}
		}
	}
};

module.exports = new CMobileSyncSettingsPaneView();
