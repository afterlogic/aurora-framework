'use strict';

var
	ko = require('knockout'),
	
	Utils = require('core/js/utils/Common.js')
;

/**
 * @param {number} iAccountID
 * @constructor
 */
function CFilterModel(iAccountID)
{
	this.iAccountId = iAccountID;
	
	this.enable = ko.observable(true).extend({'reversible': true});
	
	this.field = ko.observable('').extend({'reversible': true}); //map to Field
	this.condition = ko.observable('').extend({'reversible': true});
	this.filter = ko.observable('').extend({'reversible': true});
	this.action = ko.observable('').extend({'reversible': true});
	this.folder = ko.observable('').extend({'reversible': true});
}

/**
 * @param {Object} oData
 */
CFilterModel.prototype.parse = function (oData)
{
	this.enable(!!oData.Enable);

	this.field(Utils.pInt(oData.Field));
	this.condition(Utils.pInt(oData.Condition));
	this.filter(Utils.pString(oData.Filter));
	this.action(Utils.pInt(oData.Action));
	this.folder(Utils.pString(oData.FolderFullName));
	this.commit();
};

CFilterModel.prototype.revert = function ()
{
	this.enable.revert();
	this.field.revert();
	this.condition.revert();
	this.filter.revert();
	this.action.revert();
	this.folder.revert();
};

CFilterModel.prototype.commit = function ()
{
	this.enable.commit();
	this.field.commit();
	this.condition.commit();
	this.filter.commit();
	this.action.commit();
	this.folder.commit();
};

CFilterModel.prototype.toString = function ()
{
	var aState = [
		this.enable(),
		this.field(),
		this.condition(),
		this.filter(),
		this.action(),
		this.folder()
	];
	
	return aState.join(':');	
};

module.exports = CFilterModel;
