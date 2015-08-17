
/**
 * @constructor
 */
function AppCalendarPub()
{
	AbstractApp.call(this);

	this.init();
}

_.extend(AppCalendarPub.prototype, AbstractApp.prototype);

AppCalendarPub.prototype.init = function ()
{
	var
		oRawUserSettings = /** @type {Object} */ AppData['User'],
		oUserSettings = new CUserSettingsModel()
	;

	oUserSettings.parse(oRawUserSettings);
	AppData.User = oUserSettings;

	if (!AppData.Auth)
	{
		AppData.User.CalendarWeekStartsOn = parseInt(moment().weekday(0).format("d"));
	}
};

AppCalendarPub.prototype.authProblem = function ()
{
};

AppCalendarPub.prototype.run = function ()
{
	this.Screens.init();
	
	this.Screens.showCurrentScreen(Enums.Screens.Calendar);
};
