'use strict';

var
	_ = require('underscore'),
	Enums = {}
;

Enums.PhoneAction = {
	'Offline': 'offline',
	'OfflineError': 'offline_error',
	'OfflineInit': 'offline_init',
	'OfflineActive': 'offline_active',
	'Online': 'online',
	'OnlineActive': 'online_active',
	'Incoming': 'incoming',
	'IncomingConnect': 'incoming_connect',
	'Outgoing': 'outgoing',
	'OutgoingConnect': 'outgoing_connect',
	'Settings': 'settings'
};

if (typeof window.Enums === 'undefined')
{
	window.Enums = {};
}

_.extendOwn(window.Enums, Enums);
