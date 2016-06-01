'use strict';

var Types = require('modules/Core/js/utils/Types.js');

module.exports = {
	Plugin32DownloadLink: '',
	Plugin64DownloadLink: '',
	PluginReadMoreLink: '',
	
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.Plugin32DownloadLink = Types.pString(oAppDataSection.Plugin32DownloadLink);
			this.Plugin64DownloadLink = Types.pString(oAppDataSection.Plugin64DownloadLink);
			this.PluginReadMoreLink = Types.pString(oAppDataSection.PluginReadMoreLink);
		}
	}
};
