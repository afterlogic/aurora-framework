'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	moment = require('moment'),
	
	Utils = require('core/js/utils/Common.js'),
	TextUtils = require('core/js/utils/Text.js'),
	DateUtils = require('core/js/utils/Date.js'),
	AddressUtils = require('core/js/utils/Address.js'),
	
	CDateModel = require('core/js/models/CDateModel.js'),
	
	bMobileApp = false
;

/**
 * @constructor
 */
function CContactModel()
{
	this.allowSendEmails = ko.computed(function () {
		return true;//AppData.App.AllowWebMail && AppData.Accounts.isCurrentAllowsMail();
	}, this);
	
	this.sEmailDefaultType = Enums.ContactEmailType.Personal;
	this.sPhoneDefaultType = Enums.ContactPhoneType.Mobile;
	this.sAddressDefaultType = Enums.ContactAddressType.Personal;
	
	this.voiceApp = null;
//	if (App.Phone)
//	{
//		this.voiceApp = App.Phone.voiceApp;
//	}

	this.idContact = ko.observable('');
	this.idUser = ko.observable('');
	this.global = ko.observable(false);
	this.itsMe = ko.observable(false);

	this.isNew = ko.observable(false);
	this.readOnly = ko.observable(false);
	this.edited = ko.observable(false);
	this.extented = ko.observable(false);
	this.personalCollapsed = ko.observable(false);
	this.businessCollapsed = ko.observable(false);
	this.otherCollapsed = ko.observable(false);
	this.groupsCollapsed = ko.observable(false);

	this.displayName = ko.observable('');
	this.firstName = ko.observable('');
	this.lastName = ko.observable('');
	this.nickName = ko.observable('');

	this.skype = ko.observable('');
	this.facebook = ko.observable('');

	this.displayNameFocused = ko.observable(false);

	this.primaryEmail = ko.observable(this.sEmailDefaultType);
	this.primaryPhone = ko.observable(this.sPhoneDefaultType);
	this.primaryAddress = ko.observable(this.sAddressDefaultType);

	this.mainPrimaryEmail = ko.computed({
		'read': this.primaryEmail,
		'write': function (mValue) {
			if (!Utils.isUnd(mValue) && 0 <= $.inArray(mValue, [Enums.ContactEmailType.Personal, Enums.ContactEmailType.Business, Enums.ContactEmailType.Other]))
			{
				this.primaryEmail(mValue);
			}
			else
			{
				this.primaryEmail(Enums.ContactEmailType.Personal);
			}
		},
		'owner': this
	});

	this.mainPrimaryPhone = ko.computed({
		'read': this.primaryPhone,
		'write': function (mValue) {
			if (!Utils.isUnd(mValue) && 0 <= $.inArray(mValue, [Enums.ContactPhoneType.Mobile, Enums.ContactPhoneType.Personal, Enums.ContactPhoneType.Business]))
			{
				this.primaryPhone(mValue);
			}
			else
			{
				this.primaryPhone(Enums.ContactPhoneType.Mobile);
			}
		},
		'owner': this
	});
	
	this.mainPrimaryAddress = ko.computed({
		'read': this.primaryAddress,
		'write': function (mValue) {
			if (!Utils.isUnd(mValue) && 0 <= $.inArray(mValue, [Enums.ContactAddressType.Personal, Enums.ContactAddressType.Business]))
			{
				this.primaryAddress(mValue);
			}
			else
			{
				this.primaryAddress(Enums.ContactAddressType.Personal);
			}
		},
		'owner': this
	});

	this.personalEmail = ko.observable('');
	this.personalStreetAddress = ko.observable('');
	this.personalCity = ko.observable('');
	this.personalState = ko.observable('');
	this.personalZipCode = ko.observable('');
	this.personalCountry = ko.observable('');
	this.personalWeb = ko.observable('');
	this.personalFax = ko.observable('');
	this.personalPhone = ko.observable('');
	this.personalMobile = ko.observable('');

	this.businessEmail = ko.observable('');
	this.businessCompany = ko.observable('');
	this.businessDepartment = ko.observable('');
	this.businessJob = ko.observable('');
	this.businessOffice = ko.observable('');
	this.businessStreetAddress = ko.observable('');
	this.businessCity = ko.observable('');
	this.businessState = ko.observable('');
	this.businessZipCode = ko.observable('');
	this.businessCountry = ko.observable('');
	this.businessWeb = ko.observable('');
	this.businessFax = ko.observable('');
	this.businessPhone = ko.observable('');

	this.otherEmail = ko.observable('');
	this.otherBirthdayMonth = ko.observable('0');
	this.otherBirthdayDay = ko.observable('0');
	this.otherBirthdayYear = ko.observable('0');
	this.otherNotes = ko.observable('');
	this.etag = ko.observable('');
	
	this.sharedToAll = ko.observable(false);

	this.birthdayIsEmpty = ko.computed(function () {
		var
			bMonthEmpty = '0' === this.otherBirthdayMonth(),
			bDayEmpty = '0' === this.otherBirthdayDay(),
			bYearEmpty = '0' === this.otherBirthdayYear()
		;

		return (bMonthEmpty || bDayEmpty || bYearEmpty);
	}, this);
	
	this.otherBirthday = ko.computed(function () {
		var
			sBirthday = '',
			iYear = Utils.pInt(this.otherBirthdayYear()),
			iMonth = Utils.pInt(this.otherBirthdayMonth()),
			iDay = Utils.pInt(this.otherBirthdayDay()),
			oDateModel = new CDateModel()
		;
		
		if (!this.birthdayIsEmpty())
		{
			var fullYears = moment().diff(moment(iYear + '/' + iMonth + '/' + iDay, "YYYY/MM/DD"), 'years'),
				text = TextUtils.i18n('CONTACTS/YEARS_TEXT_PLURAL', {
					'COUNT': fullYears
				}, null, fullYears)
			;
			oDateModel.setDate(iYear, 0 < iMonth ? iMonth - 1 : 0, iDay);
			sBirthday = oDateModel.getShortDate() + ' (' + text + ')';
		}
		
		return sBirthday;
	}, this);

	this.groups = ko.observableArray([]);

	this.groupsIsEmpty = ko.computed(function () {
		return 0 === this.groups().length;
	}, this);

	this.email = ko.computed({
		'read': function () {
			var sResult = '';
			switch (this.primaryEmail()) {
				case Enums.ContactEmailType.Personal:
					sResult = this.personalEmail();
					break;
				case Enums.ContactEmailType.Business:
					sResult = this.businessEmail();
					break;
				case Enums.ContactEmailType.Other:
					sResult = this.otherEmail();
					break;
			}
			return sResult;
		},
		'write': function (sEmail) {
			switch (this.primaryEmail()) {
				case Enums.ContactEmailType.Personal:
					this.personalEmail(sEmail);
					break;
				case Enums.ContactEmailType.Business:
					this.businessEmail(sEmail);
					break;
				case Enums.ContactEmailType.Other:
					this.otherEmail(sEmail);
					break;
				default:
					this.primaryEmail(this.sEmailDefaultType);
					this.email(sEmail);
					break;
			}
		},
		'owner': this
	});

	this.personalIsEmpty = ko.computed(function () {
		var sPersonalEmail = (this.personalEmail() !== this.email()) ? this.personalEmail() : '';
		return '' === '' + sPersonalEmail +
			this.personalStreetAddress() +
			this.personalCity() +
			this.personalState() +
			this.personalZipCode() +
			this.personalCountry() +
			this.personalWeb() +
			this.personalFax() +
			this.personalPhone() +
			this.personalMobile()
		;
	}, this);

	this.businessIsEmpty = ko.computed(function () {
		var sBusinessEmail = (this.businessEmail() !== this.email()) ? this.businessEmail() : '';
		return '' === '' + sBusinessEmail +
			this.businessCompany() +
			this.businessDepartment() +
			this.businessJob() +
			this.businessOffice() +
			this.businessStreetAddress() +
			this.businessCity() +
			this.businessState() +
			this.businessZipCode() +
			this.businessCountry() +
			this.businessWeb() +
			this.businessFax() +
			this.businessPhone()
		;
	}, this);

	this.otherIsEmpty = ko.computed(function () {
		var sOtherEmail = (this.otherEmail() !== this.email()) ? this.otherEmail() : '';
		return ('' === ('' + sOtherEmail + this.otherNotes())) && this.birthdayIsEmpty();
	}, this);
	
	this.phone = ko.computed({
		'read': function () {
			var sResult = '';
			switch (this.primaryPhone()) {
				case Enums.ContactPhoneType.Mobile:
					sResult = this.personalMobile();
					break;
				case Enums.ContactPhoneType.Personal:
					sResult = this.personalPhone();
					break;
				case Enums.ContactPhoneType.Business:
					sResult = this.businessPhone();
					break;
			}
			return sResult;
		},
		'write': function (sPhone) {
			switch (this.primaryPhone()) {
				case Enums.ContactPhoneType.Mobile:
					this.personalMobile(sPhone);
					break;
				case Enums.ContactPhoneType.Personal:
					this.personalPhone(sPhone);
					break;
				case Enums.ContactPhoneType.Business:
					this.businessPhone(sPhone);
					break;
				default:
					this.primaryPhone(this.sEmailDefaultType);
					this.phone(sPhone);
					break;
			}
		},
		'owner': this
	});
	
	this.address = ko.computed({
		'read': function () {
			var sResult = '';
			switch (this.primaryAddress()) {
				case Enums.ContactAddressType.Personal:
					sResult = this.personalStreetAddress();
					break;
				case Enums.ContactAddressType.Business:
					sResult = this.businessStreetAddress();
					break;
			}
			return sResult;
		},
		'write': function (sAddress) {
			switch (this.primaryAddress()) {
				case Enums.ContactAddressType.Personal:
					this.personalStreetAddress(sAddress);
					break;
				case Enums.ContactAddressType.Business:
					this.businessStreetAddress(sAddress);
					break;
				default:
					this.primaryAddress(this.sEmailDefaultType);
					this.address(sAddress);
					break;
			}
		},
		'owner': this
	});

	this.emails = ko.computed(function () {
		var aList = [];
		
		if ('' !== this.personalEmail())
		{
			aList.push({'text': TextUtils.i18n('CONTACTS/OPTION_PERSONAL') + ': ' + this.personalEmail(), 'value': Enums.ContactEmailType.Personal});
		}
		if ('' !== this.businessEmail())
		{
			aList.push({'text': TextUtils.i18n('CONTACTS/OPTION_BUSINESS') + ': ' + this.businessEmail(), 'value': Enums.ContactEmailType.Business});
		}
		if ('' !== this.otherEmail())
		{
			aList.push({'text': TextUtils.i18n('CONTACTS/OPTION_OTHER') + ': ' + this.otherEmail(), 'value': Enums.ContactEmailType.Other});
		}

		return aList;

	}, this);

	this.phones = ko.computed(function () {
		var aList = [];

		if ('' !== this.personalMobile())
		{
			aList.push({'text': TextUtils.i18n('CONTACTS/LABEL_MOBILE') + ': ' + this.personalMobile(), 'value': Enums.ContactPhoneType.Mobile});
		}
		if ('' !== this.personalPhone())
		{
			aList.push({'text': TextUtils.i18n('CONTACTS/OPTION_PERSONAL') + ': ' + this.personalPhone(), 'value': Enums.ContactPhoneType.Personal});
		}
		if ('' !== this.businessPhone())
		{
			aList.push({'text': TextUtils.i18n('CONTACTS/OPTION_BUSINESS') + ': ' + this.businessPhone(), 'value': Enums.ContactPhoneType.Business});
		}
		return aList;

	}, this);
	
	this.addresses = ko.computed(function () {
		var aList = [];

		if ('' !== this.personalStreetAddress())
		{
			aList.push({'text': TextUtils.i18n('CONTACTS/OPTION_PERSONAL') + ': ' + this.personalStreetAddress(), 'value': Enums.ContactAddressType.Personal});
		}
		if ('' !== this.businessStreetAddress())
		{
			aList.push({'text': TextUtils.i18n('CONTACTS/OPTION_BUSINESS') + ': ' + this.businessStreetAddress(), 'value': Enums.ContactAddressType.Business});
		}
		return aList;

	}, this);

	this.hasEmails = ko.computed(function () {
		return 0 < this.emails().length;
	}, this);

	this.extented.subscribe(function (bValue) {
		if (bValue)
		{
			this.personalCollapsed(!this.personalIsEmpty());
			this.businessCollapsed(!this.businessIsEmpty());
			this.otherCollapsed(!this.otherIsEmpty());
			this.groupsCollapsed(!this.groupsIsEmpty());
		}
	}, this);

	this.birthdayMonthSelect = CContactModel.birthdayMonthSelect;
	this.birthdayYearSelect = CContactModel.birthdayYearSelect;

	this.birthdayDaySelect = ko.computed(function () {

		var
			iIndex = 1,
			iLen = Utils.pInt(DateUtils.daysInMonth(this.otherBirthdayMonth(), this.otherBirthdayYear())),
			sIndex = '',
			aList = [{'text': TextUtils.i18n('DATETIME/DAY'), 'value': '0'}]
		;

		for (; iIndex <= iLen; iIndex++)
		{
			sIndex = iIndex.toString();
			aList.push({'text': sIndex, 'value': sIndex});
		}

		return aList;

	}, this);


	for (var oDate = new Date(), sIndex = '', iIndex = oDate.getFullYear(), iLen = 2012 - 80; iIndex >= iLen; iIndex--)
	{
		sIndex = iIndex.toString();
		this.birthdayYearSelect.push(
			{'text': sIndex, 'value': sIndex}
		);
	}

	this.canBeSave = ko.computed(function () {
		return this.displayName() !== '' || !!this.emails().length;
	}, this);
}

