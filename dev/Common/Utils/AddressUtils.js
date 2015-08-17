/**
 * Used in AfterLogicApi, OpenPgpKey, Inputosaurus, CComposeViewModel, CHtmlEditorViewModel, CalendarSharePopup.
 * Included in main, mobile, helpdesk, calendar_pub, filestorage_pub applications.
 * 
 * @requires jquery
 * @requires underscore
 */

Utils.Address = {};

/**
 * Checks if specified email is correct.
 * Used in AfterLogicApi, CHtmlEditorViewModel.
 * 
 * @param {string} sValue String to check.
 * 
 * @return {boolean}
 */
Utils.Address.isCorrectEmail = function (sValue)
{
	return !!(sValue.match(/^[A-Z0-9\"!#\$%\^\{\}`~&'\+\-=_\.]+@[A-Z0-9\.\-]+$/i));
};

/**
 * Used in CAccountModel, CAddressModel, CIdentityModel, CContactListItemModel, CContactModel, CFetcherModel, CHelpdeskViewModel, CComposeViewModel.
 * 
 * @param {string} sName
 * @param {string} sEmail
 * @returns {string}
 */
Utils.Address.getFullEmail = function (sName, sEmail)
{
	var sFull = '';
	
	if (sEmail.length > 0)
	{
		if (sName.length > 0)
		{
			if (Utils.Address.isCorrectEmail(sName) || sName.indexOf(',') !== -1)
			{
				sFull = '"' + sName + '" <' + sEmail + '>';
			}
			else
			{
				sFull = sName + ' <' + sEmail + '>';
			}
		}
		else
		{
			sFull = sEmail;
		}
	}
	else
	{
		sFull = sName;
	}
	
	return sFull;
};

/**
 * Obtains Recipient-object which include "name", "email" and "fullEmail" fields from string.
 * Used in AfterLogicApi, OpenPgpKey, Inputosaurus, CComposeViewModel.
 * 
 * @param {string} sFullEmail String includes only name, only email or both name and email.
 * @param {boolean} bIgnoreQuotesInName
 *
 * @return {Object}
 */
Utils.Address.getEmailParts = function (sFullEmail, bIgnoreQuotesInName)
{
	var
		iQuote1Pos = sFullEmail.indexOf('"'),
		iQuote2Pos = sFullEmail.indexOf('"', iQuote1Pos + 1),
		iLeftBrocketPos = sFullEmail.indexOf('<', iQuote2Pos),
		iPrevLeftBroketPos = -1,
		iRightBrocketPos = -1,
		sName = '',
		sEmail = ''
	;

	while (iLeftBrocketPos !== -1)
	{
		iPrevLeftBroketPos = iLeftBrocketPos;
		iLeftBrocketPos = sFullEmail.indexOf('<', iLeftBrocketPos + 1);
	}

	iLeftBrocketPos = iPrevLeftBroketPos;
	iRightBrocketPos = sFullEmail.indexOf('>', iLeftBrocketPos + 1);

	if (iLeftBrocketPos === -1)
	{
		sEmail = $.trim(sFullEmail);
	}
	else
	{
		iQuote1Pos = bIgnoreQuotesInName ? -1 : iQuote1Pos;
		sName = (iQuote1Pos === -1) ?
			$.trim(sFullEmail.substring(0, iLeftBrocketPos)) :
			$.trim(sFullEmail.substring(iQuote1Pos + 1, iQuote2Pos));

		sEmail = $.trim(sFullEmail.substring(iLeftBrocketPos + 1, iRightBrocketPos));
	}

	return {
		'name': sName,
		'email': sEmail,
		'fullEmail': Utils.Address.getFullEmail(sName, sEmail)
	};
};

/**
 * Obtains list of Recipient-objects which include "name", "email" and "fullEmail" fields from string.
 * Used in AfterLogicApi, CalendarSharePopup, Inputosaurus.
 * 
 * @param {string} sRecipients Includes recipients, separated by separators.
 * @param {boolean} bIncludeLastIncorrectEmail If true, last recipient will be included to list, even if it is not correct email.
 * 
 * @returns {Array}
 */
Utils.Address.getArrayRecipients = function (sRecipients, bIncludeLastIncorrectEmail)
{
	var
		aSeparators = [',', ';', ' '],
		sStartRcp = '',
		sEndRcp = sRecipients,
		iPos = 0,
		iNextPos = 0,
		sFullEmail = '',
		oRecipient = null,
		aRecipients = []
	;
	
	while (sEndRcp.length > 0)
	{
		iPos = Utils.Address._getFirstSeparatorPosition(sEndRcp, aSeparators);
		iNextPos = iPos;
		
		while (_.indexOf(aSeparators, sEndRcp[iNextPos + 1]) !== -1)
		{
			iNextPos++;
		}
		
		if (iPos === -1)
		{
			sFullEmail = sStartRcp + sEndRcp;
			oRecipient = Utils.Address.getEmailParts(sFullEmail);
			if (bIncludeLastIncorrectEmail || Utils.Address.isCorrectEmail(oRecipient.email))
			{
				aRecipients.push(oRecipient);
			}
			sEndRcp = '';
		}
		else
		{
			sFullEmail = sStartRcp + sEndRcp.substring(0, iPos);
			oRecipient = Utils.Address.getEmailParts(sFullEmail);
			if (Utils.Address.isCorrectEmail(oRecipient.email))
			{
				aRecipients.push(oRecipient);
				sStartRcp = '';
			}
			else
			{
				sStartRcp += sEndRcp.substring(0, iNextPos + 1);
			}
			sEndRcp = sEndRcp.substring(iNextPos + 1);
		}
	}
	
	return aRecipients;
};

/**
 * Obtains position number of first separator-symbol in string. Avaliable separator symbols are specified in array.
 * 
 * @param {string} sValue String for search separator-symbol in.
 * @param {Array} aSeparators List of separators.
 * @returns {number}
 */
Utils.Address._getFirstSeparatorPosition = function (sValue, aSeparators)
{
	var iPos = -1;

	_.each(aSeparators, function (sSep) {
		var iSepPos = sValue.indexOf(sSep);
		if (iSepPos !== -1 && (iPos === -1 || iSepPos < iPos))
		{
			iPos = iSepPos;
		}
	});

	return iPos;
};

/**
 * Used in CComposeViewModel.
 * 
 * @param {string} sAddresses
 * 
 * @return {Array}
 */
Utils.Address.getIncorrectEmailsFromAddressString = function (sAddresses)
{
	var
		aEmails = sAddresses.replace(/"[^"]*"/g, '').replace(/;/g, ',').split(','),
		aIncorrectEmails = [],
		iIndex = 0,
		iLen = aEmails.length,
		sFullEmail = '',
		oEmailParts = null
	;

	for (; iIndex < iLen; iIndex++)
	{
		sFullEmail = $.trim(aEmails[iIndex]);
		if (sFullEmail.length > 0)
		{
			oEmailParts = Utils.Address.getEmailParts($.trim(aEmails[iIndex]));
			if (!Utils.Address.isCorrectEmail(oEmailParts.email))
			{
				aIncorrectEmails.push(oEmailParts.email);
			}
		}
	}

	return aIncorrectEmails;
};
