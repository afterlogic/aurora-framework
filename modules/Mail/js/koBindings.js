'use strict';

var
	$ = require('jquery'),
	ko = require('knockout'),
	
	ModulesManager = require('core/js/ModulesManager.js'),
	ComposeMessageToAddressesFunc = ModulesManager.run('Mail', 'getComposeMessageToAddresses')
;

if ($.isFunction(ComposeMessageToAddressesFunc))
{
	ko.bindingHandlers.sendMailTo = {
		'update': function (oElement, fValueAccessor, fAllBindingsAccessor, oViewModel, bindingContext) {
			var
				$Element = $(oElement),
				sFullEmail = fValueAccessor()
			;

			$Element.show().addClass('link');
			$Element.click(function () {
				ComposeMessageToAddressesFunc(sFullEmail);
			});
		}
	};
}
