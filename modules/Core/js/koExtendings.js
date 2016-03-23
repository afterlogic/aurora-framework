'use strict';

var
	ko = require('knockout'),
	
	Types = require('modules/Core/js/utils/Types.js')
;

/**
 * @param {Object} oTarget
 * @returns {Object}
 */
ko.extenders.reversible = function (oTarget)
{
	var mValue = oTarget();

	oTarget.commit = function ()
	{
		mValue = oTarget();
	};

	oTarget.revert = function ()
	{
		oTarget(mValue);
	};

	oTarget.commitedValue = function ()
	{
		return mValue;
	};

	oTarget.changed = function ()
	{
		return mValue !== oTarget();
	};
	
	return oTarget;
};

/**
 * @param {Object} oTarget
 * @param {Object} iOption
 * @returns {Object}
 */
ko.extenders.autoResetToFalse = function (oTarget, iOption)
{
	oTarget.iTimeout = 0;
	oTarget.subscribe(function (bValue) {
		if (bValue)
		{
			window.clearTimeout(oTarget.iTimeout);
			oTarget.iTimeout = window.setTimeout(function () {
				oTarget.iTimeout = 0;
				oTarget(false);
			}, Types.pInt(iOption));
		}
	});

	return oTarget;
};

//calendar
ko.extenders.disableLinebreaks = function (oTarget, bDisable) {
	if (bDisable)
	{
		var oResult = ko.computed({
			'read': function () {
				return oTarget();
			},
			'write': function(sNewValue) {
				oTarget(sNewValue.replace(/[\r\n\t]+/gm, ' '));
			}
		});
		oResult(oTarget());
		return oResult;
	}
	return oTarget;
};
