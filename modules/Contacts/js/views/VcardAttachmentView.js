'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js'),
	
	ContactsCache = require('modules/Contacts/js/Cache.js'),
	CVcardModel = require('modules/Contacts/js/models/VcardModel.js')
;

function CVcardAttachmentView()
{
	this.vcard = ko.observable(null);
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
	var
		aExtend = (oMessageProps && Utils.isNonEmptyArray(oMessageProps.aExtend)) ? oMessageProps.aExtend : [],
		oFoundRawVcard = _.find(aExtend, function (oRawVcard) {
			return oRawVcard['@Object'] === 'Object/CApiMailVcard';
		})
	;
	if (oFoundRawVcard)
	{
		var oVcard = ContactsCache.getVcard(oFoundRawVcard.File);
		if (!oVcard)
		{
			oVcard = new CVcardModel();
			oVcard.parse(oFoundRawVcard);
		}
		this.vcard(oVcard);
	}
	else
	{
		this.vcard(null);
	}
};

module.exports = new CVcardAttachmentView();