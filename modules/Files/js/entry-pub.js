'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'Files': require('modules/Files/js/manager-pub.js')
		},
		ModulesManager = require('core/js/ModulesManager.js'),
		App = require('core/js/App.js')
	;
	
	ModulesManager.init(oAvaliableModules);
	App.init(true);
});
