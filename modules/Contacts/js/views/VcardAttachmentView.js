'use strict';

var
	ko = require('knockout'),
	
	CVcardModel = require('modules/Contacts/js/models/VcardModel.js')
;

function CVcardAttachmentView()
{
	this.vcard = ko.observable(null);
	this.vcard.subscribe(function () {
		console.log('this.vcard', this.vcard());
	}, this);
}

CVcardAttachmentView.prototype.ViewTemplate = 'Contacts_VcardAttachmentView';

/**
 * Receives properties of the message that is displaying in the message pane. 
 * It is called every time the message is changing in the message pane.
 * Receives null if there is no message in the pane.
 * 
 * @param {Object|null} oMessageProps Information about message in message pane.
 * @param {Object} oMessageProps.oVcard
 */
CVcardAttachmentView.prototype.doAfterPopulatingMessage = function (oMessageProps)
{
	if (oMessageProps && oMessageProps.oRawVcard)
	{
		var oVcard = new CVcardModel();
		oVcard.parse(oMessageProps.oRawVcard);
		this.vcard(oVcard);
	}
	else
	{
		this.vcard(null);
	}
};

module.exports = new CVcardAttachmentView();