'use strict';

var
	ko = require('knockout'),
			
	TextUtils = require('modules/CoreClient/js/utils/Text.js')
;

function CMessageControlView()
{
	this.sensitivityText = ko.observable('');
	
	this.visible = ko.observable(false);
}

CMessageControlView.prototype.ViewTemplate = '%ModuleName%_MessageControlView';

/**
 * Receives properties of the message that is displaying in the message pane. 
 * It is called every time the message is changing in the message pane.
 * Receives null if there is no message in the pane.
 * 
 * @param {Object|null} oMessageProps Information about message in message pane.
 * @param {number} oMessageProps.iSensitivity
 */
CMessageControlView.prototype.doAfterPopulatingMessage = function (oMessageProps)
{
	if (!oMessageProps || oMessageProps.iSensitivity === Enums.Sensitivity.Nothing)
	{
		this.visible(false);
	}
	else
	{
		switch (oMessageProps.iSensitivity)
		{
			case Enums.Sensitivity.Confidential:
				this.sensitivityText(TextUtils.i18n('%MODULENAME%/INFO_CONFIDENTIAL'));
				break;
			case Enums.Sensitivity.Personal:
				this.sensitivityText(TextUtils.i18n('%MODULENAME%/INFO_PERSONAL'));
				break;
			case Enums.Sensitivity.Private:
				this.sensitivityText(TextUtils.i18n('%MODULENAME%/INFO_PRIVATE'));
				break;
		}
		this.visible(true);
	}
};

module.exports = new CMessageControlView();
