/**
 * @constructor
 */
function CreateIdentityPopup()
{
	this.oIdentityPropertiesViewModel = new CIdentityPropertiesViewModel(this, true);
}

/**
 * @return {string}
 */
CreateIdentityPopup.prototype.popupTemplate = function ()
{
	return 'Popups_AccountCreateIdentityPopupViewModel';
};

/**
 * @param {number} iAccountId
 */
CreateIdentityPopup.prototype.onShow = function (iAccountId)
{
	var
		oAccount = AppData.Accounts.getAccount(iAccountId),
		oIdentity = new CIdentityModel()
	;
	
	oIdentity.accountId(iAccountId);
	oIdentity.email(oAccount.email());
	
	this.oIdentityPropertiesViewModel.populate(oIdentity);
	this.oIdentityPropertiesViewModel.friendlyNameHasFocus(true);
};

CreateIdentityPopup.prototype.cancel = function ()
{
	this.closeCommand();
};
