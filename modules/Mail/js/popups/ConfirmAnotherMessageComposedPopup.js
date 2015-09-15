'use strict';

var
	$ = require('jquery')
;

/**
 * @constructor
 */
function CConfirmAnotherMessageComposedPopup()
{
	this.fConfirmCallback = null;
	this.shown = false;
}

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

/**
 * @return {string}
 */
CConfirmAnotherMessageComposedPopup.prototype.popupTemplate = function ()
{
	return 'Popups_ConfirmAnotherMessageComposedPopupViewModel';
};

CConfirmAnotherMessageComposedPopup.prototype.onDiscardClick = function ()
{
	if (this.shown && this.fConfirmCallback)
	{
		this.fConfirmCallback(Enums.AnotherMessageComposedAnswer.Discard);
	}

	this.closeCommand();
};

CConfirmAnotherMessageComposedPopup.prototype.onSaveAsDraftClick = function ()
{
	if (this.shown && this.fConfirmCallback)
	{
		this.fConfirmCallback(Enums.AnotherMessageComposedAnswer.SaveAsDraft);
	}

	this.closeCommand();
};

CConfirmAnotherMessageComposedPopup.prototype.onCancelClick = function ()
{
	if (this.fConfirmCallback)
	{
		this.fConfirmCallback(Enums.AnotherMessageComposedAnswer.Cancel);
	}

	this.closeCommand();
};

CConfirmAnotherMessageComposedPopup.prototype.onEnterHandler = function ()
{
	this.onSaveAsDraftClick();
};

CConfirmAnotherMessageComposedPopup.prototype.onEscHandler = function ()
{
	this.onCancelClick();
};

module.extends = new CConfirmAnotherMessageComposedPopup();