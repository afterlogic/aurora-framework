CScreens.prototype.initScreens = function ()
{
	this.oScreens[Enums.Screens.Information] = {
		'Model': CInformationViewModel,
		'TemplateName': 'Common_InformationViewModel'
	};
	this.oScreens[Enums.Screens.FileStorage] = {
		'Model': CFileStorageViewModel,
		'TemplateName': 'FileStorage_FileStorageViewModel'
	};
};

CScreens.prototype.initLayout = function ()
{
	$('#pSevenContent').append($('#FileStoragePubLayout').html());
};
