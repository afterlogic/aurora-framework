'use strict';

var ko = require('knockout');

/**
 * @constructor for object that display Sensitivity button on Compose
 */
function CComposeDropdownView()
{
	this.sId = 'MailSensitivity';
	this.selectedSensitivity = ko.observable(Enums.Sensitivity.Nothing);
}

CComposeDropdownView.prototype.ViewTemplate = 'MailSensitivity_ComposeDropdownView';

/**
 * @param {Object} oParameters
 */
CComposeDropdownView.prototype.doAfterApplyingMainTabParameters = function (oParameters)
{
	this.selectedSensitivity(oParameters.Sensitivity);
};

/**
 * @param {Object} oParameters
 */
CComposeDropdownView.prototype.doAfterPreparingMainTabParameters = function (oParameters)
{
	oParameters.Sensitivity = this.selectedSensitivity();
};

/**
 * @param {Object} oParameters
 */
CComposeDropdownView.prototype.doAfterPopulatingMessage = function (oParameters)
{
	this.selectedSensitivity(oParameters.iSensitivity);
};

/**
 * @param {Object} oParameters
 */
CComposeDropdownView.prototype.doAfterPreparingSendMessageParameters = function (oParameters)
{
	oParameters.Sensitivity = this.selectedSensitivity();
};

module.exports = new CComposeDropdownView();
