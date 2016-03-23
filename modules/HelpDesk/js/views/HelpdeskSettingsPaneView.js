'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	ModulesManager = require('modules/Core/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('Settings', 'getAbstractSettingsFormViewClass'),
	
	Settings = require('modules/HelpDesk/js/Settings.js')
;

/**
 * @constructor
 */
function CHelpdeskSettingsPaneView()
{
	CAbstractSettingsFormView.call(this, 'Helpdesk');

	this.allowNotifications = ko.observable(Settings.AllowEmailNotifications);
	this.signature = ko.observable(Settings.Signature);
	this.signatureEnable = ko.observable(Settings.useSignature() ? '1' : '0');
	
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

_.extendOwn(CHelpdeskSettingsPaneView.prototype, CAbstractSettingsFormView.prototype);

CHelpdeskSettingsPaneView.prototype.ViewTemplate = 'HelpDesk_HelpdeskSettingsPaneView';

CHelpdeskSettingsPaneView.prototype.getCurrentValues = function ()
{
	return [
		this.allowNotifications(),
		this.signature(),
		this.signatureEnable()
	];
};

CHelpdeskSettingsPaneView.prototype.revertGlobalValues = function ()
{
	this.allowNotifications(Settings.AllowEmailNotifications);
	this.signature(Settings.signature());
	this.signatureEnable(Settings.useSignature() ? '1' : '0');
};

CHelpdeskSettingsPaneView.prototype.getParametersForSave = function ()
{
	return {
		'AllowEmailNotifications': this.allowNotifications() ? '1' : '0',
		'HelpdeskSignature': this.signature(),
		'HelpdeskSignatureEnable': this.signatureEnable()
	};
};

CHelpdeskSettingsPaneView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.AllowEmailNotifications, oParameters.HelpdeskSignature, oParameters.HelpdeskSignatureEnable);
};

module.exports = new CHelpdeskSettingsPaneView();
