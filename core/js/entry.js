'use strict';

var $ = require('jquery');

$('body').ready(function () {
	var
		App = require('core/js/App.js'),
		oAvaliableModules = {
			'auth': require('modules/Auth/js/manager.js'),
			'mail': require('modules/Mail/js/manager.js'),
			'contacts': require('modules/Contacts/js/manager.js'),
			'settings': require('modules/Settings/js/manager.js')
		}
	;
	
	App.init(oAvaliableModules);
	
	require('core/js/AppTab.js');
});
