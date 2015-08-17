/**
 * @constructor
 */
function PlayerPopup()
{
	this.iframe = ko.observable('');
	//this.closeCallback = null;
}

PlayerPopup.prototype.onShow = function (sIframe)
{
	this.iframe(sIframe);
	//this.closeCallback = fCloseCallback || null;
};

/**
 * @return {string}
 */
PlayerPopup.prototype.popupTemplate = function ()
{
	return 'Popups_PlayerPopupViewModel';
};

PlayerPopup.prototype.onClose = function ()
{
	if (Utils.isFunc(this.closeCallback))
	{
		this.closeCallback();
	}
	this.closeCommand();
	this.iframe('');
};
