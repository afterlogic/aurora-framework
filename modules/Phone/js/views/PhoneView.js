'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	$ = require('jquery'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	ModulesManager = require('core/js/ModulesManager.js'),
	
	Phone = require('modules/Phone/js/Phone.js')
;

/**
 * @constructor
 */
function CPhoneView()
{
	this.phone = Phone;
	this.action = Phone.action;
	this.report = Phone.report;

	this.logs = ko.observableArray([]);
	this.logsToShow = ko.observableArray([]);
	this.spinner = ko.observable(true);
	this.tooltip = ko.observable(TextUtils.i18n('PHONE/NOT_CONNECTED'));
	this.indicator = ko.observable(TextUtils.i18n('PHONE/MISSED_CALLS'));
	this.dropdownShow = ko.observable(false);
	this.input = ko.observable('');
	this.input.subscribe(function (sInput) {
		this.dropdownShow(sInput === '' && this.action() === Enums.PhoneAction.OnlineActive);
	}, this);
	this.inputFocus = ko.observable(false);
	this.inputFocus.subscribe(function (bFocus) {
		if(bFocus && this.input() === '' && this.action() === Enums.PhoneAction.OnlineActive)
		{
			this.dropdownShow(true);
		}
	}, this);
	this.phoneAutocompleteItem = ko.observable(null);

	this.action.subscribe(function (sAction) {
		switch (sAction)
		{
			case Enums.PhoneAction.Offline:
				this.tooltip(TextUtils.i18n('PHONE/NOT_CONNECTED'));
				break;
			case Enums.PhoneAction.OfflineError:
				this.tooltip(TextUtils.i18n('Connection error'));
				break;
			case Enums.PhoneAction.OfflineInit:
				this.tooltip(TextUtils.i18n('PHONE/CONNECTING'));
				break;
			case Enums.PhoneAction.OfflineActive:
				break;
			case Enums.PhoneAction.Online:
				this.tooltip(TextUtils.i18n('PHONE/CONNECTED'));
				this.input('');
				this.report('');
				this.timer('stop');
				break;
			case Enums.PhoneAction.OnlineActive:
				break;
			case Enums.PhoneAction.Outgoing:
				this.timer('start');
				break;
			case Enums.PhoneAction.OutgoingConnect:
				this.tooltip(TextUtils.i18n('In Call'));
				break;
			case Enums.PhoneAction.Incoming:
				break;
			case Enums.PhoneAction.IncomingConnect:
				this.tooltip(TextUtils.i18n('In Call'));
				this.report('');
				this.timer('start');
				break;
		}
	}, this);

	$(document).on('click', _.bind(function (e) {
		if ($(e.target).closest('.item.phone, .ui-autocomplete').length === 0)
		{
			if (this.action() === Enums.PhoneAction.OnlineActive)
			{
				this.action(Enums.PhoneAction.Online);
				this.dropdownShow(false);
			}
		}
	}, this));
}

CPhoneView.prototype.ViewTemplate = 'Phone_PhoneView';

CPhoneView.prototype.answer = function ()
{
	this.action(Enums.PhoneAction.IncomingConnect);
};

CPhoneView.prototype.multiAction = function ()
{
	var sAction = this.action();
	/*if (sAction === Enums.PhoneAction.Offline)
	 {
	 //this.action(Enums.PhoneAction.OfflineActive);
	 }
	 else */
	if (sAction === Enums.PhoneAction.OfflineActive)
	{
		this.action(Enums.PhoneAction.Offline);
	}
	else if (sAction === Enums.PhoneAction.Online)
	{
		this.action(Enums.PhoneAction.OnlineActive);
		this.getLogs();
		_.delay(_.bind(function () {
			this.inputFocus(true);
		},this), 500);
	}
	else if (sAction === Enums.PhoneAction.OnlineActive && this.validateNumber())
	{
		if (Phone)
		{
			Phone.phoneToCall(this.input());
			this.action(Enums.PhoneAction.Outgoing);
		}

		this.inputFocus(false);
	}
	else if (sAction === Enums.PhoneAction.OnlineActive && !this.validateNumber()) //online action performed through the loss of focus
	{
		this.action(Enums.PhoneAction.Online);
		this.dropdownShow(false);
	}
	else if (sAction === Enums.PhoneAction.Outgoing  ||
			sAction === Enums.PhoneAction.Incoming ||
			sAction === Enums.PhoneAction.OutgoingConnect ||
			sAction === Enums.PhoneAction.IncomingConnect)
	{
		this.action(Enums.PhoneAction.Online);
		this.dropdownShow(false);
	}
};

