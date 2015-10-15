'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	
	Utils = require('core/js/utils/Common.js'),
	CAbstractFileModel = require('core/js/models/CAbstractFileModel.js')
;

/**
 * @constructor
 * @extends CCommonFileModel
 */
function CFolderModel()
{
	//template
	this.selected = ko.observable(false);
	this.checked = ko.observable(false); // ? = selected ?
	this.deleted = ko.observable(false); // temporary removal until it was confirmation from the server to delete, css-animation
	this.recivedAnim = ko.observable(false).extend({'autoResetToFalse': 500});
	
	this.shared = ko.observable(false);
	this.fileName = ko.observable('');
	
	//onDrop
	this.fullPath = ko.observable('');
	
	//rename
	this.path = ko.observable('');
	
	//pathItems
	this.storageType = ko.observable(Enums.FileStorageType.Personal);
	this.displayName = ko.observable('');
	this.id = ko.observable('');
}

CFolderModel.prototype.parse = function (oData, sPublicHash)
{
	this.shared(!!oData.Shared);
	this.fileName(Utils.pString(oData.Name));
	this.fullPath(Utils.pString(oData.FullPath));
	this.path(Utils.pString(oData.Path));
	this.storageType(Utils.pString(oData.Type));
	this.displayName(this.fileName());
	this.id(Utils.pString(oData.Id));
};

CFolderModel.prototype.eventDragStart = CAbstractFileModel.prototype.eventDragStart;

module.exports = CFolderModel;