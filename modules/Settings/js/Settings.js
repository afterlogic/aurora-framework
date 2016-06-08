'use strict';

module.exports = {
	HashModuleName: 'settings',
	
	TabsOrder: ['common', 'mail', 'mail-accounts', 'contacts', 'calendar', 'files', 'mobilesync', 'outlooksync', 'helpdesk', 'openpgp'],
	
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.TabsOrder = oAppDataSection.TabsOrder;
		}
	}
};