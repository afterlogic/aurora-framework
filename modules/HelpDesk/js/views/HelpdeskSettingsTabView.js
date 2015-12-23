'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	ModulesManager = require('core/js/ModulesManager.js'),
	CAbstractSettingsTabView = ModulesManager.run('Settings', 'getAbstractSettingsTabViewClass'),
	
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

CHelpdeskSettingsTabView.prototype.getCurrentValues = function ()
{
	return [
		this.allowNotifications(),
		this.signature(),
		this.signatureEnable()
	];
};

CHelpdeskSettingsTabView.prototype.revertGlobalValues = function ()
{
	this.allowNotifications(Settings.AllowHelpdeskNotifications);
	this.signature(Settings.helpdeskSignature());
	this.signatureEnable(Settings.helpdeskSignatureEnable() ? '1' : '0');
};

CHelpdeskSettingsTabView.prototype.getParametersForSave = function ()
{
	return {
		'AllowHelpdeskNotifications': this.allowNotifications() ? '1' : '0',
		'HelpdeskSignature': this.signature(),
		'HelpdeskSignatureEnable': this.signatureEnable()
	};
};

CHelpdeskSettingsTabView.prototype.applySavedValues = function (oParameters)
{
	Settings.update(oParameters.AllowHelpdeskNotifications, oParameters.HelpdeskSignature, oParameters.HelpdeskSignatureEnable);
};

module.exports = new CHelpdeskSettingsTabView();
