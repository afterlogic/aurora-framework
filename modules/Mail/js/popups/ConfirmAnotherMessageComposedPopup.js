'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	
	CAbstractPopup = require('modules/Core/js/popups/CAbstractPopup.js')
;

/**
 * @constructor
 */
function CConfirmAnotherMessageComposedPopup()
{
	CAbstractPopup.call(this);
	
	this.fConfirmCallback = null;
	this.shown = false;
}

_.extendOwn(CConfirmAnotherMessageComposedPopup.prototype, CAbstractPopup.prototype);

CConfirmAnotherMessageComposedPopup.prototype.PopupTemplate = 'Mail_ConfirmAnotherMessageComposedPopup';

/**
 * @param {Function} fConfirmCallback
 */
CConfirmAnotherMessageComposedPopup.prototype.onShow = function (fConfirmCallback)
{
	this.fConfirmCallback = $.isFunction(fConfirmCallback) ? fConfirmCallback : null;
	this.shown = true;
};

CConfirmAnotherMessageComposedPopup.prototype.onHide = function ()
{
	this.shown = false;
};

CConfirmAnotherMessageComposedPopup.prototype.onDiscardClick = function ()
{
	if (this.shown && this.fConfirmCallback)
	{
		this.fConfirmCallback(Enums.AnotherMessageComposedAnswer.Discard);
	}

	this.closePopup();
};

CConfirmAnotherMessageComposedPopup.prototype.onSaveAsDraftClick = function ()
{
	if (this.shown && this.fConfirmCallback)
	{
		this.fConfirmCallback(Enums.AnotherMessageComposedAnswer.SaveAsDraft);
	}

	this.closePopup();
};

CConfirmAnotherMessageComposedPopup.prototype.cancelPopup = function ()
{
	if (this.fConfirmCallback)
	{
		this.fConfirmCallback(Enums.AnotherMessageComposedAnswer.Cancel);
	}

	this.closePopup();
};

CConfirmAnotherMessageComposedPopup.prototype.onEnterHandler = function ()
{
	this.onSaveAsDraftClick();
};

module.exports = new CConfirmAnotherMessageComposedPopup();