'use strict';

var
	ko = require('knockout'),
	
	App = require('core/js/App.js'),
	
	CIcalModel = require('modules/Calendar/js/models/CIcalModel.js')
;

function CIcalAttachmentView()
{
	this.ical = ko.observable(null);
}

CIcalAttachmentView.prototype.ViewTemplate = 'Calendar_IcalAttachmentView';

/**
 * Receives properties of the message that is displaying in the message pane. 
 * It is called every time the message is changing in the message pane.
 * Receives null if there is no message in the pane.
 * 
 * @param {Object|null} oMessageProps Information about message in message pane.
 * @param {Array} oMessageProps.aToEmails
 * @param {Object} oMessageProps.oIcal
 */
CIcalAttachmentView.prototype.doAfterPopulatingMessage = function (oMessageProps)
{
	if (oMessageProps && oMessageProps.oRawIcal)
	{
		var
			sAttendee = App.getAttendee(oMessageProps.aToEmails),
			oIcal = new CIcalModel(oMessageProps.oRawIcal, sAttendee)
		;
		this.ical(oIcal);
	}
	else
	{
		this.ical(null);
	}
};

module.exports = new CIcalAttachmentView();