'use strict';

var
	ko = require('knockout'),
			
	TextUtils = require('modules/Core/js/utils/Text.js'),
	App = require('modules/Core/js/App.js')
;

/**
 * @constructor
 */
function CGroupModel()
{
	this.isNew = ko.observable(false);
	this.readOnly = ko.observable(false);

	this.idGroup = ko.observable('');
	this.idUser = ko.observable('');

	this.name = ko.observable('');

	this.isOrganization = ko.observable(false);
	this.email = ko.observable('');
	this.country = ko.observable('');
	this.city = ko.observable('');
	this.company = ko.observable('');
	this.fax = ko.observable('');
	this.phone = ko.observable('');
	this.state = ko.observable('');
	this.street = ko.observable('');
	this.web = ko.observable('');
	this.zip = ko.observable('');
	
	this.edited = ko.observable(false);

	this.nameFocused = ko.observable(false);

	this.canBeSave = ko.computed(function () {
		return '' !== this.name();
	}, this);

	this.newContactsInGroupCount = ko.observable(0);

	this.newContactsInGroupHint = ko.computed(function () {
		var iCount = this.newContactsInGroupCount();
		return this.isNew() && 0 < iCount ? TextUtils.i18n('CONTACTS/INFO_NEW_GROUP_CONTACTS_PLURAL', {
			'COUNT' : iCount
		}, null, iCount) : '';
	}, this);
	
	this.events = ko.observableArray([]);

}

CGroupModel.prototype.clear = function ()
{
	this.isNew(false);

	this.idGroup('');
	this.idUser('');

	this.name('');
	this.nameFocused(false);
	this.edited(false);
	
	this.isOrganization(false);
	this.email('');
	this.country('');
	this.city('');
	this.company('');
	this.fax('');
	this.phone('');
	this.state('');
	this.street('');
	this.web('');
	this.zip('');	
	this.events([]);
};

CGroupModel.prototype.populate = function (oGroup)
{
	this.isNew(oGroup.isNew());

	this.idGroup(oGroup.idGroup());
	this.idUser(oGroup.idUser());

	this.name(oGroup.name());
	this.nameFocused(oGroup.nameFocused());
	this.edited(oGroup.edited());
	
	this.isOrganization(oGroup.isOrganization());
	this.email(oGroup.email());
	this.country(oGroup.country());
	this.city(oGroup.city());
	this.company(oGroup.company());
	this.fax(oGroup.fax());
	this.phone(oGroup.phone());
	this.state(oGroup.state());
	this.street(oGroup.street());
	this.web(oGroup.web());
	this.zip(oGroup.zip());	
};

CGroupModel.prototype.switchToNew = function ()
{
	this.clear();
	this.edited(true);
	this.isNew(true);
	if (!App.isMobile())
	{
		this.nameFocused(true);
	}
};

CGroupModel.prototype.switchToView = function ()
{
	this.edited(false);
};

/**
 * @return {Object}
 */
CGroupModel.prototype.toObject = function ()
{
	return {
		'GroupId': this.idGroup(),
		'Name': this.name(),
		'IsOrganization': this.isOrganization() ? '1' : '0',
		'Email': this.email(),
		'Country': this.country(),
		'City': this.city(),
		'Company': this.company(),
		'Fax': this.fax(),
		'Phone': this.phone(),
		'State': this.state(),
		'Street': this.street(),
		'Web': this.web(),
		'Zip': this.zip()			
	};
};

module.exports = CGroupModel;