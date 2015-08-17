
/**
 * @constructor
 */
function AppFileStoragePub()
{
	AbstractApp.call(this);
	
	this.init();
}

_.extend(AppFileStoragePub.prototype, AbstractApp.prototype);

AppFileStoragePub.prototype.init = function ()
{
	AppData.User = new CUserSettingsModel();
};

AppFileStoragePub.prototype.authProblem = function ()
{
};

AppFileStoragePub.prototype.run = function ()
{
	this.Screens.init();
	
	this.Screens.showCurrentScreen(Enums.Screens.FileStorage);
};
