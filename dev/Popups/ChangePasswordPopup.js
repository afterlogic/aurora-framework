/**
 * @constructor
 */
function ChangePasswordPopup()
{
	this.currentPassword = ko.observable('');
	this.newPassword = ko.observable('');
	this.confirmPassword = ko.observable('');
	
	this.isHelpdesk = ko.observable(false);
	
	this.hasOldPassword = ko.observable(false);
}

/**
 * @param {boolean} bHelpdesk
 * @param {boolean} bHasOldPassword
 * @param {Function=} fOnPasswordChangedCallback
 */
ChangePasswordPopup.prototype.onShow = function (bHelpdesk, bHasOldPassword, fOnPasswordChangedCallback)
{
	this.isHelpdesk(bHelpdesk);
	
	this.hasOldPassword(bHasOldPassword);
	
	this.fOnPasswordChangedCallback = fOnPasswordChangedCallback;
	
	this.currentPassword('');
	this.newPassword('');
	this.confirmPassword('');
};

/**
 * @return {string}
 */
ChangePasswordPopup.prototype.popupTemplate = function ()
{
	return 'Popups_ChangePasswordPopupViewModel';
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
ChangePasswordPopup.prototype.onUpdatePasswordResponse = function (oResponse, oRequest)
{
	if (oResponse.Result === false)
	{
		App.Api.showErrorByCode(oResponse, Utils.i18n('SETTINGS/ACCOUNT_PROPERTIES_NEW_PASSWORD_UPDATE_ERROR'));
	}
	else
	{
		if (this.hasOldPassword())
		{
			App.Api.showReport(Utils.i18n('SETTINGS/ACCOUNT_PROPERTIES_CHANGE_PASSWORD_SUCCESS'));
		}
		else
		{
			App.Api.showReport(Utils.i18n('SETTINGS/ACCOUNT_PROPERTIES_SET_PASSWORD_SUCCESS'));
		}
		
		this.closeCommand();
		
		if (typeof this.fOnPasswordChangedCallback === 'function')
		{
			this.fOnPasswordChangedCallback();
		}
		
		App.sResetPassHash = '';
	}
};

ChangePasswordPopup.prototype.onOKClick = function ()
{
	var 
		oParameters = null
	;
	
	if (this.confirmPassword() !== this.newPassword())
	{
		App.Api.showError(Utils.i18n('WARNING/PASSWORDS_DO_NOT_MATCH'));
	}
	else
	{
		if (this.newPassword().length < AppData.App.PasswordMinLength) 
		{ 
			App.Api.showError(Utils.i18n('WARNING/PASSWORDS_MIN_LENGTH_ERROR').replace('%N%', AppData.App.PasswordMinLength));
			
		}
		else if (AppData.App.PasswordMustBeComplex && (!this.newPassword().match(/([0-9])/) || !this.newPassword().match(/([!,%,&,@,#,$,^,*,?,_,~])/)))
		{
			App.Api.showError(Utils.i18n('WARNING/PASSWORD_MUST_BE_COMPLEX'));
		}
		else
		{
			if (this.isHelpdesk())
			{
				oParameters = {
					'Action': 'HelpdeskUserPasswordUpdate',
					'CurrentPassword': this.currentPassword(),
					'NewPassword': this.newPassword()
				};
				App.Ajax.sendExt(oParameters, this.onUpdatePasswordResponse, this);
			}
			else
			{
				oParameters = {
					'Action': 'AccountUpdatePassword',
					'AccountID': AppData.Accounts.editedId(),
					'CurrentIncomingMailPassword': this.currentPassword(),
					'NewIncomingMailPassword': this.newPassword(),
					'Hash': App.sResetPassHash
				};
				App.Ajax.send(oParameters, this.onUpdatePasswordResponse, this);
			}
		}
	}
};

ChangePasswordPopup.prototype.onCancelClick = function ()
{
	this.closeCommand();
};
