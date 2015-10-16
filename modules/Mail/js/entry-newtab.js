'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'Mail': require('modules/Mail/js/manager-newtab.js')
		},
		ModulesManager = require('core/js/ModulesManager.js'),
		App = require('core/js/App.js')
	;
	
	App.setPublic();
	ModulesManager.init(oAvaliableModules);
	App.init();
});
