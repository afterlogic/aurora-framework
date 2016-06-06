'use strict';

var
	_ = require('underscore'),
			
	CAbstractPopup = require('modules/Core/js/popups/CAbstractPopup.js'),
	
	AccountList = require('modules/%ModuleName%/js/AccountList.js'),
	
	CIdentityModel = require('modules/%ModuleName%/js/models/CIdentityModel.js'),
	CIdentityPropertiesPaneView = require('modules/%ModuleName%/js/views/settings/CIdentityPropertiesPaneView.js')
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
