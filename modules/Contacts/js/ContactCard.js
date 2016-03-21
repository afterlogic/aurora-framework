'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	CustomTooltip = require('core/js/CustomTooltip.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	Screens = require('core/js/Screens.js'),
	
	ComposeMessageToAddressesFunc = ModulesManager.run('Mail', 'getComposeMessageToAddresses'),
	SearchMessagesInCurrentFolderFunc = ModulesManager.run('Mail', 'getSearchMessagesInCurrentFolder'),
	
	Popups = require('core/js/Popups.js'),
	CreateContactPopup = require('modules/Contacts/js/popups/CreateContactPopup.js'),
	
	ContactsCache = require('modules/Contacts/js/Cache.js'),
	
	oContactCardsView = {
		contacts: ko.observableArray([]),
		ViewTemplate: 'Contacts_ContactCardsView',
		bAllowComposeMessageToAddresses: $.isFunction(ComposeMessageToAddressesFunc),
		searchMessagesInCurrentFolder: SearchMessagesInCurrentFolderFunc,
		bAllowSearchMessagesInCurrentFolder: $.isFunction(SearchMessagesInCurrentFolderFunc),
		add: function (aContacts) {
			var aDiffContacts = _.filter(this.contacts(), function (oContact) {
				return -1 === $.inArray(oContact.email(), _.keys(aContacts));
			});
			this.contacts(aDiffContacts.concat(_.compact(_.values(aContacts))));
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

function ClearElement($Element)
{
	if ($Element.next().hasClass('add_contact'))
	{
		$Element.next().remove();
	}
	$Element.removeClass('found');
	$Element.parent().removeClass('found_contact');
	$Element.off();
}

/**
 * @param {Array} aElements
 * @param {Array} aContacts
 */
function OnContactResponse(aElements, aContacts)
{
	_.each(aElements, function ($Element) {
		var
			sEmail = $Element.attr('data-email'), // $Element.data('email') returns wrong values if data-email was changed by knockoutjs
			oContact = aContacts[sEmail]
		;
		
		if (oContact !== undefined)
		{
			ClearElement($Element);
			
			if (oContact === null)
			{
				var $add = $('<span class="add_contact"></span>');
				$Element.after($add);
				CustomTooltip.init($add, 'CONTACTS/ACTION_ADD_TO_CONTACTS');
				$add.on('click', function () {
					Popups.showPopup(CreateContactPopup, [$Element.attr('data-name'), sEmail, function (aContacts) {
						_.each(aElements, function ($El) {
							if ($El.attr('data-email') === sEmail)
							{
								ClearElement($El);
								$El.addClass('found');
								$El.parent().addClass('found_contact');
								oContactCardsView.add(aContacts);
								BindContactCard($El, sEmail);
							}
						});
					}]);
				});
			}
			else
			{
				$Element.addClass('found');
				$Element.parent().addClass('found_contact');
				oContactCardsView.add(aContacts);
				BindContactCard($Element, sEmail);
			}
		}
	});
}

module.exports = {
	applyTo: function ($Addresses) {
		var
			aElements = _.map($Addresses, function (oElement) {
				return $(oElement);
			}),
			aEmails = _.uniq(_.map(aElements, function ($Element) {
				return $Element && $Element.attr('data-email');
			}))
		;

		ContactsCache.getContactsByEmails(aEmails, _.bind(OnContactResponse, {}, aElements));
	}
};
