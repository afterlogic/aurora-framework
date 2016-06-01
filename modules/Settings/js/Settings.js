'use strict';

module.exports = {
	TabsOrder: ['common', 'mail', 'accounts', 'contacts', 'calendar', 'cloud-storage', 'mobile_sync', 'outlook_sync', 'helpdesk', 'pgp'],
	
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.TabsOrder = oAppDataSection.TabsOrder;
		}
	}
};