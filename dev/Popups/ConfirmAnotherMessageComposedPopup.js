/**
 * @constructor
 */
function ConfirmAnotherMessageComposedPopup()
{
	this.fConfirmCallback = null;
	this.shown = false;
}

/**
 * @param {Function} fConfirmCallback
 */
ConfirmAnotherMessageComposedPopup.prototype.onShow = function (fConfirmCallback)
{
	if (Utils.isFunc(fConfirmCallback))
	{
		this.fConfirmCallback = fConfirmCallback;
	}
	this.shown = true;
};

ConfirmAnotherMessageComposedPopup.prototype.onHide = function ()
{
	this.shown = false;
};

/**
 * @return {string}
 */
ConfirmAnotherMessageComposedPopup.prototype.popupTemplate = function ()
{
	return 'Popups_ConfirmAnotherMessageComposedPopupViewModel';
};

ConfirmAnotherMessageComposedPopup.prototype.onDiscardClick = function ()
{
	if (this.shown && this.fConfirmCallback)
	{
		this.fConfirmCallback(Enums.AnotherMessageComposedAnswer.Discard);
	}

	this.closeCommand();
};

ConfirmAnotherMessageComposedPopup.prototype.onSaveAsDraftClick = function ()
{
	if (this.shown && this.fConfirmCallback)
	{
		this.fConfirmCallback(Enums.AnotherMessageComposedAnswer.SaveAsDraft);
	}

	this.closeCommand();
};

ConfirmAnotherMessageComposedPopup.prototype.onCancelClick = function ()
{
	if (this.fConfirmCallback)
	{
		this.fConfirmCallback(Enums.AnotherMessageComposedAnswer.Cancel);
	}

	this.closeCommand();
};

ConfirmAnotherMessageComposedPopup.prototype.onEnterHandler = function ()
{
	this.onSaveAsDraftClick();
};

ConfirmAnotherMessageComposedPopup.prototype.onEscHandler = function ()
{
	this.onCancelClick();
};
