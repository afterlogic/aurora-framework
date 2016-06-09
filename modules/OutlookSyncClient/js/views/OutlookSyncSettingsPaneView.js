'use strict';

var
	ko = require('knockout'),
	
	TextUtils = require('modules/CoreClient/js/utils/Text.js'),
	
	Ajax = require('modules/CoreClient/js/Ajax.js'),
	Api = require('modules/CoreClient/js/Api.js'),
	App = require('modules/CoreClient/js/App.js'),
	ModulesManager = require('modules/CoreClient/js/ModulesManager.js'),
	UserSettings = require('modules/CoreClient/js/Settings.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * @constructor
 */
function COutlookSyncSettingsPaneView()
{
	this.oResetPasswordViewModel = ModulesManager.run('ChangePasswordClient', 'getResetPasswordView');
	
	this.server = ko.observable('');
	
	this.bDemo = UserSettings.IsDemo;

	this.sPlugin32DownloadLink = Settings.Plugin32DownloadLink;
	this.sPlugin64DownloadLink = Settings.Plugin64DownloadLink;
	this.sPluginReadMoreLink = Settings.PluginReadMoreLink;

	this.credentialsHintText = ko.observable(TextUtils.i18n('CORE/INFO_MOBILE_CREDENTIALS', {'EMAIL': App.defaultAccountEmail()}));
}

COutlookSyncSettingsPaneView.prototype.ViewTemplate = '%ModuleName%_OutlookSyncSettingsPaneView';

COutlookSyncSettingsPaneView.prototype.onRoute = function ()
{
	Ajax.send(Settings.ServerModuleName, 'GetInfo', this.onGetInfoResponse, this);
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
