'use strict';

var
	ko = require('knockout'),
	$ = require('jquery')
;

/**
 * @constructor
 */
function CCreateFolderPopup()
{
	this.fCallback = null;
	this.folderName = ko.observable('');
	this.folderName.focus = ko.observable(false);
	this.folderName.error = ko.observable('');

	this.folderName.subscribe(function () {
		this.folderName.error('');
	}, this);
}

CCreateFolderPopup.prototype.PopupTemplate = 'Files_CreateFolderPopup';

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
			this.closeCommand();
		}
	}
	else
	{
		this.closeCommand();
	}
};

CCreateFolderPopup.prototype.onCancelClick = function ()
{
	this.closeCommand();
};

module.exports = new CCreateFolderPopup();