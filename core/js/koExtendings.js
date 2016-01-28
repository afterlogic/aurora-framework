'use strict';

var
	ko = require('knockout'),
	
	Types = require('core/js/utils/Types.js')
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
