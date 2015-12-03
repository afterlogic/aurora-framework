'use strict';

var
	_ = require('underscore'),
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
 * @param {String} oMessageProps.sFromEmail Message sender email.
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
		
		// animation of buttons turns on with delay
		// so it does not trigger when placing initial values
		oIcal.animation(false);
		_.defer(_.bind(function () {
			if (oIcal !== null)
			{
				oIcal.animation(true);
			}
		}, this));
		
		oIcal.updateAttendeeStatus(oMessageProps.sFromEmail);
		
		this.ical(oIcal);
	}
	else
	{
		this.ical(null);
	}
};

module.exports = new CIcalAttachmentView();