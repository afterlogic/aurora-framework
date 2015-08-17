
/**
 * @constructor
 */
function CForwardModel()
{
	this.iAccountId = 0;

	this.enable = false;
	this.email = '';
}

/**
 * @param {number} iAccountId
 * @param {Object} oData
 */
CForwardModel.prototype.parse = function (iAccountId, oData)
{
	this.iAccountId = iAccountId;

	this.enable = !!oData.Enable;
	this.email = Utils.pString(oData.Email);
};