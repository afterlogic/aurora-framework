'use strict';

var
	$ = require('jquery'),
	ko = require('knockout'),
	_ = require('underscore'),
	
	Utils = require('core/js/utils/Common.js'),
	customTooltip = require('core/js/customTooltip.js'),
	Screens = require('core/js/Screens.js'),
	
	Popups = require('core/js/Popups.js'),
	CreateContactPopup = require('modules/Contacts/js/popups/CreateContactPopup.js'),
	
	ContactsCache = require('modules/Contacts/js/Cache.js'),
	CContactModel = require('modules/Contacts/js/models/CContactModel.js'),
	
	aWaitElements = [],
	oContactCardsView = {
		contacts: ko.observableArray([]),
		ViewTemplate: 'Contacts_ContactCardsView',
		add: function (aContacts) {
			this.contacts(_.uniq(this.contacts().concat(_.values(aContacts))));
		}
	}
;

Screens.showAnyView(oContactCardsView);

/**
 * @param {Object} $Element
 * @param {String} sAddress
 */
function BindContactCard($Element, sAddress)
{
	var
		$Popup = $('div.item_viewer[data-email=\'' + sAddress + '\']'),
		bPopupOpened = false,
		iCloseTimeoutId = 0,
		fOpenPopup = function () {
			if ($Popup && $Element)
			{
				bPopupOpened = true;
				clearTimeout(iCloseTimeoutId);
				setTimeout(function () {
					var
						oOffset = $Element.offset(),
						iLeft, iTop, iFitToScreenOffset
					;
					if (bPopupOpened && oOffset.left + oOffset.top !== 0)
					{
						iLeft = oOffset.left + 10;
						iTop = oOffset.top + $Element.height() + 6;
						iFitToScreenOffset = $(window).width() - (iLeft + 396); //396 - popup outer width

						if (iFitToScreenOffset > 0)
						{
							iFitToScreenOffset = 0;
						}
						$Popup.addClass('expand').offset({'top': iTop, 'left': iLeft + iFitToScreenOffset});
					}
				}, 180);
			}
		},
		fClosePopup = function () {
			if (bPopupOpened && $Popup && $Element)
			{
				bPopupOpened = false;
				iCloseTimeoutId = setTimeout(function () {
					if (!bPopupOpened)
					{
						$Popup.removeClass('expand');
					}
				}, 200);
			}
		}
	;

	if ($Popup.length > 0)
	{
		$Element
			.off()
			.on('mouseover', function () {
				$Popup
					.off()
					.on('mouseenter', fOpenPopup)
					.on('mouseleave', fClosePopup)
					.find('.link, .button')
					.off('.links')
					.on('click.links', function () {
						bPopupOpened = false;
						$Popup.removeClass('expand');
					})
				;

				setTimeout(function () {
					$Popup
						.find('.link, .button')
						.off('click.links')
						.on('click.links', function () {
							bPopupOpened = false;
							$Popup.removeClass('expand');
						});
				}.bind(this), 100);

				fOpenPopup();
			})
			.on('mouseout', fClosePopup)
		;

		bPopupOpened = false;
		$Popup.removeClass('expand');
	}
	else
	{
		$Element.off();
	}
}

/**
 * @param {Array} aContacts
 */
function OnContactResponse(aContacts)
{
	console.log('aWaitElements', aWaitElements);
	_.each(aWaitElements, function ($Element, iIndex) {
		// поиск по ключам, потому что значение может быть null - underscore его игнорирует.
		var sFoundEmail = _.find(_.keys(aContacts), function (sEmail) {
			return sEmail === $Element.data('email');
		});
		
		if (Utils.isNonEmptyString(sFoundEmail))
		{
			if (aContacts[sFoundEmail] === null)
			{
				if (!$Element.next().hasClass('add_contact'))
				{
					var $add = $('<span class="add_contact"></span>');
					$Element.after($add);
					customTooltip.init($add, 'MESSAGE/ACTION_ADD_TO_CONTACTS');
					$add.on('click', function () {
						Popups.showPopup(CreateContactPopup, [$Element.data('name'), sFoundEmail, function (aContacts) {
							$add.remove();
							oContactCardsView.add(aContacts);
							BindContactCard($Element, sFoundEmail);
						}]);
					});
				}
			}
			else
			{
				$Element.addClass('link found');
				oContactCardsView.add(aContacts);
				BindContactCard($Element, sFoundEmail);
			}
			aWaitElements[iIndex] = undefined;
		}
//		_.each(_.keys(aContacts), function (sEmail) {
//	console.log('$Element', $Element);
//	console.log('iIndex', iIndex);
//			if ($Element && sEmail === $Element.data('email'))
//			{
//			}
//		});
	});
	
	aWaitElements = _.compact(aWaitElements);
}

module.exports = {
	doAfterPopulatingMessage: function (oMessageProps) {
		if (oMessageProps)
		{
			var
				aRecipients = oMessageProps.a$recipients,
				aEmails = _.uniq(_.map(aRecipients, function ($Element) {
					return $Element.data('email');
				}))
			;
			
			aWaitElements = _.filter(_.uniq(aWaitElements.concat(aRecipients)), function ($Element) {
				return $Element.parent().length > 0;
			});
			
			ContactsCache.getContactsByEmails(aEmails, OnContactResponse);
		}
	}
};