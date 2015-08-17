/**
 * @constructor
 */
function CPhoneViewModel()
{
	this.phone = App.Phone;
	this.action = this.phone.action;
	this.report = this.phone.report;

	this.logs = ko.observableArray([]);
	this.logsToShow = ko.observableArray([]);
	this.spinner = ko.observable(true);
	this.tooltip = ko.observable(Utils.i18n('PHONE/NOT_CONNECTED'));
	this.indicator = ko.observable(Utils.i18n('PHONE/MISSED_CALLS'));
	this.dropdownShow = ko.observable(false);
	this.input = ko.observable('');
	this.input.subscribe(function(sInput) {
		this.dropdownShow(sInput === '' && this.action() === Enums.PhoneAction.OnlineActive);
	}, this);
	this.inputFocus = ko.observable(false);
	this.inputFocus.subscribe(function(bFocus) {
		if(bFocus && this.input() === '' && this.action() === Enums.PhoneAction.OnlineActive)
		{
			this.dropdownShow(true);
		}
	}, this);
	this.phoneAutocompleteItem = ko.observable(null);

	this.action.subscribe(function(sAction) {
		switch (sAction)
		{
			case Enums.PhoneAction.Offline:
				this.tooltip(Utils.i18n('PHONE/NOT_CONNECTED'));
				break;
			case Enums.PhoneAction.OfflineError:
				this.tooltip(Utils.i18n('Connection error'));
				break;
			case Enums.PhoneAction.OfflineInit:
				this.tooltip(Utils.i18n('PHONE/CONNECTING'));
				break;
			case Enums.PhoneAction.OfflineActive:
				break;
			case Enums.PhoneAction.Online:
				this.tooltip(Utils.i18n('PHONE/CONNECTED'));
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
				this.tooltip(Utils.i18n('In Call'));
				break;
			case Enums.PhoneAction.Incoming:
				break;
			case Enums.PhoneAction.IncomingConnect:
				this.tooltip(Utils.i18n('In Call'));
				this.report('');
				this.timer('start');
				break;
		}
	}, this);

	$(document).on('click', _.bind(function (e) {
		if ($(e.target).closest('.item.phone, .ui-autocomplete').length === 0) {
			if (this.action() === Enums.PhoneAction.OnlineActive) {
				this.action(Enums.PhoneAction.Online);
				this.dropdownShow(false);
			}
		}
	}, this));
}

CPhoneViewModel.prototype.answer = function ()
{
	this.action(Enums.PhoneAction.IncomingConnect);
};

CPhoneViewModel.prototype.multiAction = function ()
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
		_.delay(_.bind(function(){
			this.inputFocus(true);
		},this), 500);
	}
	else if (sAction === Enums.PhoneAction.OnlineActive && this.validateNumber())
	{
		if (this.phone)
		{
			this.phone.phoneToCall(this.input());
			this.action(Enums.PhoneAction.Outgoing);
		}

		this.inputFocus(false);
	}
	else if (sAction === Enums.PhoneAction.OnlineActive && !this.validateNumber()) //online action performed through the loss of focus
	{
		this.action(Enums.PhoneAction.Online);
		this.dropdownShow(false);
	}
	else if (
		sAction === Enums.PhoneAction.Outgoing  ||
		sAction === Enums.PhoneAction.Incoming ||
		sAction === Enums.PhoneAction.OutgoingConnect ||
		sAction === Enums.PhoneAction.IncomingConnect
	)
	{
		this.action(Enums.PhoneAction.Online);
		this.dropdownShow(false);
	}
};

CPhoneViewModel.prototype.autocompleteCallback = function (sTerm, fResponse)
{
	var oParameters = {
			'Action': 'ContactSuggestions',
			'Search': sTerm,
			'PhoneOnly': '1'
		}
	;

	this.phoneAutocompleteItem(null);

	sTerm = Utils.trim(sTerm);
	if ('' !== sTerm)
	{
		App.Ajax.send(oParameters, function (oData) {
			var aList = []
			//sCategory = ''
				;

			if (oData && oData.Result && oData.Result.List)
			{
				_.each(oData.Result.List, function (oItem) {
					//sCategory = oItem.Name === '' ? oItem.Email : oItem.Name + ' ' + oItem.Email;
					_.each(oItem.Phones, function (sPhone, sKey) {
						aList.push({
							label: oItem.Name !== '' ? oItem.Name + ' ' + '<' + oItem.Email + '> ' + sPhone : oItem.Email + ' ' + sPhone,
							value: sPhone,
							frequency: oItem.Frequency
							//category: sCategory
						});
					}, this);
				}, this);

				aList = _.sortBy(_.compact(aList), function(num){ return -(num.frequency); });
			}
			fResponse(aList);

		}, this);
	}
};

CPhoneViewModel.prototype.validateNumber = function ()
{
	return (/^[^a-zA-Z\u00BF-\u1FFF\u2C00-\uD7FF]+$/g).test(this.input()); //Check for letters absence
};

CPhoneViewModel.prototype.onLogItem = function (oItem)
{
	this.input(oItem.phoneToCall);
	this.dropdownShow(false);
};

CPhoneViewModel.prototype.getLogs = function ()
{
	this.spinner(true);
	this.logs([]);
	this.logsToShow([]);

	this.phone.getLogs(this.onLogsResponse, this);
};

CPhoneViewModel.prototype.onLogsResponse = function (oResponse, oRequest)
{
	if (oResponse && oResponse.Result) {
		this.logs([]);

		/*_.each(oResponse.Result, function (oStatus) {
			_.each(oStatus, function (oDirection) {
				_.each(oDirection, function (oItem) {
					oItem.phoneToShow = this.phone.getCleanedPhone(oItem.UserDirection === 'incoming' ? oItem.From : oItem.To);
					if (oItem.phoneToShow) {
						this.logs.push(oItem);
					}
				}, this);
			}, this);
		}, this);*/
		_.each(oResponse.Result, function (oItem) {

			oItem.phoneToCall = this.phone.getCleanedPhone(oItem.UserDirection === 'incoming' ? oItem.From : oItem.To);
			if (oItem.UserDisplayName)
			{
				oItem.phoneToShow = oItem.UserDisplayName;
			}
			else
			{
				oItem.phoneToShow = oItem.phoneToCall;
			}

			if (oItem.phoneToShow) {
				this.logs.push(oItem);
			}
		}, this);

		this.logs(_.sortBy(this.logs(), function(oItem){ return -(Date.parse(oItem.StartTime)); }).slice(0, 100));

		this.seeMore();
	}

	this.spinner(false);
};

CPhoneViewModel.prototype.seeMore = function ()
{
	this.logsToShow(this.logs().slice(0, this.logsToShow().length + 10));
};

CPhoneViewModel.prototype.timer = (function ()
{
	var
		iIntervalId = 0,
		iSeconds = 0,
		iMinutes = 0,
		fAddNull = function (iItem) {
			var sItem = iItem.toString();
			return sItem.length === 1 ? sItem = '0' + sItem : sItem;
		};

	return function (sAction)
	{
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
				this.report(Utils.i18n('PHONE/PASSED_TIME') + ' ' + fAddNull(iMinutes) + ':' + fAddNull(iSeconds));
				iSeconds++;
			}, this), 1000);
		}
		else if (sAction === 'stop')
		{
			clearInterval(iIntervalId);
		}
	};
}());