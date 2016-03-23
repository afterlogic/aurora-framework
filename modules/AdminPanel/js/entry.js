'use strict';

var $ = require('jquery');

$('body').ready(function () {

	var
		oAvaliableModules = {
		},
		ModulesManager = require('modules/Core/js/ModulesManager.js'),
		App = require('modules/Core/js/App.js')
	;
	
	//App.setNewTab();
	ModulesManager.init(oAvaliableModules);
	App.init();
});
