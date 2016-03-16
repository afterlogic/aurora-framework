'use strict';

var $ = require('jquery');

$('body').ready(function () {

	var
		oAvaliableModules = {
		},
		ModulesManager = require('core/js/ModulesManager.js'),
		App = require('core/js/App.js')
	;
	
	//App.setNewTab();
	ModulesManager.init(oAvaliableModules);
	App.init();
});
