'use strict';

var
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	Ajax = require('core/js/Ajax.js'),
	Api = require('core/js/Api.js'),
	App = require('core/js/App.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	UserSettings = require('core/js/Settings.js'),
	
	Settings = require('modules/OutlookSync/js/Settings.js')
;

/**
 * @constructor
 */
function COutlookSyncSettingsPaneView()
{
	this.oResetPasswordViewModel = ModulesManager.run('ChangePassword', 'getResetPasswordView');
	
	this.server = ko.observable('');
	
	this.bDemo = UserSettings.IsDemo;

	this.sPlugin32DownloadLink = Settings.Plugin32DownloadLink;
	this.sPlugin64DownloadLink = Settings.Plugin64DownloadLink;
	this.sPluginReadMoreLink = Settings.PluginReadMoreLink;

	this.credentialsHintText = ko.observable(TextUtils.i18n('SETTINGS/MOBILE_CREDENTIALS_TITLE', {'EMAIL': App.defaultAccountEmail()}));
}

COutlookSyncSettingsPaneView.prototype.ViewTemplate = 'OutlookSync_OutlookSyncSettingsPaneView';

COutlookSyncSettingsPaneView.prototype.onRoute = function ()
{
	Ajax.send('OutlookSync', 'GetInfo', this.onGetInfoResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
COutlookSyncSettingsPaneView.prototype.onGetInfoResponse = function (oResponse, oRequest)
{
	var oResult = oResponse.Result;
	
	if (!oResult)
	{
		Api.showErrorByCode(oResponse);
	}
	else
	{
		this.server(oResult.Server);
	}
};

module.exports = new COutlookSyncSettingsPaneView();
