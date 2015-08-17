
/**
 * @constructor
 */
function CSieveFiltersModel()
{
	this.iAccountId = 0;
	this.collection = ko.observableArray([]);
}

/**
 * @param {number} iAccountId
 * @param {Object} oData
 */
CSieveFiltersModel.prototype.parse = function (iAccountId, oData)
{
	var 
		iIndex = 0,
		iLen = oData.length,
		oSieveFilter = null
	;

	this.iAccountId = iAccountId;
	
	if (_.isArray(oData))
	{
		for (iLen = oData.length; iIndex < iLen; iIndex++)
		{	
			oSieveFilter =  new CSieveFilterModel(iAccountId);
			oSieveFilter.parse(oData[iIndex]);
			this.collection.push(oSieveFilter);
		}
	}
};