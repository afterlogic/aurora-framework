'use strict';

var $ = require('jquery');

$('body').ready(function () {

	var
		oAvaliableModules = {
		},
		ModulesManager = require('modules/CoreClient/js/ModulesManager.js'),
		App = require('modules/CoreClient/js/App.js')
	;
	
	//App.setNewTab();
	ModulesManager.init(oAvaliableModules, App.getUserRole(), App.isPublic());
	App.init();
});
