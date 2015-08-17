CScreens.prototype.initScreens = function ()
{
	this.oScreens[Enums.Screens.Information] = {
		'Model': CInformationViewModel,
		'TemplateName': 'Common_InformationViewModel'
	};
	this.oScreens[Enums.Screens.Calendar] = {
		'Model': CCalendarViewModel,
		'TemplateName': 'Calendar_CalendarViewModel'
	};
};

CScreens.prototype.initLayout = function ()
{
	$('#pSevenContent').append($('#CalendarPubLayout').html());
};
