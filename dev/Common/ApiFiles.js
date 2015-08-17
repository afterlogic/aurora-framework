/**
 * These methods are not includes in js for mobile version.
 */

CApi.prototype.showPlayer = function (sUrl)
{
	App.Screens.showPopup(PlayerPopup, [sUrl]);
};
