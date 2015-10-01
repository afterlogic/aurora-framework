'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	AddressUtils = require('core/js/utils/Address.js'),
//	Ajax = require('core/js/Ajax.js'),
	CAbstractPopup = require('core/js/popups/CAbstractPopup.js'),
	
	bMobileDevice = false
;

/**
 * @constructor
 */
function CShareCalendarPopup()
{
	CAbstractPopup.call(this);
	
	this.guestsDom = ko.observable();
	this.guestsDom.subscribe(function (a) {
		this.initInputosaurus(this.guestsDom, this.guests, this.guestsLock);
	}, this);
	this.ownersDom = ko.observable();
	this.ownersDom.subscribe(function () {
		this.initInputosaurus(this.ownersDom, this.owners, this.ownersLock);
	}, this);

	this.guestsLock = ko.observable(false);
	this.guests = ko.observable('').extend({'reversible': true});
	this.guests.subscribe(function () {
		if (!this.guestsLock())
		{
			$(this.guestsDom()).val(this.guests());
			$(this.guestsDom()).inputosaurus('refresh');
		}
	}, this);
	this.ownersLock = ko.observable(false);
	this.owners = ko.observable('').extend({'reversible': true});
	this.owners.subscribe(function () {
		if (!this.ownersLock())
		{
			$(this.ownersDom()).val(this.owners());
			$(this.ownersDom()).inputosaurus('refresh');
		}
	}, this);

	this.fCallback = null;

	this.calendarId = ko.observable(null);
	this.selectedColor = ko.observable('');
	this.calendarUrl = ko.observable('');
	this.exportUrl = ko.observable('');
	this.icsLink = ko.observable('');
	this.isPublic = ko.observable(false);
	this.shares = ko.observableArray([]);
	this.owner = ko.observable('');

	this.recivedAnim = ko.observable(false).extend({'autoResetToFalse': 500});
	this.whomAnimate = ko.observable('');

	this.newShare = ko.observable('');
	this.newShareFocus = ko.observable(false);
	this.newShareAccess = ko.observable(Enums.CalendarAccess.Read);
	this.sharedToAll = ko.observable(false);
	this.sharedToAllAccess = ko.observable(Enums.CalendarAccess.Read);
	this.canAdd = ko.observable(false);
	this.aAccess = [
		{'value': Enums.CalendarAccess.Read, 'display': TextUtils.i18n('CALENDAR/CALENDAR_ACCESS_READ')},
		{'value': Enums.CalendarAccess.Write, 'display': TextUtils.i18n('CALENDAR/CALENDAR_ACCESS_WRITE')}
	];

	this.showGlobalContacts = false;//!!AppData.User.ShowGlobalContacts;
}

_.extendOwn(CShareCalendarPopup.prototype, CAbstractPopup.prototype);

CShareCalendarPopup.prototype.PopupTemplate = 'Calendar_ShareCalendarPopup';

/**
 * @param {Function} fCallback
 * @param {Object} oCalendar
 */
CShareCalendarPopup.prototype.onShow = function (fCallback, oCalendar)
{
	if ($.isFunction(fCallback))
	{
		this.fCallback = fCallback;
	}
	if (!Utils.isUnd(oCalendar))
	{
		this.selectedColor(oCalendar.color());
		this.calendarId(oCalendar.id);
		this.calendarUrl(oCalendar.davUrl() + oCalendar.url());
		this.exportUrl(oCalendar.exportUrl());
		this.icsLink(oCalendar.davUrl() + oCalendar.url() + '?export');
		this.isPublic(oCalendar.isPublic());
		this.owner(oCalendar.owner());

		this.populateShares(oCalendar.shares());
		this.sharedToAll(oCalendar.isSharedToAll());
		this.sharedToAllAccess(oCalendar.sharedToAllAccess);
	}
};

CShareCalendarPopup.prototype.onSaveClick = function ()
{
	if (this.fCallback)
	{
		this.fCallback(this.calendarId(), this.isPublic(), this.getShares(), this.sharedToAll(), this.sharedToAllAccess());
	}
	this.closePopup();
};

CShareCalendarPopup.prototype.closePopup = function ()
{
	this.cleanAll();

	this.closePopup();
};

