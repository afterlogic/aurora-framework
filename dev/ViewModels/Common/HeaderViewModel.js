
/**
 * @constructor
 */
function CHeaderViewModel()
{
	CHeaderBaseViewModel.call(this);
	
	this.oPhone = App.Phone ? new CPhoneViewModel() : null;
}

_.extend(CHeaderViewModel.prototype, CHeaderBaseViewModel.prototype);