CContactModel.birthdayMonths = DateUtils.getMonthNamesArray();

CContactModel.birthdayMonthSelect = [
	{'text': TextUtils.i18n('DATETIME/MONTH'), value: '0'},
	{'text': CContactModel.birthdayMonths[0], value: '1'},
	{'text': CContactModel.birthdayMonths[1], value: '2'},
	{'text': CContactModel.birthdayMonths[2], value: '3'},
	{'text': CContactModel.birthdayMonths[3], value: '4'},
	{'text': CContactModel.birthdayMonths[4], value: '5'},
	{'text': CContactModel.birthdayMonths[5], value: '6'},
	{'text': CContactModel.birthdayMonths[6], value: '7'},
	{'text': CContactModel.birthdayMonths[7], value: '8'},
	{'text': CContactModel.birthdayMonths[8], value: '9'},
	{'text': CContactModel.birthdayMonths[9], value: '10'},
	{'text': CContactModel.birthdayMonths[10], value: '11'},
	{'text': CContactModel.birthdayMonths[11], value: '12'}
];

CContactModel.birthdayYearSelect = [
	{'text': TextUtils.i18n('DATETIME/YEAR'), 'value': '0'}
];

CContactModel.prototype.clear = function ()
{
	this.isNew(false);
	this.readOnly(false);

	this.idContact('');
	this.idUser('');
	this.global(false);
	this.itsMe(false);

	this.edited(false);
	this.extented(false);
	this.personalCollapsed(false);
	this.businessCollapsed(false);
	this.otherCollapsed(false);
	this.groupsCollapsed(false);

	this.displayName('');
	this.firstName('');
	this.lastName('');
	this.nickName('');

	this.skype('');
	this.facebook('');

	this.primaryEmail(this.sEmailDefaultType);
	this.primaryPhone(this.sPhoneDefaultType);
	this.primaryAddress(this.sAddressDefaultType);

	this.personalEmail('');
	this.personalStreetAddress('');
	this.personalCity('');
	this.personalState('');
	this.personalZipCode('');
	this.personalCountry('');
	this.personalWeb('');
	this.personalFax('');
	this.personalPhone('');
	this.personalMobile('');

	this.businessEmail('');
	this.businessCompany('');
	this.businessDepartment('');
	this.businessJob('');
	this.businessOffice('');
	this.businessStreetAddress('');
	this.businessCity('');
	this.businessState('');
	this.businessZipCode('');
	this.businessCountry('');
	this.businessWeb('');
	this.businessFax('');
	this.businessPhone('');

	this.otherEmail('');
	this.otherBirthdayMonth('0');
	this.otherBirthdayDay('0');
	this.otherBirthdayYear('0');
	this.otherNotes('');

	this.etag('');
	this.sharedToAll(false);

	this.groups([]);
};

