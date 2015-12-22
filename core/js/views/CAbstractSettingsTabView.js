'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Popups = require('core/js/Popups.js'),
	ConfirmPopup = require('core/js/popups/ConfirmPopup.js')
;

/**
 * @constructor
 */
function CAbstractSettingsTabView()
{
	this.isSaving = ko.observable(false);
	this.isSelected = ko.observable(false);
}

CAbstractSettingsTabView.prototype.ViewTemplate = ''; // should be overriden

CAbstractSettingsTabView.prototype.show = function ()
{
	this.revert();
	this.isSelected(true);
};

/**
 * @param {Function} fAfterHideHandler
 * @param {Function} fRevertRouting
 */
CAbstractSettingsTabView.prototype.hide = function (fAfterHideHandler, fRevertRouting)
{
	if (this.isChanged())
	{
		Popups.showPopup(ConfirmPopup, ['Discard unsaved changes?', _.bind(function (bDiscard) {
			if (bDiscard)
			{
				this.isSelected(false);
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
		this.isSelected(false);
		fAfterHideHandler();
	}
};

/**
 * Should be overriden.
 * 
 * @returns {String}
 */
CAbstractSettingsTabView.prototype.getState = function ()
{
	var aState = []; // in overriden function put here all fields that determine tab state
	
	return aState.join(':');
};

CAbstractSettingsTabView.prototype.updateCurrentState = function()
{
	this.sCurrentState = this.getState();
};

/**
 * @returns {Boolean}
 */
CAbstractSettingsTabView.prototype.isChanged = function()
{
	return this.getState() !== this.sCurrentState;
};

/**
 * Should be overriden.
 */
CAbstractSettingsTabView.prototype.revert = function ()
{
	// in overriden function put here reverting of all fields
	
	this.updateCurrentState();
};

/**
 * Sends a request to the server to save the settings.
 * 
 * Should be overriden.
 */
CAbstractSettingsTabView.prototype.save = function ()
{
	this.isSaving(true);
	
	this.updateCurrentState();
	
	// in overriden function put here ajax sending of all fields
};

/**
 * Parses the response from the server. If the settings are normally stored, then updates them. 
 * Otherwise an error message.
 * 
 * Should be overriden.
 * 
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CAbstractSettingsTabView.prototype.onResponse = function (oResponse, oRequest)
{
	this.isSaving(false);

	if (oResponse.Result === false)
	{
		// in overriden function put here error displaying like below
		// Api.showErrorByCode(oResponse, TextUtils.i18n('your error constant name or text'));
	}
	else
	{
		// in overriden function put here settings updating
		// and report displaying like below
		// Screens.showReport(TextUtils.i18n('your report constant name or text'));
	}
};

module.exports = CAbstractSettingsTabView;
