'use strict';

var
	$ = require('jquery'),
	_ = require('underscore'),
	
	AddressUtils = require('core/js/utils/Address.js'),
	
	Ajax = require('modules/Contacts/js/Ajax.js')
;

/**
 * @param {object} oRequest
 * @param {function} fResponse
 * @param {string} sExceptEmail
 * @param {boolean} bGlobalOnly
 */
function Callback(oRequest, fResponse, sExceptEmail, bGlobalOnly)
{
	var
		sTerm = oRequest.term,
		oParameters = {
			'Search': sTerm,
			'GlobalOnly': bGlobalOnly ? '1' : '0'
		}
	;

	Ajax.send('GetSuggestions', oParameters, function (oResponse) {
		var aList = [];
		if (oResponse && oResponse.Result && oResponse.Result.List)
		{
			aList = _.map(oResponse.Result.List, function (oItem) {
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

/**
 * @param {object} oRequest
 * @param {function} fResponse
 */
function ComposeCallback(oRequest, fResponse)
{
	var
		sTerm = oRequest.term,
		oParameters = { 'Search': sTerm }
	;

	Ajax.send('GetSuggestions', oParameters, function (oResponse) {
		var aList = [];
		if (oResponse && oResponse.Result && oResponse.Result.List)
		{
			aList = _.map(oResponse.Result.List, function (oItem) {
				var
					sLabel = '',
					sValue = oItem.Email
				;

				if (oItem.IsGroup)
				{
					if (oItem.Name && 0 < $.trim(oItem.Name).length)
					{
						sLabel = '"' + oItem.Name + '" (' + oItem.Email + ')';
					}
					else
					{
						sLabel = '(' + oItem.Email + ')';
					}
				}
				else
				{
					sLabel = AddressUtils.getFullEmail(oItem.Name, oItem.Email);
					sValue = sLabel;
				}

				return {
					'label': sLabel,
					'value': sValue,
					'frequency': oItem.Frequency,
					'id': oItem.Id,
					'global': oItem.Global,
					'sharedToAll': oItem.SharedToAll
				};
			});

			aList = _.sortBy(_.compact(aList), function(oItem) {
				return oItem.frequency;
			}).reverse();
		}

		fResponse(aList);

	}, this);
};

/**
 * @param {Object} oContact
 */
function DeleteHandler(oContact)
{
	Ajax.send('DeleteSuggestion', {
		'ContactId': oContact.id,
		'SharedToAll': oContact.sharedToAll ? '1' : '0'
	});
};


module.exports = {
	callback: Callback,
	composeCallback: ComposeCallback,
	deleteHandler: DeleteHandler
};