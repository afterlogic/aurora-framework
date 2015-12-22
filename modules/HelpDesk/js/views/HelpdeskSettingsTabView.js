'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	Utils = require('core/js/utils/Common.js'),
	
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsTabView = ModulesManager.run('Settings', 'getAbstractSettingsTabViewClass'),
	
	Ajax = require('modules/HelpDesk/js/Ajax.js'),
	Settings = require('modules/HelpDesk/js/Settings.js')
;

/**
 * @constructor
 */
function CHelpdeskSettingsTabView()
{
	CAbstractSettingsTabView.call(this);

	this.allowNotifications = ko.observable(Settings.AllowHelpdeskNotifications);
	this.signature = ko.observable(Settings.helpdeskSignature());
	this.signatureEnable = ko.observable(Settings.helpdeskSignatureEnable() ? '1' : '0');
	this.signatureEnable.subscribe(function () {
		if (this.signatureEnable() === '1' && !this.signatureFocused())
		{
			this.signatureFocused(true);
		}
	}, this);
	this.signatureFocused = ko.observable(false);
	this.signatureFocused.subscribe(function () {
		if (this.signatureFocused() && this.signatureEnable() !== '1')
		{
			this.signatureEnable('1');
		}
	}, this);
}

_.extendOwn(CHelpdeskSettingsTabView.prototype, CAbstractSettingsTabView.prototype);

CHelpdeskSettingsTabView.prototype.ViewTemplate = 'HelpDesk_HelpdeskSettingsTabView';

CHelpdeskSettingsTabView.prototype.getState = function()
{
	var aState = [
		this.allowNotifications(),
		this.signature(),
		this.signatureEnable()
	];
	
	return aState.join(':');
};

CHelpdeskSettingsTabView.prototype.revert = function()
{
	this.allowNotifications(Settings.AllowHelpdeskNotifications);
	this.signature(Settings.helpdeskSignature());
	this.signatureEnable(Settings.helpdeskSignatureEnable() ? '1' : '0');
	
	this.updateCurrentState();
};

CHelpdeskSettingsTabView.prototype.save = function ()
{
	this.isSaving(true);
	
	this.updateCurrentState();
	
	Ajax.send('UpdateHelpdeskSettings', {
		'AllowHelpdeskNotifications': this.allowNotifications() ? '1' : '0',
		'HelpdeskSignature': this.signature(),
		'HelpdeskSignatureEnable': this.signatureEnable()
	}, this.onResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CHelpdeskSettingsTabView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (oResponse.Result === false)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		var oParameters = JSON.parse(oRequest.Parameters);
		
		Settings.update(oParameters.AllowHelpdeskNotifications, oParameters.HelpdeskSignature, oParameters.HelpdeskSignatureEnable);

		Screens.showReport(Utils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
	}
};

module.exports = new CHelpdeskSettingsTabView();
