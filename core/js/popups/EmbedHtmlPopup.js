'use strict';

var ko = require('knockout');

/**
 * @constructor
 */
function CEmbedHtmlPopup()
{
	this.htmlEmbed = ko.observable('');
}

CEmbedHtmlPopup.prototype.PopupTemplate = 'Core_EmbedHtmlPopup';

CEmbedHtmlPopup.prototype.onShow = function (sHtmlEmbed)
{
	this.htmlEmbed(sHtmlEmbed);
};

CEmbedHtmlPopup.prototype.onClose = function ()
{
	this.closeCommand();
	this.htmlEmbed('');
};

module.exports = new CEmbedHtmlPopup();