CContactModel.prototype.switchToNew = function ()
{
	this.clear();
	this.edited(true);
	this.extented(false);
	this.isNew(true);
	if (!bMobileApp)
	{
		this.displayNameFocused(true);
	}
};

CContactModel.prototype.switchToView = function ()
{
	this.edited(false);
	this.extented(false);
};

/**
 * @return {Object}
 */
CContactModel.prototype.toObject = function ()
{
	var oResult = {
		'ContactId': this.idContact(),
		'PrimaryEmail': this.primaryEmail(),
		'PrimaryPhone': this.primaryPhone(),
		'PrimaryAddress': this.primaryAddress(),
		'UseFriendlyName': '1',
		'Title': '',
		'FullName': this.displayName(),
		'FirstName': this.firstName(),
		'LastName': this.lastName(),
		'NickName': this.nickName(),

		'Global': this.global() ? '1' : '0',
		'ItsMe': this.itsMe() ? '1' : '0',

		'Skype': this.skype(),
		'Facebook': this.facebook(),

		'HomeEmail': this.personalEmail(),
		'HomeStreet': this.personalStreetAddress(),
		'HomeCity': this.personalCity(),
		'HomeState': this.personalState(),
		'HomeZip': this.personalZipCode(),
		'HomeCountry': this.personalCountry(),
		'HomeFax': this.personalFax(),
		'HomePhone': this.personalPhone(),
		'HomeMobile': this.personalMobile(),
		'HomeWeb': this.personalWeb(),

		'BusinessEmail': this.businessEmail(),
		'BusinessCompany': this.businessCompany(),
		'BusinessJobTitle': this.businessJob(),
		'BusinessDepartment': this.businessDepartment(),
		'BusinessOffice': this.businessOffice(),
		'BusinessStreet': this.businessStreetAddress(),
		'BusinessCity': this.businessCity(),
		'BusinessState': this.businessState(),
		'BusinessZip': this.businessZipCode(),
		'BusinessCountry': this.businessCountry(),
		'BusinessFax': this.businessFax(),
		'BusinessPhone': this.businessPhone(),
		'BusinessWeb': this.businessWeb(),

		'OtherEmail': this.otherEmail(),
		'Notes': this.otherNotes(),
		'ETag': this.etag(),
		'BirthdayDay': this.otherBirthdayDay(),
		'BirthdayMonth': this.otherBirthdayMonth(),
		'BirthdayYear': this.otherBirthdayYear(),

		'SharedToAll': this.sharedToAll() ? '1' : '0',
		
		'GroupsIds': this.groups()
	};

	return oResult;
};

