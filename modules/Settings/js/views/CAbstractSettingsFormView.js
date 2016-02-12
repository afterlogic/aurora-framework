'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	TextUtils = require('core/js/utils/Text.js'),
	
	Ajax = require('core/js/Ajax.js'),
	Api = require('core/js/Api.js'),
	Screens = require('core/js/Screens.js'),
	
	Popups = require('core/js/Popups.js'),
	ConfirmPopup = require('core/js/popups/ConfirmPopup.js')
;

/**
 * @constructor
 * @param {string} sModule
 */
function CAbstractSettingsFormView(sModule)
{
	this.sModule = sModule ? sModule : 'Core';
	
	this.isSaving = ko.observable(false);
}

CAbstractSettingsFormView.prototype.ViewTemplate = ''; // should be overriden

CAbstractSettingsFormView.prototype.onRoute = function ()
{
	this.revert();
};

/**
 * @param {Function} fAfterHideHandler
 * @param {Function} fRevertRouting
 */
CAbstractSettingsFormView.prototype.hide = function (fAfterHideHandler, fRevertRouting)
{
	if (this.getCurrentState() !== this.sSavedState) // if values have been changed
	{
		Popups.showPopup(ConfirmPopup, [TextUtils.i18n('COMPOSE/CONFIRM_DISCARD_CHANGES'), _.bind(function (bDiscard) {
			if (bDiscard)
			{
				fAfterHideHandler();
				this.revert();
			}
			else if ($.isFunction(fRevertRouting))
			{
				fRevertRouting();
			}
		}, this)]);
	}
	else
	{
		fAfterHideHandler();
	}
};

/**
 * Returns an array with the values of editable fields.
 * 
 * Should be overriden.
 * 
 * @returns {Array}
 */
CAbstractSettingsFormView.prototype.getCurrentValues = function ()
{
	return [];
};

/**
 * @returns {String}
 */
CAbstractSettingsFormView.prototype.getCurrentState = function ()
{
	var aState = this.getCurrentValues();
	
	return aState.join(':');
};

CAbstractSettingsFormView.prototype.updateSavedState = function()
{
	this.sSavedState = this.getCurrentState();
};

/**
 * Puts values from the global settings object to the editable fields.
 * 
 * Should be overriden.
 */
CAbstractSettingsFormView.prototype.revertGlobalValues = function ()
{
	
};

CAbstractSettingsFormView.prototype.revert = function ()
{
	this.revertGlobalValues();
	
	this.updateSavedState();
};

/**
 * Gets values from the editable fields and prepares object for passing to the server and saving settings therein.
 * 
 * Should be overriden.
 * 
 * @returns {Object}
 */
CAbstractSettingsFormView.prototype.getParametersForSave = function ()
{
	return {};
};

/**
 * Sends a request to the server to save the settings.
 */
CAbstractSettingsFormView.prototype.save = function ()
{
	this.isSaving(true);
	
	this.updateSavedState();
	
	Ajax.send(this.sModule, 'UpdateSettings', this.getParametersForSave(), this.onResponse, this);
};

/**
 * Applies saved values of settings to the global settings object.
 * 
 * Should be overriden.
 * 
 * @param {Object} oParameters Object that have been obtained by getParameters function.
 */
CAbstractSettingsFormView.prototype.applySavedValues = function (oParameters)
{
	
};

/**
 * Parses the response from the server.
 * If the settings are normally stored, then updates them in the global settings object. 
 * Otherwise shows an error message.
 * 
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAbstractSettingsFormView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (oResponse.Result === false)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('CORE/ERROR_SAVING_SETTINGS_FAILED'));
	}
	else
	{
		var oParameters = JSON.parse(oRequest.Parameters);
		
		this.updateEditableValues(oParameters);
		
		Screens.showReport(TextUtils.i18n('MAIL/REPORT_SETTINGS_UPDATE_SUCCESS'));
	}
};

module.exports = CAbstractSettingsFormView;
