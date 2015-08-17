
/**
 * @constructor
 */
function CHelpdeskHeaderViewModel()
{
	this.sThreadsHash = App.Routing.buildHashFromArray([Enums.Screens.Helpdesk]);
	this.settingsHash = App.Routing.lastSettingsHash;
}

CHelpdeskHeaderViewModel.prototype.logout = function ()
{
	App.logout();
};