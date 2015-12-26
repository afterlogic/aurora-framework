'use strict';

var
	_ = require('underscore'),
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
 */
function CAbstractSettingsTabView()
{
	this.isSaving = ko.observable(false);
}

CAbstractSettingsTabView.prototype.ViewTemplate = ''; // should be overriden

CAbstractSettingsTabView.prototype.show = function ()
{
	this.revert();
};

/**
 * @param {Function} fAfterHideHandler
 * @param {Function} fRevertRouting
 */
CAbstractSettingsTabView.prototype.hide = function (fAfterHideHandler, fRevertRouting)
{
	if (this.getCurrentState() !== this.sSavedState) // if values have been changed
	{
		Popups.showPopup(ConfirmPopup, [TextUtils.i18n('COMPOSE/CONFIRM_DISCARD_CHANGES'), _.bind(function (bDiscard) {
			if (bDiscard)
			{
				fAfterHideHandler();
				this.revert();
			}
			else
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
CAbstractSettingsTabView.prototype.getCurrentValues = function ()
{
	return [];
};

/**
 * @returns {String}
 */
CAbstractSettingsTabView.prototype.getCurrentState = function ()
{
	var aState = this.getCurrentValues();
	
	return aState.join(':');
};

CAbstractSettingsTabView.prototype.updateSavedState = function()
{
	this.sSavedState = this.getCurrentState();
};

/**
 * Puts values from the global settings object to the editable fields.
 * 
 * Should be overriden.
 */
CAbstractSettingsTabView.prototype.revertGlobalValues = function ()
{
	
};

CAbstractSettingsTabView.prototype.revert = function ()
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
CAbstractSettingsTabView.prototype.getParametersForSave = function ()
{
	return {};
};

/**
 * Sends a request to the server to save the settings.
 */
CAbstractSettingsTabView.prototype.save = function ()
{
	this.isSaving(true);
	
	this.updateSavedState();
	
	Ajax.send('Settings', 'UpdateSettings', this.getParametersForSave(), this.onResponse, this);
};

/**
 * Applies saved values of settings to the global settings object.
 * 
 * Should be overriden.
 * 
 * @param {Object} oParameters Object that have been obtained by getParameters function.
 */
CAbstractSettingsTabView.prototype.applySavedValues = function (oParameters)
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
CAbstractSettingsTabView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (oResponse.Result === false)
	{
		Api.showErrorByCode(oResponse, TextUtils.i18n('SETTINGS/ERROR_SETTINGS_SAVING_FAILED'));
	}
	else
	{
		var oParameters = JSON.parse(oRequest.Parameters);
		
		this.updateEditableValues(oParameters);
		
		Screens.showReport(TextUtils.i18n('SETTINGS/COMMON_REPORT_UPDATED_SUCCESSFULLY'));
	}
};

module.exports = CAbstractSettingsTabView;
