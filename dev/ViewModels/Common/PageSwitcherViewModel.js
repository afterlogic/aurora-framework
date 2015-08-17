
/**
 * @constructor
 * @param {number} iCount
 * @param {number} iPerPage
 */
function CPageSwitcherViewModel(iCount, iPerPage)
{
	this.shown = false;
	
	this.currentPage = ko.observable(1);
	this.count = ko.observable(iCount);
	this.perPage = ko.observable(iPerPage);
	this.firstPage = ko.observable(1);
	this.lastPage = ko.observable(1);

	this.pagesCount = ko.computed(function () {
		var iCount = Math.ceil(this.count() / this.perPage());
		return (iCount > 0) ? iCount : 1;
	}, this);

	ko.computed(function () {

		var
			iAllLimit = 20,
			iLimit = 4,
			iPagesCount = this.pagesCount(),
			iCurrentPage = this.currentPage(),
			iStart = iCurrentPage,
			iEnd = iCurrentPage
		;

		if (iPagesCount > 1)
		{
			while (true)
			{
				iAllLimit--;
				
				if (1 < iStart)
				{
					iStart--;
					iLimit--;
				}

				if (0 === iLimit)
				{
					break;
				}

				if (iPagesCount > iEnd)
				{
					iEnd++;
					iLimit--;
				}

				if (0 === iLimit)
				{
					break;
				}

				if (0 === iAllLimit)
				{
					break;
				}
			}
		}

		this.firstPage(iStart);
		this.lastPage(iEnd);
		
	}, this);

	
//	this.firstPage = ko.computed(function () {
//		var iValue = this.currentPage() - this.iLimitAround;
//		return (iValue > 0) ? iValue : 1;
//	}, this);
//
//	this.lastPage = ko.computed(function () {
//		var iValue = this.firstPage() + this.iLimitAround * 2;
//		return (iValue <= this.pagesCount()) ? iValue : this.pagesCount();
//	}, this);

	this.visibleFirst = ko.computed(function () {
		return (this.firstPage() > 1);
	}, this);

	this.visibleLast = ko.computed(function () {
		return (this.lastPage() < this.pagesCount());
	}, this);

	this.clickPage = _.bind(this.clickPage, this);

	this.pages = ko.computed(function () {
		var
			iIndex = this.firstPage(),
			aPages = []
		;

		if (this.firstPage() < this.lastPage())
		{
			for (; iIndex <= this.lastPage(); iIndex++)
			{
				aPages.push({
					number: iIndex,
					current: (iIndex === this.currentPage()),
					clickFunc: this.clickPage
				});
			}
		}

		return aPages;
	}, this);
	
	this.hotKeysBind();
}

CPageSwitcherViewModel.prototype.hotKeysBind = function ()
{
	$(document).on('keydown', $.proxy(function(ev) {
		if (this.shown && !Utils.isTextFieldFocused())
		{
			var sKey = ev.keyCode;
			if (ev.ctrlKey && sKey === Enums.Key.Left)
			{
				this.clickPreviousPage();
			}
			else if (ev.ctrlKey && sKey === Enums.Key.Right)
			{
				this.clickNextPage();
			}
		}
	},this));
};

CPageSwitcherViewModel.prototype.hide = function ()
{
	this.shown = false;
};

CPageSwitcherViewModel.prototype.show = function ()
{
	this.shown = true;
};

CPageSwitcherViewModel.prototype.clear = function ()
{
	this.currentPage(1);
	this.count(0);
};

/**
 * @param {number} iCount
 */
CPageSwitcherViewModel.prototype.setCount = function (iCount)
{
	this.count(iCount);
	if (this.currentPage() > this.pagesCount())
	{
		this.currentPage(this.pagesCount());
	}
};

/**
 * @param {number} iPage
 * @param {number} iPerPage
 */
CPageSwitcherViewModel.prototype.setPage = function (iPage, iPerPage)
{
	this.perPage(iPerPage);
	if (iPage > this.pagesCount())
	{
		this.currentPage(this.pagesCount());
	}
	else
	{
		this.currentPage(iPage);
	}
};

/**
 * @param {Object} oPage
 */
CPageSwitcherViewModel.prototype.clickPage = function (oPage)
{
	var iPage = oPage.number;
	if (iPage < 1)
	{
		iPage = 1;
	}
	if (iPage > this.pagesCount())
	{
		iPage = this.pagesCount();
	}
	this.currentPage(iPage);
};

CPageSwitcherViewModel.prototype.clickFirstPage = function ()
{
	this.currentPage(1);
};

CPageSwitcherViewModel.prototype.clickPreviousPage = function ()
{
	var iPrevPage = this.currentPage() - 1;
	if (iPrevPage < 1)
	{
		iPrevPage = 1;
	}
	this.currentPage(iPrevPage);
};

CPageSwitcherViewModel.prototype.clickNextPage = function ()
{
	var iNextPage = this.currentPage() + 1;
	if (iNextPage > this.pagesCount())
	{
		iNextPage = this.pagesCount();
	}
	this.currentPage(iNextPage);
};

CPageSwitcherViewModel.prototype.clickLastPage = function ()
{
	this.currentPage(this.pagesCount());
};