'use strict';

module.exports = function (oSettings) {
	require('modules/Contacts/js/enums.js');

	var
		_ = require('underscore'),
		
		App = require('core/js/App.js'),
				
		Settings = require('modules/Contacts/js/Settings.js'),
		SuggestionsAutocomplete = require('modules/Contacts/js/SuggestionsAutocomplete.js'),
		
		ScreensMethods = {},
		SuggestionsMethods = {}
	;

	Settings.init(oSettings);
	
	ScreensMethods = {
		screens: {
			'main': function () {
				return require('modules/Contacts/js/views/CContactsView.js');
			}
		},
		getHeaderItem: function () {
			var
				TextUtils = require('core/js/utils/Text.js'),
				CHeaderItemView = require('core/js/views/CHeaderItemView.js')
			;
			return new CHeaderItemView(TextUtils.i18n('HEADER/CONTACTS'), TextUtils.i18n('TITLE/CONTACTS'));
		}
	};
	
	SuggestionsMethods = {
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
	
	if (App.isNewTab())
	{
		return SuggestionsMethods;
	}
	else
	{
		return _.extend(ScreensMethods, SuggestionsMethods);
	}
};
