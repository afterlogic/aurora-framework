'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Types = require('modules/CoreClient/js/utils/Types.js'),
	
	App = require('modules/CoreClient/js/App.js'),
	
	CalendarCache = require('modules/%ModuleName%/js/Cache.js'),
	CIcalModel = require('modules/%ModuleName%/js/models/CIcalModel.js')
;

function CIcalAttachmentView()
{
	this.ical = ko.observable(null);
}

CIcalAttachmentView.prototype.ViewTemplate = '%ModuleName%_IcalAttachmentView';

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
	var
		aExtend = (oMessageProps && Types.isNonEmptyArray(oMessageProps.aExtend)) ? oMessageProps.aExtend : [],
		oFoundRawIcal = _.find(aExtend, function (oRawIcal) {
			return oRawIcal['@Object'] === 'Object/CApiMailIcs';
		})
	;
	
	if (oFoundRawIcal)
	{
		var
			sAttendee = App.getAttendee(oMessageProps.aToEmails),
			oIcal = CalendarCache.getIcal(oFoundRawIcal.File)
		;
		
		if (!oIcal)
		{
			oIcal = new CIcalModel(oFoundRawIcal, sAttendee);
		}
		
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
