'use strict';

var
	$ = require('jquery'),
	_ = require('underscore'),
	
	Ajax = require('modules/Contacts/js/Ajax.js')
;

/**
 * @param {string} sTerm
 * @param {Function} fResponse
 * @param {string} sExceptEmail
 * @param {boolean} bGlobalOnly
 */
function Callback(sTerm, fResponse, sExceptEmail, bGlobalOnly)
{
	var
		oParameters = {
			'Search': sTerm,
			'GlobalOnly': bGlobalOnly ? '1' : '0'
		}
	;

	Ajax.send('GetSuggestions', oParameters, function (oData) {
		var aList = [];
		if (oData && oData.Result && oData.Result && oData.Result.List)
		{
			aList = _.map(oData.Result.List, function (oItem) {
				return oItem && oItem.Email && oItem.Email !== sExceptEmail ?
				{
					value: (oItem.Name && 0 < $.trim(oItem.Name).length) ? (oItem.ForSharedToAll ? oItem.Name : ('"' + oItem.Name + '" <' + oItem.Email + '>')) : oItem.Email,
					name: oItem.Name,
					email: oItem.Email,
					frequency: oItem.Frequency,
					id: oItem.Id,
					global: oItem.Global,
					sharedToAll: oItem.SharedToAll
				} :
				null;
			});

			aList = _.sortBy(_.compact(aList), function(oItem){
				return oItem.frequency;
			}).reverse();
		}

		fResponse(aList);

	});
};

function DeleteHandler(oContact)
{
	var
		oParameters = {
			'ContactId': oContact.id,
			'SharedToAll': oContact.sharedToAll ? '1' : '0'
		}
	;

	Ajax.send('DeleteSuggestion', oParameters, function (oData) {
		return true;
	}, this);
};


module.exports = {
	callback: Callback,
	deleteHandler: DeleteHandler
};