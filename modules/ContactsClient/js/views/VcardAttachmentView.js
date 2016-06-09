'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	Types = require('modules/CoreClient/js/utils/Types.js'),
	
	ContactsCache = require('modules/%ModuleName%/js/Cache.js'),
	CVcardModel = require('modules/%ModuleName%/js/models/VcardModel.js')
;

function CVcardAttachmentView()
{
	this.vcard = ko.observable(null);
}

CVcardAttachmentView.prototype.ViewTemplate = '%ModuleName%_VcardAttachmentView';

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
		aExtend = (oMessageProps && Types.isNonEmptyArray(oMessageProps.aExtend)) ? oMessageProps.aExtend : [],
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
