var
	_ = require('underscore')
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
		var sModules = this.getParameter('--modules');

		return _.union(['CoreWebclient'], sModules.split(','));
	}
};
