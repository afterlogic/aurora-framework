/**
 * These methods are not includes in js for mobile version.
 */

CApi.prototype.contactCreate = function (sName, sEmail, fContactCreateResponse, oContactCreateContext)
{
	App.Screens.showPopup(ContactCreatePopup, [sName, sEmail, fContactCreateResponse, oContactCreateContext]);
};
