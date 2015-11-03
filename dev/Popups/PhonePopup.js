/**
 * @constructor
 */
function PhonePopup()
{
	this.phone = App.Phone;
	this.action = this.phone.action;
	this.report = this.phone.report;

	this.text = ko.observable('');
	this.callback = null;
}

/**
 * @return {string}
 */
PhonePopup.prototype.popupTemplate = function ()
{
	return 'Popups_PhonePopupViewModel';
};

PhonePopup.prototype.onShow = function (oParameters)
{
	this.text(oParameters.text);
	this.callback = oParameters.Callback || Utils.emptyFunction;
};

PhonePopup.prototype.onCancelClick = function ()
{
	this.closeCommand();
};

PhonePopup.prototype.onOKClick = function ()
{
	this.closeCommand();
	this.callback();
};

PhonePopup.prototype.answer = function ()
{
	this.action(Enums.PhoneAction.IncomingConnect);
};

PhonePopup.prototype.hangup = function ()
{
	this.action(Enums.PhoneAction.Online);
};
