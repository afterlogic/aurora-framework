'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		oAvaliableModules = {
			'Files': require('modules/FilesClient/js/manager-pub.js')
		},
		ModulesManager = require('modules/Core/js/ModulesManager.js'),
		App = require('modules/Core/js/App.js')
	;
	
	App.setPublic();
	ModulesManager.init(oAvaliableModules, App.getUserRole(), App.isPublic());
	App.init();
});
