'use strict';

var
	_ = require('underscore'),
			
	CAbstractPopup = require('core/js/popups/CAbstractPopup.js'),
	
	AccountList = require('modules/Mail/js/AccountList.js'),
	
	CIdentityModel = require('modules/Mail/js/models/CIdentityModel.js'),
	CIdentityPropertiesPaneView = require('modules/Mail/js/views/settings/CIdentityPropertiesPaneView.js')
;

/**
 * @constructor
 */
function CCreateIdentityPopup()
{
	CAbstractPopup.call(this);
	
	this.oIdentityPropertiesViewModel = new CIdentityPropertiesPaneView(this, true);
}

_.extendOwn(CCreateIdentityPopup.prototype, CAbstractPopup.prototype);

CCreateIdentityPopup.prototype.PopupTemplate = 'Mail_Settings_CreateIdentityPopup';

/**
 * @param {number} iAccountId
 */
CCreateIdentityPopup.prototype.onShow = function (iAccountId)
{
	var
		oAccount = AccountList.getAccount(iAccountId),
		oIdentity = new CIdentityModel()
	;
	
	oIdentity.accountId(iAccountId);
	oIdentity.email(oAccount.email());
	
	this.oIdentityPropertiesViewModel.populate(oIdentity);
	this.oIdentityPropertiesViewModel.friendlyNameHasFocus(true);
};

module.exports = new CCreateIdentityPopup();
