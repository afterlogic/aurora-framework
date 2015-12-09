'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	CAbstractPopup = require('core/js/popups/CAbstractPopup.js')
;

/**
 * @constructor
 */
function CEmbedHtmlPopup()
{
	CAbstractPopup.call(this);
	
	this.htmlEmbed = ko.observable('');
}

_.extendOwn(CEmbedHtmlPopup.prototype, CAbstractPopup.prototype);

CEmbedHtmlPopup.prototype.PopupTemplate = 'Core_EmbedHtmlPopup';

CEmbedHtmlPopup.prototype.onShow = function (sHtmlEmbed)
{
	this.htmlEmbed(sHtmlEmbed);
};

CEmbedHtmlPopup.prototype.onClose = function ()
{
	this.closePopup();
	this.htmlEmbed('');
};

module.exports = new CEmbedHtmlPopup();