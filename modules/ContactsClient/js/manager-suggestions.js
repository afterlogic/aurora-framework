'use strict';

module.exports = function (oAppData) {
	require('modules/%ModuleName%/js/enums.js');
	
	if (oSettings)
	{
		var
			_ = require('underscore'),
			
			Settings = require('modules/%ModuleName%/js/Settings.js'),
			oSettings = _.extend({}, oAppData[Settings.ServerModuleName] || {}, oAppData['%ModuleName%'] || {})
		;
		
		Settings.init(oSettings);
	}
	
	var
		SuggestionsAutocomplete = require('modules/%ModuleName%/js/SuggestionsAutocomplete.js')
	;

	return {
		isAvailable: function (iUserRole, bPublic) {
			return !bPublic && iUserRole === Enums.UserRole.PowerUser;
		},
		getSuggestionsAutocompleteCallback: function () {
			return SuggestionsAutocomplete.callback;
		},
		getSuggestionsAutocompleteComposeCallback: function () {
			return SuggestionsAutocomplete.composeCallback;
		},
		getSuggestionsAutocompletePhoneCallback: function () {
			return SuggestionsAutocomplete.phoneCallback;
		},
		getSuggestionsAutocompleteDeleteHandler: function () {
			return SuggestionsAutocomplete.deleteHandler;
		},
		requestUserByPhone: function (sNumber, fCallBack, oContext) {
			SuggestionsAutocomplete.requestUserByPhone(sNumber, fCallBack, oContext);
		}
	};
};
