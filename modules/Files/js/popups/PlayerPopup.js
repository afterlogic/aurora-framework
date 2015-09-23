'use strict';

var
	ko = require('knockout'),
	$ = require('jquery')
;

/**
 * @constructor
 */
function CPlayerPopup()
{
	this.iframe = ko.observable('');
	//this.closeCallback = null;
}

CPlayerPopup.prototype.onShow = function (sIframe)
{
	this.iframe(sIframe);
	//this.closeCallback = fCloseCallback || null;
};

/**
 * @return {string}
 */
CPlayerPopup.prototype.popupTemplate = function ()
{
	return 'Files_PlayerPopup';
};

CPlayerPopup.prototype.onClose = function ()
{
	if ($.isFunction(this.closeCallback))
	{
		this.closeCallback();
	}
	this.closeCommand();
	this.iframe('');
};

module.exports = new CPlayerPopup();