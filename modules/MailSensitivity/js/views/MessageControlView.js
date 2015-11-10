'use strict';

var
	ko = require('knockout'),
			
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js')
;

function CMessageControlView()
{
	this.sensitivityText = ko.observable('');
	
	this.visible = ko.observable(false);
}

CMessageControlView.prototype.ViewTemplate = 'MailSensitivity_MessageControlView';

/**
 * Receives properties of the message that is displaying in the message pane. 
 * It is called every time the message is changing in the message pane.
 * Receives null if there is no message in the pane.
 * 
 * @param {Object|null} oMessageProps Information about message in message pane.
 * @param {Object} oMessageProps.oCustom
 */
CMessageControlView.prototype.doAfterPopulatingMessage = function (oMessageProps)
{
	var iSensitivity = Utils.pInt(oMessageProps && oMessageProps.oCustom && oMessageProps.oCustom.Sensitivity);
	
	if (!Enums.has('Sensitivity', iSensitivity))
	{
		iSensitivity = Enums.Sensitivity.Nothing;
	}
	
	if (iSensitivity === Enums.Sensitivity.Nothing)
	{
		this.visible(false);
	}
	else
	{
		switch (iSensitivity)
		{
			case Enums.Sensitivity.Confidential:
				this.sensitivityText(TextUtils.i18n('MESSAGE/SENSITIVITY_CONFIDENTIAL'));
				break;
			case Enums.Sensitivity.Personal:
				this.sensitivityText(TextUtils.i18n('MESSAGE/SENSITIVITY_PERSONAL'));
				break;
			case Enums.Sensitivity.Private:
				this.sensitivityText(TextUtils.i18n('MESSAGE/SENSITIVITY_PRIVATE'));
				break;
		}
		this.visible(true);
	}
};

module.exports = new CMessageControlView();