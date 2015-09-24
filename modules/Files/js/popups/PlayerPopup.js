'use strict';

var ko = require('knockout');

/**
 * @constructor
 */
function CPlayerPopup()
{
	this.iframe = ko.observable('');
}

CPlayerPopup.prototype.PopupTemplate = 'Files_PlayerPopup';

CPlayerPopup.prototype.onShow = function (sIframe)
{
	this.iframe(sIframe);
};

CPlayerPopup.prototype.onClose = function ()
{
	this.closeCommand();
	this.iframe('');
};

module.exports = new CPlayerPopup();