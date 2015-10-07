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

	});
};

/**
 * @param {object} oRequest
 * @param {function} fResponse
 */
function PhoneCallback(oRequest, fResponse)
{
	var
		sTerm = $.trim(oRequest.term),
		oParameters = {
			'Search': sTerm,
			'PhoneOnly': '1'
		}
	;

	if ('' !== sTerm)
	{
		Ajax.send('GetSuggestions', oParameters, function (oData) {
			var aList = [];

			if (oData && oData.Result && oData.Result.List)
			{
				_.each(oData.Result.List, function (oItem) {
					_.each(oItem.Phones, function (sPhone, sKey) {
						aList.push({
							label: oItem.Name !== '' ? oItem.Name + ' ' + '<' + oItem.Email + '> ' + sPhone : oItem.Email + ' ' + sPhone,
							value: sPhone,
							frequency: oItem.Frequency
						});
					});
				});

				aList = _.sortBy(_.compact(aList), function(num){ return -(num.frequency); });
			}
			
			fResponse(aList);
		});
	}
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
	phoneCallback: PhoneCallback,
	deleteHandler: DeleteHandler
};