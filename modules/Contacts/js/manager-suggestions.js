'use strict';

module.exports = function (oSettings) {
	require('modules/Contacts/js/enums.js');
	
	if (oSettings)
	{
		var Settings = require('modules/Contacts/js/Settings.js');
		Settings.init(oSettings);
	}
	
	var
		SuggestionsAutocomplete = require('modules/Contacts/js/SuggestionsAutocomplete.js')
	;

	return {
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
