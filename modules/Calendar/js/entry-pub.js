'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
//			'Contacts': require('modules/Contacts/js/manager.js'),
			'Calendar': require('modules/Calendar/js/manager-pub.js')
		},
		ModulesManager = require('core/js/ModulesManager.js'),
		App = require('core/js/App.js')
	;
	
	App.setPublic();
	ModulesManager.init(oAvaliableModules);
	App.init();
});
