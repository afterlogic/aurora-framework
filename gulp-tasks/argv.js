var
	_ = require('underscore'),
	fs = require('fs')
;

module.exports = {
	getParameter: function (sParamName, sDefault)
	{
		var sParamValue = sDefault || '';

		_.each(process.argv, function (sArg, iIndex) {
			if (sArg === sParamName && process.argv[iIndex + 1])
			{
				sParamValue = process.argv[iIndex + 1];
			}
		});

		return sParamValue;
	},

	getModules: function ()
	{
		var 
			aModules = [],
			sModules = this.getParameter('--modules')
		;
		
		if (sModules)
		{
			aModules = _.union(['CoreWebclient'], _.compact(sModules.split(',')));
		}
		else
		{
			aModules = fs.readdirSync('modules/');
		}
		
		return aModules;
	}
};