/**
 * @param {object} oRequest
 * @param {function} fResponse
 */
CPhoneView.prototype.autocompleteCallback = function (oRequest, fResponse)
{
	var fAutocompleteCallback = ModulesManager.run('Contacts', 'getSuggestionsAutocompletePhoneCallback');
	
	if ($.isFunction(fAutocompleteCallback))
	{
		this.phoneAutocompleteItem(null);
		fAutocompleteCallback(oRequest, fResponse, this.owner(), false);
	}
};

CPhoneView.prototype.validateNumber = function ()
{
	return (/^[^a-zA-Z\u00BF-\u1FFF\u2C00-\uD7FF]+$/g).test(this.input()); //Check for letters absence
};

/**
 * @param {object} oItem
 */
CPhoneView.prototype.onLogItem = function (oItem)
{
	this.input(oItem.phoneToCall);
	this.dropdownShow(false);
};

CPhoneView.prototype.getLogs = function ()
{
	this.spinner(true);
	this.logs([]);
	this.logsToShow([]);

	Phone.getLogs(this.onLogsResponse, this);
};

/**
 * @param {object} oResponse
 * @param {object} oRequest
 */
CPhoneView.prototype.onLogsResponse = function (oResponse, oRequest)
{
	if (Utils.isNonEmptyArray(oResponse.Result))
	{
		this.logs([]);

		/*_.each(oResponse.Result, function (oStatus) {
			_.each(oStatus, function (oDirection) {
				_.each(oDirection, function (oItem) {
					oItem.phoneToShow = Phone.getCleanedPhone(oItem.UserDirection === 'incoming' ? oItem.From : oItem.To);
					if (oItem.phoneToShow)
					{
						this.logs.push(oItem);
					}
				}, this);
			}, this);
		}, this);*/
		_.each(oResponse.Result, function (oItem) {
			oItem.phoneToCall = Phone.getCleanedPhone(oItem.UserDirection === 'incoming' ? oItem.From : oItem.To);
			if (oItem.UserDisplayName)
			{
				oItem.phoneToShow = oItem.UserDisplayName;
			}
			else
			{
				oItem.phoneToShow = oItem.phoneToCall;
			}

			if (oItem.phoneToShow)
			{
				this.logs.push(oItem);
			}
		}, this);

		this.logs(_.sortBy(this.logs(), function (oItem) { return -(Date.parse(oItem.StartTime)); }).slice(0, 100));

		this.seeMore();
	}

	this.spinner(false);
};

CPhoneView.prototype.seeMore = function ()
{
	this.logsToShow(this.logs().slice(0, this.logsToShow().length + 10));
};

CPhoneView.prototype.timer = (function ()
{
	var
		iIntervalId = 0,
		iSeconds = 0,
		iMinutes = 0,
		fAddNull = function (iItem) {
			var sItem = iItem.toString();
			return sItem.length === 1 ? sItem = '0' + sItem : sItem;
		};

	return function (sAction) {
		if (sAction === 'start')
		{
			iSeconds = 0;
			iMinutes = 0;
			iIntervalId = setInterval(_.bind(function() {
				if(iSeconds === 60)
				{
					iSeconds = 0;
					iMinutes++;
				}
				this.report(TextUtils.i18n('PHONE/PASSED_TIME') + ' ' + fAddNull(iMinutes) + ':' + fAddNull(iSeconds));
				iSeconds++;
			}, this), 1000);
		}
		else if (sAction === 'stop')
		{
			clearInterval(iIntervalId);
		}
	};
}());

module.exports = new CPhoneView();