CShareCalendarPopup.prototype.cleanAll = function ()
{
	this.newShare('');
	this.newShareAccess(Enums.CalendarAccess.Read);
	this.shareToAllAccess = ko.observable(Enums.CalendarAccess.Read);
	//this.shareAutocompleteItem(null);
	this.canAdd(false);
};

/**
 * @param {string} sTerm
 * @param {Function} fResponse
 */
CShareCalendarPopup.prototype.autocompleteCallback = function (sTerm, fResponse)
{
//	var oParameters = {
//			'Action': 'ContactSuggestions',
//			'Search': sTerm,
//			'GlobalOnly': '1'
//		}
//	;
//
//	Ajax.send(oParameters, function (oData) {
//		var aList = [];
//		if (oData && oData.Result && oData.Result && oData.Result.List)
//		{
//			aList = _.map(oData.Result.List, function (oItem) {
//				return oItem && oItem.Email && oItem.Email !== this.owner() ?
//					(oItem.Name && 0 < $.trim(oItem.Name).length ?
//						oItem.ForSharedToAll ? {value: oItem.Name, name: oItem.Name, email: oItem.Email, frequency: oItem.Frequency} :
//						{value:'"' + oItem.Name + '" <' + oItem.Email + '>', name: oItem.Name, email: oItem.Email, frequency: oItem.Frequency} : {value: oItem.Email, name: '', email: oItem.Email, frequency: oItem.Frequency}) : null;
//			}, this);
//
//			aList = _.sortBy(_.compact(aList), function(num){
//				return num.frequency;
//			}).reverse();
//		}
//
//		fResponse(aList);
//
//	}, this);
};

/**
 * @param {Object} koDom
 * @param {Object} ko
 * @param {Object} koLock
 */
CShareCalendarPopup.prototype.initInputosaurus = function (koDom, ko, koLock)
{
	if (koDom() && $(koDom()).length > 0)
	{
		$(koDom()).inputosaurus({
			width: 'auto',
			parseOnBlur: true,
			autoCompleteSource: _.bind(function (oData, fResponse) {
				this.autocompleteCallback(oData.term, fResponse);
			}, this),
			change : _.bind(function (ev) {
				koLock(true);
				this.setRecipient(ko, ev.target.value);
				koLock(false);
			}, this),
			copy: _.bind(function (sVal) {
				this.inputosaurusBuffer = sVal;
			}, this),
			paste: _.bind(function () {
				var sInputosaurusBuffer = this.inputosaurusBuffer || '';
				this.inputosaurusBuffer = '';
				return sInputosaurusBuffer;
			}, this),
			mobileDevice: bMobileDevice
		});
	}
};

/**
 * @param {Object} koRecipient
 * @param {string} sRecipient
 */
CShareCalendarPopup.prototype.setRecipient = function (koRecipient, sRecipient)
{
	if (koRecipient() === sRecipient)
	{
		koRecipient.valueHasMutated();
	}
	else
	{
		koRecipient(sRecipient);
	}
};

CShareCalendarPopup.prototype.getShares = function ()
{
	return $.merge(_.map(AddressUtils.getArrayRecipients(this.guests(), false), function(oGuest){
			return {
				name: oGuest.name,
				email: oGuest.email,
				access: Enums.CalendarAccess.Read
			};
		}),
		_.map(AddressUtils.getArrayRecipients(this.owners(), false), function(oOwner){
			return {
				name: oOwner.name,
				email: oOwner.email,
				access: Enums.CalendarAccess.Write
			};
		}));
};

/**
 * @param {Array} aShares
 */
CShareCalendarPopup.prototype.populateShares = function (aShares)
{
	var
		sGuests = '',
		sOwners = ''
	;

	_.each(aShares, function (oShare) {
		if (oShare.access === Enums.CalendarAccess.Read)
		{
			sGuests = oShare.name !== '' && oShare.name !== oShare.email ? 
						sGuests + '"' + oShare.name + '" <' + oShare.email + '>,' : 
						sGuests + oShare.email + ', ';
		}
		else if (oShare.access === Enums.CalendarAccess.Write)
		{
			sOwners = oShare.name !== '' && oShare.name !== oShare.email ? 
						sOwners + '"' + oShare.name + '" <' + oShare.email + '>,' : 
						sOwners + oShare.email + ', ';
		}
	}, this);

	this.setRecipient (this.guests, sGuests);
	this.setRecipient (this.owners, sOwners);
};

module.exports = new CShareCalendarPopup();