'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	
	CAbstractPopup = require('modules/CoreClient/js/popups/CAbstractPopup.js')
;

/**
 * @constructor
 */
function CCreateFolderPopup()
{
	CAbstractPopup.call(this);
	
	this.fCallback = null;
	this.folderName = ko.observable('');
	this.folderName.focus = ko.observable(false);
	this.folderName.error = ko.observable('');

	this.folderName.subscribe(function () {
		this.folderName.error('');
	}, this);
}

_.extendOwn(CCreateFolderPopup.prototype, CAbstractPopup.prototype);

CCreateFolderPopup.prototype.PopupTemplate = '%ModuleName%_CreateFolderPopup';

/**
 * @param {Function} fCallback
 */
CCreateFolderPopup.prototype.onShow = function (fCallback)
{
	this.folderName('');
	this.folderName.focus(true);
	this.folderName.error('');
	
	if ($.isFunction(fCallback))
	{
		this.fCallback = fCallback;
	}
};

CCreateFolderPopup.prototype.onOKClick = function ()
{
	this.folderName.error('');
	
	if (this.fCallback)
	{
		var sError = this.fCallback(this.folderName());
		if (sError)
		{
			this.folderName.error('' + sError);
		}
		else
		{
			this.closePopup();
		}
	}
	else
	{
		this.closePopup();
	}
};

module.exports = new CCreateFolderPopup();