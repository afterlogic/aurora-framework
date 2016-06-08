'use strict';

var
	ko = require('knockout'),
	
	Types = require('modules/Core/js/utils/Types.js')
;

module.exports = {
	ServerModuleName: 'Files',
	HashModuleName: 'files',
	
	enableModule: ko.observable(true),
	AllowCollaboration: true,
	AllowSharing: true,
	PublicHash: '',
	PublicName: '',
	UploadSizeLimitMb: 0,
	
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.enableModule =  ko.observable(!!oAppDataSection.EnableModule);
			this.AllowCollaboration = !!oAppDataSection.AllowCollaboration;
			this.AllowSharing = !!oAppDataSection.AllowSharing;
			this.PublicHash = Types.pString(oAppDataSection.PublicHash);
			this.PublicName = Types.pString(oAppDataSection.PublicName);
			this.UploadSizeLimitMb = Types.pString(oAppDataSection.UploadSizeLimitMb);
		}
	},
	
	update: function (sEnableModule) {
		this.enableModule(sEnableModule === '1');
	}
};
