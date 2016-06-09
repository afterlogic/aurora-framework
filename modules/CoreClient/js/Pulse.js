'use strict';

var
	_ = require('underscore'),
	$ = require('jquery'),
	ko = require('knockout'),
	moment = require('moment'),
	
	aEveryMinuteFunctions = [],
	aDayOfMonthFunctions = [],
	koNowDayOfMonth = ko.observable(moment().date())
;

window.setInterval(function () {
	_.each(aEveryMinuteFunctions, function (fEveryMinute) {
		fEveryMinute();
	});
	
	koNowDayOfMonth(moment().date());
}, 1000 * 60); // every minute

koNowDayOfMonth.subscribe(function () {
	_.each(aDayOfMonthFunctions, function (fDayOfMonth) {
		fDayOfMonth();
	});
}, this);

module.exports = {
	registerEveryMinuteFunction: function (fEveryMinute)
	{
		if ($.isFunction(fEveryMinute))
		{
			aEveryMinuteFunctions.push(fEveryMinute);
		}
	},
	registerDayOfMonthFunction: function (fDayOfMonth)
	{
		if ($.isFunction(fDayOfMonth))
		{
			aDayOfMonthFunctions.push(fDayOfMonth);
		}
	}
};