/**
 * @param {Object} oData
 */
CContactModel.prototype.parse = function (oData)
{
	var
		iPrimaryEmail = 0,
		iPrimaryPhone = 0,
		iPrimaryAddress = 0
	;

	this.idContact(Utils.pString(oData.IdContact));
	this.idUser(Utils.pString(oData.IdUser));

	this.global(!!oData.Global);
	this.itsMe(!!oData.ItsMe);
	this.readOnly(!!oData.ReadOnly);

	this.displayName(Utils.pString(oData.FullName));
	this.firstName(Utils.pString(oData.FirstName));
	this.lastName(Utils.pString(oData.LastName));
	this.nickName(Utils.pString(oData.NickName));

	this.skype(Utils.pString(oData.Skype));
	this.facebook(Utils.pString(oData.Facebook));

	iPrimaryEmail = Utils.pInt(oData.PrimaryEmail);
	switch (iPrimaryEmail)
	{
		case 1:
			iPrimaryEmail = Enums.ContactEmailType.Business;
			break;
		case 2:
			iPrimaryEmail = Enums.ContactEmailType.Other;
			break;
		default:
		case 0:
			iPrimaryEmail = Enums.ContactEmailType.Personal;
			break;
	}
	this.primaryEmail(iPrimaryEmail);

	iPrimaryPhone = Utils.pInt(oData.PrimaryPhone);
	switch (iPrimaryPhone)
	{
		case 2:
			iPrimaryPhone = Enums.ContactPhoneType.Business;
			break;
		case 1:
			iPrimaryPhone = Enums.ContactPhoneType.Personal;
			break;
		default:
		case 0:
			iPrimaryPhone = Enums.ContactPhoneType.Mobile;
			break;
	}
	this.primaryPhone(iPrimaryPhone);

	iPrimaryAddress = Utils.pInt(oData.PrimaryAddress);
	switch (iPrimaryAddress)
	{
		case 1:
			iPrimaryAddress = Enums.ContactAddressType.Business;
			break;
		default:
		case 0:
			iPrimaryAddress = Enums.ContactAddressType.Personal;
			break;
	}
	this.primaryAddress(iPrimaryAddress);

	this.personalEmail(Utils.pString(oData.HomeEmail));
	this.personalStreetAddress(Utils.pString(oData.HomeStreet));
	this.personalCity(Utils.pString(oData.HomeCity));
	this.personalState(Utils.pString(oData.HomeState));
	this.personalZipCode(Utils.pString(oData.HomeZip));
	this.personalCountry(Utils.pString(oData.HomeCountry));
	this.personalWeb(Utils.pString(oData.HomeWeb));
	this.personalFax(Utils.pString(oData.HomeFax));
	this.personalPhone(Utils.pString(oData.HomePhone));
	this.personalMobile(Utils.pString(oData.HomeMobile));

	this.businessEmail(Utils.pString(oData.BusinessEmail));
	this.businessCompany(Utils.pString(oData.BusinessCompany));
	this.businessDepartment(Utils.pString(oData.BusinessDepartment));
	this.businessJob(Utils.pString(oData.BusinessJobTitle));
	this.businessOffice(Utils.pString(oData.BusinessOffice));
	this.businessStreetAddress(Utils.pString(oData.BusinessStreet));
	this.businessCity(Utils.pString(oData.BusinessCity));
	this.businessState(Utils.pString(oData.BusinessState));
	this.businessZipCode(Utils.pString(oData.BusinessZip));
	this.businessCountry(Utils.pString(oData.BusinessCountry));
	this.businessWeb(Utils.pString(oData.BusinessWeb));
	this.businessFax(Utils.pString(oData.BusinessFax));
	this.businessPhone(Utils.pString(oData.BusinessPhone));

	this.otherEmail(Utils.pString(oData.OtherEmail));
	this.otherBirthdayMonth(Utils.pString(oData.BirthdayMonth));
	this.otherBirthdayDay(Utils.pString(oData.BirthdayDay));
	this.otherBirthdayYear(Utils.pString(oData.BirthdayYear));
	this.otherNotes(Utils.pString(oData.Notes));

	this.etag(Utils.pString(oData.ETag));

	this.sharedToAll(!!oData.SharedToAll);

	if (_.isArray(oData.GroupsIds))
	{
		this.groups(
			_.map(oData.GroupsIds, function (sItem) {
				return Utils.pString(sItem);
			})
		);
	}
};

/**
 * @param {string} sEmail
 * @return {string}
 */
CContactModel.prototype.getFullEmail = function (sEmail)
{
	if (!Utils.isNonEmptyString(sEmail))
	{
		sEmail = this.email();
	}
	return AddressUtils.getFullEmail(this.displayName(), sEmail);
};

CContactModel.prototype.getEmailsString = function ()
{
	return _.uniq(_.without([this.email(), this.personalEmail(), this.businessEmail(), this.otherEmail()], '')).join(',');
};

CContactModel.prototype.sendThisContact = function ()
{
	App.Api.composeMessageWithVcard(this);
};

/**
 * @param {?} mLink
 * @return {boolean}
 */
CContactModel.prototype.isStrLink = function (mLink)
{
	return (/^http/).test(mLink);
};

/**
 * @param {string} sPhone
 */
CContactModel.prototype.onCallClick = function (sPhone)
{
//	App.Phone.call(sPhone);
};

module.exports = CContactModel;