'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	$ = require('jquery'),
	
	aDayOfMonthFunctions = [],
	iNowDayOfMonth = ko.observable(moment().date())
;

window.setInterval(function () {
//	todo: helpdesk
//	$('.moment-date-trigger-fast').each(function () {
//		var oItem = ko.dataFor(this);
//		if (oItem && oItem.updateMomentDate)
//		{
//			oItem.updateMomentDate();
//		}
//	});

	iNowDayOfMonth(moment().date());
}, 1000 * 60); // every minute

iNowDayOfMonth.subscribe(function () {
	_.each(aDayOfMonthFunctions, function (fDayOfMonth) {
		fDayOfMonth();
	});
}, this);

module.exports = {
	registerDayOfMonthFunction: function (fDayOfMonth)
	{
		if ($.isFunction(fDayOfMonth))
		{
			aDayOfMonthFunctions.push(fDayOfMonth);
		}
	}
};