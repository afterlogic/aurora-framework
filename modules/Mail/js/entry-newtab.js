'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'Mail': require('modules/Mail/js/manager-newtab.js'),
			'Contacts': require('modules/Contacts/js/manager-suggestions.js')
		},
		ModulesManager = require('core/js/ModulesManager.js'),
		App = require('core/js/App.js')
	;
	
	App.setNewTab();
	ModulesManager.init(oAvaliableModules);
	App.init();
});
