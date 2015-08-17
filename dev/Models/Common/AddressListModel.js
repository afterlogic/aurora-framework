
/**
 * @constructor
 */
function CAddressListModel()
{
	this.aCollection = [];
}

/**
 * @param {Array} aData
 */
CAddressListModel.prototype.parse = function (aData)
{
	this.aCollection = _.map(aData, function (oItem) {
		var oAddress = new CAddressModel();
		oAddress.parse(oItem);
		return oAddress;
	});
};

/**
 * @param {Array} aCollection
 */
CAddressListModel.prototype.addCollection = function (aCollection)
{
	_.each(aCollection, function (oAddress) {
		var oFoundAddress = _.find(this.aCollection, function (oThisAddress) {
			return oAddress.sEmail === oThisAddress.sEmail;
		});
		
		if (!oFoundAddress)
		{
			this.aCollection.push(oAddress);
		}
	}, this);
};

/**
 * @param {Array} aCollection
 */
CAddressListModel.prototype.excludeCollection = function (aCollection)
{
	_.each(aCollection, function (oAddress) {
		this.aCollection = _.filter(this.aCollection, function (oThisAddress) {
			return oAddress.sEmail !== oThisAddress.sEmail;
		});
	}, this);
};

/**
 * @return {string}
 */
CAddressListModel.prototype.getFirstEmail = function ()
{
	if (this.aCollection.length > 0)
	{
		return this.aCollection[0].getEmail();
	}
	
	return '';
};

/**
 * @return {string}
 */
CAddressListModel.prototype.getFirstName = function ()
{
	if (this.aCollection.length > 0)
	{
		return this.aCollection[0].getName();
	}
	
	return '';
};

/**
 * @return {string}
 */
CAddressListModel.prototype.getFirstDisplay = function ()
{
	if (this.aCollection.length > 0)
	{
		return this.aCollection[0].getDisplay();
	}
	
	return '';
};

/**
 * @param {string=} sMeReplacement
 * @param {string=} sMyAccountEmail
 * 
 * @return {string}
 */
CAddressListModel.prototype.getDisplay = function (sMeReplacement, sMyAccountEmail)
{
	var aAddresses = _.map(this.aCollection, function (oAddress) {
		if (sMeReplacement && sMyAccountEmail === oAddress.sEmail)
		{
			return sMeReplacement;
		}
		return oAddress.getDisplay(sMeReplacement);
	});
	
	return aAddresses.join(', ');
};

/**
 * @return {string}
 */
CAddressListModel.prototype.getFull = function ()
{
	var aAddresses = _.map(this.aCollection, function (oAddress) {
		return oAddress.getFull();
	});
	
	return aAddresses.join(', ');
};

/**
 * @return {Array}
 */
CAddressListModel.prototype.getEmails = function ()
{
	var aEmails = _.map(this.aCollection, function (oAddress) {
		return oAddress.getEmail();
	});
	
	return aEmails;
};
