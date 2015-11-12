'use strict';

var
	$ = require('jquery'),
	ko = require('knockout'),
	_ = require('underscore'),
	
	customTooltip = require('core/js/customTooltip.js'),
	
	Popups = require('core/js/Popups.js'),
	CreateContactPopup = require('modules/Contacts/js/popups/CreateContactPopup.js'),
	
	ContactsCache = require('modules/Contacts/js/Cache.js'),
	CContactModel = require('modules/Contacts/js/models/CContactModel.js'),
	
	aWaitElements = []
;

function OnContactResponse(aContacts)
{
	_.each(_.keys(aContacts), function (sEmail) {
		_.each(aWaitElements, function ($element, iIndex) {
			var $add = $('<span class="add_contact"></span>');
			if (sEmail === $element.data('email'))
			{
				if (aContacts[sEmail] === null)
				{
					if (!$element.next().hasClass('add_contact'))
					{
						$element.after($add);
						customTooltip.init($add, 'MESSAGE/ACTION_ADD_TO_CONTACTS');
						$add.on('click', function () {
							Popups.showPopup(CreateContactPopup, [$element.data('name'), sEmail, function () {
									console.log('arguments', arguments);
							}, {}]);
						});
					}
				}
				aWaitElements[iIndex] = undefined;
			}
		});
	});
	aWaitElements = _.compact(aWaitElements);
}

module.exports = {
	doAfterPopulatingMessage: function (oMessageProps) {
		if (oMessageProps)
		{
			var
				aRecipients = oMessageProps.a$recipients,
				aEmails = _.uniq(_.map(aRecipients, function ($element) {
					return $element.data('email');
				}))
			;
			aWaitElements = _.filter(_.uniq(aWaitElements.concat(aRecipients)), function ($Element) {
				return $Element.parent().length > 0;
			});
			
			ContactsCache.getContactsByEmails(aEmails, OnContactResponse);
		}
	}
};