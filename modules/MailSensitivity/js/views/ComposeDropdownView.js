'use strict';

var
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js')
;

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
CComposeDropdownView.prototype.doAfterApplyingBaseTabParameters = function (oParameters)
{
	this.selectedSensitivity(oParameters.Sensitivity);
};

/**
 * @param {Object} oParameters
 */
CComposeDropdownView.prototype.doAfterComposingBaseTabParameters = function (oParameters)
{
	oParameters.Sensitivity = this.selectedSensitivity();
};

/**
 * @param {Object} oParameters
 */
CComposeDropdownView.prototype.doAfterPopulatingMessage = function (oParameters)
{
	var iSensitivity = Utils.pInt(oParameters.oCustom && oParameters.oCustom.Sensitivity);
	
	if (!Enums.has('Sensitivity', iSensitivity))
	{
		iSensitivity = Enums.Sensitivity.Nothing;
	}
	
	this.selectedSensitivity(oParameters.oCustom.Sensitivity);
};

/**
 * @param {Object} oParameters
 */
CComposeDropdownView.prototype.doAfterComposingSendMessageParameters = function (oParameters)
{
	oParameters.Sensitivity = this.selectedSensitivity();
};

module.exports = new CComposeDropdownView();
