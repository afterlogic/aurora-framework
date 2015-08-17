
/**
 * @constructor
 */
function CCalendarViewModel()
{
	var self = this;
	this.initialized = ko.observable(false);
	this.isPublic = bExtApp;
	
	this.uploaderArea = ko.observable(null);
	this.bDragActive = ko.observable(false);
	this.bDragActiveComp = ko.computed(function () {
		return this.bDragActive();
	}, this);
	
	this.defaultAccount = (AppData.Accounts) ? AppData.Accounts.getDefault() : null;
	this.todayDate = new Date();
	this.aDayNames = Utils.i18n('DATETIME/DAY_NAMES').split(' ');

	this.popUpStatus = false;
	this.linkRow = 0;
	this.linkColumn = 0;
	
	this.publicCalendarId = (this.isPublic) ? AppData.CalendarPubHash : '';
	this.publicCalendarName = ko.observable('');

	this.timeFormat = (AppData.User.defaultTimeFormat() === Enums.TimeFormat.F24) ? 'HH:mm' : 'hh:mm A';
	this.dateFormat = AppData.User.DefaultDateFormat;

	this.topPositionToday = ko.observable('.fc-widget-content.fc-today');
	this.loadOnce = false;
	this.scrollModel = ko.observable(null);
	this.scrollHeight = 0;
	
	this.dateTitle = ko.observable('');
	this.dateTitleHelper = ko.observableArray(Utils.i18n('DATETIME/MONTH_NAMES').split(' '));
	this.selectedView = ko.observable('');
	this.visibleWeekdayHeader = ko.computed(function () {
		return this.selectedView() === 'month';
	}, this);
	this.selectedView.subscribe(function () {
		this.resize();
	}, this);
	
	this.$calendarGrid = null;
	this.calendarGridDom = ko.observable(null);
	
	this.$datePicker = null;
	this.datePickerDom = ko.observable(null);

	this.calendars = new CCalendarListModel({
		onCalendarCollectionChange: function () {
			self.refreshView();
		},
		onCalendarActiveChange: function () {
			self.refreshView();
		}
	});

	this.colors = [
		'#f09650', 
		'#f68987', 
		'#6fd0ce', 
		'#8fbce2', 
		'#b9a4f5', 
		'#f68dcf', 
		'#d88adc', 
		'#4afdb4', 
		'#9da1ff', 
		'#5cc9c9', 
		'#77ca71', 
		'#aec9c9'
	];
	
	this.busyDays = ko.observableArray([]);
	
	this.$inlineEditedEvent = null;
	this.inlineEditedEventText = null;
	this.checkStarted = ko.observable(false);
	
	this.loaded = false;
	
	this.startDateTime = 0;
	this.endDateTime = 0;
	
	this.needsToReload = false;
	
	this.calendarListClick = function (oItem) {
		oItem.active(!oItem.active());
	};
	this.currentCalendarDropdown = ko.observable(false);
	this.currentCalendarDropdownOffset = ko.observable(0);
	this.calendarDropdownToggle = function (bValue, oElement) {
		if (oElement && bValue)
		{
			var
				position = oElement.position(),
				height = oElement.outerHeight()
			;

			self.currentCalendarDropdownOffset(parseInt(position.top, 10) + height);
		}

		self.currentCalendarDropdown(bValue);
	};
	
	this.dayNamesResizeBinding = _.throttle(_.bind(this.resize, this), 50);

	this.customscrollTop = ko.observable(0);
	this.fullcalendarOptions = {
		handleWindowResize: true,
		eventLimit: 10,
		header: false,
		editable: !this.isPublic,
		selectable: !this.isPublic,
		allDayText: Utils.i18n('CALENDAR/TITLE_ALLDAY'),
		dayNames: this.aDayNames,
		isRTL: Utils.isRTL(),
		scrollTime: moment.duration(8, 'hours'),
		forceEventDuration: true,
		columnFormat: {
			month: 'dddd',  // Monday
			week: 'dddd D', // Monday 7
			day: 'dddd D'	// Monday 7
		},
		titleFormat: {
			month: 'MMMM YYYY',                         // September 2009
			week: "MMMM D[ YYYY]{ '-'[ MMMM] D YYYY}",	// Sep 7 - 13 2009
			day: 'MMMM D, YYYY'							// Tuesday, Sep 8, 2009
		},
		displayEventEnd:  {
			month: true,
			basicWeek: true,
			'default': true
		},
		select: _.bind(this.createEventFromGrid, this),
		eventClick: _.bind(this.eventClickCallback, this),
		eventDragStart: _.bind(this.onEventDragStart, this),
		eventDragStop: _.bind(this.onEventDragStop, this),
		eventResizeStart: _.bind(this.onEventResizeStart, this),
		eventResizeStop: _.bind(this.onEventResizeStop, this),
		eventDrop: _.bind(this.moveEvent, this),
		eventResize: _.bind(this.resizeEvent, this),
		eventAfterRender: _.bind(function(oEv, oEl) {}, this),
		eventAfterAllRender: _.bind(this.updateAllEvents, this),
		viewRender: _.bind(this.viewRenderCallback, this),
		events: _.bind(this.eventsSource, this)
	};

	this.revertFunction = null;
	
	this.calendarSharing = AppData.User.AllowCalendar && AppData.User.CalendarSharing;
	
	this.defaultViewName = ko.computed(function () {
		var 
			viewName = 'month'
		;
		
		switch (AppData.User.CalendarDefaultTab)
		{
			case Enums.CalendarDefaultTab.Day:
				viewName = 'agendaDay';
				break;
			case Enums.CalendarDefaultTab.Week:
				viewName = 'agendaWeek';
				break;
			case Enums.CalendarDefaultTab.Month:
				viewName = 'month';
				break;
		}
		return viewName;
	}, this);
	
	this.iAutoReloadTimer = -1;

	this.dragEventTrigger = false;
	this.delayOnEventResult = false;
	this.delayOnEventResultData = [];
	
	this.refreshView = _.throttle(_.bind(this.refreshViewSingle, this), 100);
	this.defaultCalendarId = ko.computed(function () {
		var 
			defaultCalendar = this.calendars.defaultCal()
		;
		if (defaultCalendar)
		{
			return defaultCalendar.id;
		}
	}, this);
	this.uploadCalendarId = ko.observable('');
	this.changeFullCalendarDate = true;
	this.domScrollWrapper = null;
	this.hotKeysBind();
}
/*
 * Hot keys events
*/
CCalendarViewModel.prototype.hotKeysBind = function ()
{
	var self = this;
	$(document).on('keyup', function(ev) {
		var
			nKey = ev.keyCode
		;
		/* Close popup more if click Esc button */
		if (self.calendars.getEvents().length > 0 && self.selectedView() === 'month'){
			if (nKey === 27 && self.popUpStatus) {
				/* two triggers for correct pluggin working */
				$('body').trigger('click');
				if (!self.popUpStatus){
					$('body').trigger('mousedown');
				}
			}
		}
	});
};


CCalendarViewModel.prototype.getFCObject = function ()
{
	return this.$calendarGrid.fullCalendar('getCalendar');
};

CCalendarViewModel.prototype.getDateFromCurrentView = function (sDateType)
{
	var
		oView = this.$calendarGrid.fullCalendar('getView'),
		oDate = oView && oView[sDateType] ? oView[sDateType] : null
	;
	if (oDate && sDateType === 'end' && oView.name === 'agendaDay')
	{
		oDate.add(1, 'd');
	}
	
	return (oDate && oDate['unix']) ? oView[sDateType]['unix']() : 0;
};

CCalendarViewModel.prototype.eventsSource = function (start, end, timezone, callback)
{
	callback(this.calendars.getEvents(start, end));
};

CCalendarViewModel.prototype.initFullCalendar = function ()
{
	this.$calendarGrid.fullCalendar(this.fullcalendarOptions);
};

CCalendarViewModel.prototype.applyCalendarSettings = function ()
{
	this.timeFormat = (AppData.User.defaultTimeFormat() === Enums.TimeFormat.F24) ? 'HH:mm' : 'hh:mm A';
	this.dateFormat = AppData.User.DefaultDateFormat;

	this.calendarGridDom().removeClass("fc-show-weekends");
	if (AppData.User.CalendarShowWeekEnds)
	{
		this.calendarGridDom().addClass("fc-show-weekends");
	}

	this.fullcalendarOptions.timeFormat = this.timeFormat;
	this.fullcalendarOptions.axisFormat = this.timeFormat;
	this.fullcalendarOptions.defaultView = this.defaultViewName();
	this.fullcalendarOptions.lang = moment.locale();
	
	this.applyFirstDay();

	this.$calendarGrid.fullCalendar('destroy');
	this.$calendarGrid.fullCalendar(this.fullcalendarOptions);
	this.changeView(this.defaultViewName());
};

CCalendarViewModel.prototype.applyFirstDay = function ()
{
	var
		aDayNames = [],
		sFirstDay = '',
		sLastDay = ''
	;

	if (AppData.Auth)
	{
		this.fullcalendarOptions.firstDay = AppData.User.CalendarWeekStartsOn;
	}
	
	_.each(this.aDayNames, function (sDayName) {
		aDayNames.push(sDayName);
	});
	
	switch (AppData.User.CalendarWeekStartsOn)
	{
		case 1:
			sLastDay = aDayNames.shift();
			aDayNames.push(sLastDay);
			break;
		case 6:
			sFirstDay = aDayNames.pop();
			aDayNames.unshift(sFirstDay);
			break;
	}
	
	this.$datePicker.datepicker('option', 'firstDay', AppData.User.CalendarWeekStartsOn);
};

CCalendarViewModel.prototype.initDatePicker = function ()
{
	this.$datePicker.datepicker({
		showOtherMonths: true,
		selectOtherMonths: true,
		monthNames: Utils.getMonthNamesArray(),
		dayNamesMin: Utils.i18n('DATETIME/DAY_NAMES_MIN').split(' '),
		nextText: '',
		prevText: '',
		onChangeMonthYear: _.bind(this.changeMonthYearFromDatePicker, this),
		onSelect: _.bind(this.selectDateFromDatePicker, this),
		beforeShowDay: _.bind(this.getDayDescription, this)
	});
};

CCalendarViewModel.prototype.onApplyBindings = function ()
{
	var self = this;
	this.$calendarGrid = $(this.calendarGridDom());
	this.$datePicker = $(this.datePickerDom());
	if (!this.isPublic) {
		this.initUploader();
	}
	/* Click more links */
	$('body').on('click', function (e) {
		if (self.calendars.getEvents().length > 0 && self.selectedView() === 'month'){
			if ($(e.target).hasClass('fc-more')){
				var $this = $(e.target);
				$('.fc-more-cell.active').removeClass('active');
				$('.fc-row.fc-week.active').removeClass('active');
				$this.closest('.fc-more-cell').addClass('active');
				$this.closest('.fc-row.fc-week').addClass('active');
				var $popup = $('body').find('.fc-popover.fc-more-popover'),
					$parent = $this.closest('tr'),
					$superParent = $this.closest('.fc-day-grid'),
					indexColumn = parseInt($parent.find('.fc-more-cell.active').index('.fc-more-cell')),
					indexRow = parseInt($superParent.find('.fc-row.fc-week.active').index('.fc-row.fc-week'))
				;
				if ($popup.length > 0){
					self.linkRow = indexRow;
					self.linkColumn = indexColumn;
					self.popUpStatus = true;
				} else {
					self.popUpStatus = false;
					self.linkRow = 0;
					self.linkColumn = 0;
				}
			} else if ($(e.target).hasClass('checkmail') || $(e.target).parent().hasClass('checkmail')) {
				e.preventDefault();
			} else {
				self.popUpStatus = false;
				self.linkRow = 0;
				self.linkColumn = 0;
			}
		}
	});
};


CCalendarViewModel.prototype.onShow = function ()
{
	if (!this.initialized())
	{
		this.initDatePicker();

		this.applyCalendarSettings();
		this.highlightWeekInDayPicker();
		this.initialized(true);
	}

	if (App.CalendarCache)
	{
		if (App.CalendarCache.calendarSettingsChanged() || App.CalendarCache.calendarChanged())
		{
			if (App.CalendarCache.calendarSettingsChanged())
			{
				this.applyCalendarSettings();
			}
			App.CalendarCache.calendarSettingsChanged(false);
			App.CalendarCache.calendarChanged(false);
			this.getCalendars();
		}
	}
	else if (this.isPublic)
	{
		this.$calendarGrid.fullCalendar("render");
	}
	this.refetchEvents();
};

CCalendarViewModel.prototype.setTimeline = function () 
{
	var 
		oView = this.$calendarGrid.fullCalendar("getView"),
		now = new Date(),
		nowDate = new Date(now.getFullYear(), now.getMonth(), now.getDate()),
		todayDate = new Date(this.todayDate.getFullYear(), this.todayDate.getMonth(), this.todayDate.getDate()),
		parentDiv = null,
		timeline = null,
		curSeconds = 0,
		percentOfDay = 0,
		topLoc = 0
	;

	if (todayDate < nowDate)
	{// the day has changed
		this.execCommand("today");
		this.todayDate = this.$calendarGrid.fullCalendar("getDate").toDate();
		this.$calendarGrid.fullCalendar("render");
	}
	
	// render timeline
	parentDiv = $(".fc-slats:visible").parent();
	timeline = parentDiv.children(".timeline");
	
	if (timeline.length === 0) 
	{ //if timeline isn't there, add it
		timeline = $("<hr>").addClass("timeline");
		parentDiv.prepend(timeline);
	}
	timeline.css('left', $("td .fc-axis").width() + 10);
	
	timeline.show();
	
/*	
	if (oView.start.toDate() < now && oView.end.toDate() > now)
	{
		timeline.show();
	}
	else
	{
		timeline.hide();
	}
*/
	curSeconds = (now.getHours() * 60 * 60) + (now.getMinutes() * 60) + now.getSeconds();
	percentOfDay = curSeconds / 86400; //24 * 60 * 60 = 86400, % of seconds in a day
	topLoc = Math.floor(parentDiv.height() * percentOfDay);

	timeline.css("top", topLoc + "px");
};

/**
 * @param {Object} oView
 * When all event's rendered
 */
CCalendarViewModel.prototype.updateAllEvents = function (oView)
{
	if (this.calendars.getEvents().length > 0 && this.selectedView() === 'month'){
		if (!this.loadOnce){
			this.topPositionToday.valueHasMutated();
			this.loadOnce = true;
		} else {
			this.scrollModel()['vertical'].set(this.scrollHeight);
		}

		/* open current more link */
		if (this.popUpStatus){
			$('body').find('.fc-row.fc-week').eq(this.linkRow).find('.fc-more-cell').eq(this.linkColumn).find('a.fc-more').click();
		}
	}
};

/**
 * @param {Object} oView
 * @param {Object} oElement
 */
CCalendarViewModel.prototype.viewRenderCallback = function (oView, oElement)
{
	var
		count = 0,
		prevDate = null,
		constDate = "01/01/1971 ",
		timelineInterval
	;
	this.changeDate();
	


	if (!this.loaded)
	{
		this.initResizing();
	}
	
	if(typeof(timelineInterval) !== "undefined")
	{
		window.clearInterval(timelineInterval);
	}
	timelineInterval = window.setInterval(_.bind(function () {
		this.setTimeline();
	}, this), 60000);
	try 
	{
		this.setTimeline();
	} 
	catch(err) { }	
	
	if (oView.name !== 'month' && AppData.User.CalendarShowWorkDay)
	{
		$('.fc-slats tr').each(function() {
			$('tr .fc-time span').each(function() {
				var 
					theValue = $(this).eq(0).text(),
					theDate = (theValue !== '') ? Date.parse(constDate + theValue) : prevDate,
					rangeTimeFrom = Date.parse(constDate + AppData.User.CalendarWorkDayStarts + ':00'),
					rangeTimeTo = Date.parse(constDate + AppData.User.CalendarWorkDayEnds + ':00')
				;
				prevDate = theDate;
				if(theDate < rangeTimeFrom || theDate >= rangeTimeTo)
				{
					$(this).parent().parent().addClass("fc-non-working-time");
					$(this).parent().parent().next().addClass("fc-non-working-time");
				}
			});
		});	
	}
	
	this.activateCustomScrollInDayAndWeekView();
};

CCalendarViewModel.prototype.collectBusyDays = function ()
{
	var 
		aBusyDays = [],
		oStart = null,
		oEnd = null,
		iDaysDiff = 0,
		iIndex = 0
	;

	_.each(this.calendars.getEvents(), function (oEvent) {
		oStart = moment(oEvent.start);
		oEnd = oEvent.end ? moment(oEvent.end) : null;
		if (oEvent.allDay && oEnd)
		{
			oEnd.subtract(1, 'days');
		}
		
		iDaysDiff = oEnd ? oEnd.diff(oStart, 'days') : 0;
		iIndex = 0;

		for (; iIndex <= iDaysDiff; iIndex++)
		{
			aBusyDays.push(oStart.clone().add(iIndex, 'days').toDate());
		}
	}, this);

	this.busyDays(aBusyDays);
};

CCalendarViewModel.prototype.refreshDatePicker = function ()
{
	var self = this;
	
	_.defer(function () {
		self.collectBusyDays();
		self.$datePicker.datepicker('refresh');
		self.highlightWeekInDayPicker();
	});
};

/**
 * @param {Object} oDate
 */
CCalendarViewModel.prototype.getDayDescription = function (oDate)
{
	var
		bSelectable = true,
		oFindedBusyDay = _.find(this.busyDays(), function (oBusyDay) {
			return oBusyDay.getDate() === oDate.getDate() && oBusyDay.getMonth() === oDate.getMonth() &&
				oBusyDay.getYear() === oDate.getYear();
		}, this),
		sDayClass = oFindedBusyDay ? 'day_with_events' : '',
		sDayTitle = ''
	;
	
	return [bSelectable, sDayClass, sDayTitle];
};

CCalendarViewModel.prototype.initResizing = function ()
{
	var fResize = _.throttle(_.bind(this.resize, this), 50);

	$(window).bind('resize', function (e) {
		if (e.target !== this && !App.browser.ie8AndBelow)
		{
			return;
		}

		fResize();
	});

	fResize();
};

CCalendarViewModel.prototype.resize = function ()
{
	var oParent = this.$calendarGrid.parent();
	if (oParent)
	{
		this.$calendarGrid.fullCalendar('option', 'height', oParent.height());
	}
	this.dayNamesResize();
};

CCalendarViewModel.prototype.dayNamesResize = function ()
{
	if (this.selectedView() === 'month')
	{
		var
			oDayNamesHeaderItem = $('div.weekday-header-item'),
			oFirstWeek = $('tr.fc-first td.fc-day'),
			oFirstWeekWidth = $(oFirstWeek[0]).width(),
			iIndex = 0
		;
		
		if (oDayNamesHeaderItem.length === 7 && oFirstWeek.length === 7 && oFirstWeekWidth !== 0)
		{
			for(; iIndex < 7; iIndex++)
			{
				$(oDayNamesHeaderItem[iIndex]).width(oFirstWeekWidth);
			}
		}
	}
};

/**
 * @param {number} iYear
 * @param {number} iMonth
 * @param {Object} oInst
 */
CCalendarViewModel.prototype.changeMonthYearFromDatePicker = function (iYear, iMonth, oInst)
{
	if (this.changeFullCalendarDate)
	{
		var oDate = this.$calendarGrid.fullCalendar('getDate');
		// Date object in javascript and fullcalendar use numbers 0,1,2...11 for monthes
		// datepiker uses numbers 1,2,3...12 for monthes
		oDate
			.month(iMonth - 1)
			.year(iYear);
		this.$calendarGrid.fullCalendar('gotoDate', oDate);
	}
};

/**
 * @param {string} sDate
 * @param {Object} oInst
 */
CCalendarViewModel.prototype.selectDateFromDatePicker = function (sDate, oInst)
{
	var oDate = this.getFCObject().moment(sDate, 'MM/DD/YYYY');
	this.$calendarGrid.fullCalendar('gotoDate', oDate);
	
	_.defer(_.bind(this.highlightWeekInDayPicker, this));
};

CCalendarViewModel.prototype.highlightWeekInDayPicker = function ()
{
	var
		$currentDay = this.$datePicker.find('td.ui-datepicker-current-day'),
		$currentWeek = $currentDay.parent(),
		$currentMonth = this.$datePicker.find('table.ui-datepicker-calendar'),
		oView = this.$calendarGrid.fullCalendar('getView')
	;
	
	switch (oView.name)
	{
		case 'agendaDay':
			$currentMonth.addClass('highlight_day').removeClass('highlight_week');
			break;
		case 'agendaWeek':
			$currentMonth.removeClass('highlight_day').addClass('highlight_week');
			break;
		default:
			$currentMonth.removeClass('highlight_day').removeClass('highlight_week');
			break;
	}
	
	$currentWeek.addClass('current_week');
};

CCalendarViewModel.prototype.changeDateTitle = function ()
{
	var
		oDate = this.$calendarGrid.fullCalendar('getDate'),
		oView = this.$calendarGrid.fullCalendar('getView'),
		sTitle = oDate.format('MMMM YYYY'),
		oStart = oView.intervalStart,
		oEnd = oView.intervalEnd ? oView.intervalEnd.add(-1, 'days') : null
	;
	
	switch (oView.name)
	{
		case 'agendaDay':
			sTitle = oDate.format('MMMM D, YYYY');
			break;
		case 'agendaWeek':
			if (oStart && oEnd)
			{
				sTitle = oStart.format('MMMM D, YYYY') + ' - ' + oEnd.format('MMMM D, YYYY');
			}
			break;
	}
	this.dateTitle(sTitle);
};

CCalendarViewModel.prototype.changeDate = function ()
{
	this.changeDateInDatePicker();
	this.changeDateTitle();
	this.getTimeLimits();
	this.getCalendars();
};

CCalendarViewModel.prototype.changeDateInDatePicker = function ()
{
	var 
		oDateMoment = this.$calendarGrid.fullCalendar('getDate')
	;
	this.changeFullCalendarDate = false;
	this.$datePicker.datepicker('setDate', oDateMoment.local().toDate());
	this.changeFullCalendarDate = true;
	this.highlightWeekInDayPicker();
};

CCalendarViewModel.prototype.activateCustomScrollInDayAndWeekView = function ()
{
	if (bMobileDevice)
	{
		return;
	}
	
	var 
		oView = this.$calendarGrid.fullCalendar('getView'),
		sGridType = oView.name === 'month' ? 'day' : 'time',
		oGridContainer = $('.fc-' + sGridType + '-grid-container'),
		oScrollWrapper = $('<div></div>')
	;

	oGridContainer.parent().append(oScrollWrapper);
	oGridContainer.appendTo(oScrollWrapper);
	
	if (!oScrollWrapper.hasClass('scroll-wrap'))
	{
		oScrollWrapper.attr('data-bind', 'customScrollbar: {x: false, y: true, top: 0, scrollTo: topPositionToday, oScroll: scrollModel}');
		oGridContainer.css({'overflow': 'hidden'}).addClass('scroll-inner');
		ko.applyBindings(this, oScrollWrapper[0]);
		
	}
	this.domScrollWrapper = oScrollWrapper;
};

/**
 * @param {string} sCmd
 * @param {string=} sParam = ''
 */
CCalendarViewModel.prototype.execCommand = function (sCmd, sParam)
{
	if (sParam)
	{
		this.$calendarGrid.fullCalendar(sCmd, sParam);
	}
	else
	{
		this.$calendarGrid.fullCalendar(sCmd);
	}
};

CCalendarViewModel.prototype.displayToday = function ()
{
	this.execCommand('today');
};

CCalendarViewModel.prototype.displayPrev = function ()
{
	this.execCommand('prev');
};

CCalendarViewModel.prototype.displayNext = function ()
{
	this.execCommand('next');
};

CCalendarViewModel.prototype.changeView = function (viewName)
{
	this.selectedView(viewName);
	if (viewName === 'month'){
		this.loadOnce = false;
	}
	this.$calendarGrid.fullCalendar('changeView', viewName);
	
};

CCalendarViewModel.prototype.setAutoReloadTimer = function ()
{
	var self = this;
	clearTimeout(this.iAutoReloadTimer);
	
	if (AppData.User.AutoCheckMailInterval > 0)
	{
		this.iAutoReloadTimer = setTimeout(function () {
			self.getCalendars();
		}, AppData.User.AutoCheckMailInterval * 60 * 1000);
	}
};

CCalendarViewModel.prototype.reloadAll = function ()
{
//	this.startDateTime = 0;
//	this.endDateTime = 0;
	this.needsToReload = true;
	
	this.getCalendars();
};

CCalendarViewModel.prototype.getTimeLimits = function ()
{
	var
		iStart = this.getDateFromCurrentView('start'),
		iEnd = this.getDateFromCurrentView('end')
	;
	
	this.startDateTime = iStart;
	this.endDateTime = iEnd;
	this.needsToReload = true;
	
/*	
	if (this.startDateTime === 0 && this.endDateTime === 0)
	{
		this.startDateTime = iStart;
		this.endDateTime = iEnd;
		this.needsToReload = true;
	}
	else if (iStart < this.startDateTime && iEnd > this.endDateTime)
	{
		this.startDateTime = iStart;
		this.endDateTime = iEnd;
		this.needsToReload = true;
	}
	else if (iStart < this.startDateTime)
	{
		iEnd= this.startDateTime;
		this.startDateTime = iStart;
		this.needsToReload = true;
	}
	else if (iEnd > this.endDateTime)
	{
		iStart = this.endDateTime;
		this.endDateTime = iEnd;
		this.needsToReload = true;
	}
*/	
};

CCalendarViewModel.prototype.getCalendars = function ()
{
	this.checkStarted(true);
	this.setCalendarGridVisibility();	

	App.Ajax.sendExt({
			'Action': 'CalendarList',
			'IsPublic': this.isPublic ? 1 : 0,
			'PublicCalendarId': this.publicCalendarId
		}, this.onCalendarsResponse, this
	);
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CCalendarViewModel.prototype.onCalendarsResponse = function (oData, oParameters)
{
	var
		aCalendarIds = [],
		aNewCalendarIds = [],
		oCalendar = null,
		oClientCalendar = null
	;
	if (this.loadOnce && this.selectedView() === 'month'){
		this.scrollHeight = this.scrollModel()['vertical'].get();
	} else {
		this.scrollHeight = 0;
	}
	if (oData.Result)
	{
		this.loaded = true;

		//sets default calendar aways fist in list
		//oData.Result = _.sortBy(oData.Result, function(oItem){return !oItem.isDefault;});
		_.each(oData.Result, function (oCalendarData) {
			oCalendar = this.calendars.parseCalendar(oCalendarData);
			aCalendarIds.push(oCalendar.id);
			oClientCalendar = this.calendars.getCalendarById(oCalendar.id);
			if (/*this.needsToReload || */!oClientCalendar ||
					(oCalendar && oClientCalendar &&
						oClientCalendar.cTag !== oCalendar.cTag))
			{
				oCalendar = this.calendars.parseAndAddCalendar(oCalendarData);
				if (oCalendar)
				{
					if (this.isPublic)
					{
						App.setTitle(oCalendar.name());
						this.publicCalendarName(oCalendar.name());
					}
					aNewCalendarIds.push(oCalendar.id);
				}
			}
		}, this);


		if (this.calendars.count() === 0 && this.isPublic && this.needsToReload)
		{
			App.setTitle(Utils.i18n('CALENDAR/NO_CALENDAR_FOUND'));
			App.Api.showErrorByCode(0, Utils.i18n('CALENDAR/NO_CALENDAR_FOUND'));
		}

		this.needsToReload = false;
		this.calendars.expunge(aCalendarIds);

		_.each(aCalendarIds, function (sCalendarId){
			oCalendar = this.calendars.getCalendarById(sCalendarId);
			if (oCalendar && oCalendar.eventsCount() > 0)
			{
				oCalendar.reloadEvents();
			}
		}, this);
		this.getEvents(aCalendarIds);
	}
	else
	{
		this.setCalendarGridVisibility();
		this.checkStarted(false);
	}
};

/**
 * @param {Array} aCalendarIds
 */
CCalendarViewModel.prototype.getEvents = function (aCalendarIds)
{
	if (aCalendarIds.length > 0)
	{
//		this.checkStarted(true);
//		if (aCalendarIds.length > 1)
//		{
//			this.$calendarGrid.find('.fc-view div').first().css('visibility', 'hidden');
//		}
		App.Ajax.sendExt({
			'Action': 'CalendarEventList',
			'CalendarIds': JSON.stringify(aCalendarIds),
			'Start': this.startDateTime,
			'End': this.endDateTime,
			'IsPublic': this.isPublic ? 1 : 0,
			'TimezoneOffset': moment().utcOffset(),
			'Timezone': window.jstz ? window.jstz.determine().name() : ''
		}, this.onEventsResponse, this);
	}
	else
	{
		this.setAutoReloadTimer();
		this.checkStarted(false);
	}
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CCalendarViewModel.prototype.onEventsResponse = function (oData, oParameters)
{
	var 
		oCalendar = null,
		aCalendarIds = oParameters.CalendarIds ? JSON.parse(oParameters.CalendarIds) : [],
		aEvents = []
	;

	if (oData.Result)
	{
		_.each(oData.Result, function (oEventData) {
			oCalendar = this.calendars.getCalendarById(oEventData.calendarId);			
			if (oCalendar)
			{
				aEvents.push(oEventData.id);
				var oEvent = oCalendar.eventExists(oEventData.id);
				if (Utils.isUnd(oEvent))
				{
					oCalendar.addEvent(oEventData);
				}
				else if (oEvent.lastModified !== oEventData.lastModified)
				{
					oCalendar.updateEvent(oEventData);
				}
			}
		}, this);
		
		_.each(aCalendarIds, function (sCalendarId){
			oCalendar = this.calendars.getCalendarById(sCalendarId);
			if (oCalendar && oCalendar.eventsCount() > 0 && oCalendar.active())
			{
				oCalendar.expungeEvents(aEvents, this.startDateTime, this.endDateTime);
			}
		}, this);

		this.refreshView();
	}
	
//	this.setCalendarGridVisibility();
	this.setAutoReloadTimer();
	this.checkStarted(false);
};

CCalendarViewModel.prototype.setCalendarGridVisibility = function ()
{
	this.$calendarGrid
		.css('visibility', '')
		.find('.fc-view div')
		.first()
		.css('visibility', '')
	;
};
	
CCalendarViewModel.prototype.getUnusedColor = function ()
{
	var 
		colors = _.difference(this.colors, this.calendars.getColors())
	;
	
	return (colors.length > 0) ? colors[0] :  this.colors[0];
};

CCalendarViewModel.prototype.openCreateCalendarForm = function ()
{
	if (!this.isPublic)
	{
		var 
			oCalendar = new CCalendarModel()
		;
		oCalendar.color(this.getUnusedColor());
		App.Screens.showPopup(CalendarPopup, [_.bind(this.createCalendar, this), this.colors, oCalendar]);
	}
};

/**
 * @param {string} sName
 * @param {string} sDescription
 * @param {string} sColor
 */
CCalendarViewModel.prototype.createCalendar = function (sName, sDescription, sColor)
{
	if (!this.isPublic)
	{
		App.Ajax.send({
				'Name': sName,
				'Description': sDescription,
				'Color': sColor,
				'Action': 'CalendarCreate'
			}, this.onCalendarCreateResponse, this
		);
	}
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CCalendarViewModel.prototype.onCalendarCreateResponse = function (oData, oParameters)
{
	if (oData.Result)
	{
		this.calendars.parseAndAddCalendar(oData.Result);
		//this.calendars.sort();
	}
};

/**
 * @param {Object} oCalendar
 */
CCalendarViewModel.prototype.openImportCalendarForm = function (oCalendar)
{
	if (!this.isPublic)
	{
		App.Screens.showPopup(CalendarImportPopup, [_.bind(this.reloadAll, this), oCalendar]);
	}
};

/**
 * @param {Object} oCalendar
 */
CCalendarViewModel.prototype.openUpdateCalendarForm = function (oCalendar)
{
	if (!this.isPublic)
	{
		App.Screens.showPopup(CalendarPopup, [_.bind(this.updateCalendar, this), this.colors, oCalendar]);
	}
};

/**
 * @param {string} sName
 * @param {string} sDescription
 * @param {string} sColor
 * @param {string} sId
 */
CCalendarViewModel.prototype.updateCalendar = function (sName, sDescription, sColor, sId)
{
	if (!this.isPublic)
	{
		App.Ajax.send({
				'Name': sName,
				'Description': sDescription,
				'Color': sColor,
				'Id': sId,
				'Action': 'CalendarUpdate'
			}, this.onCalendarUpdateResponse, this
		);
	}
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CCalendarViewModel.prototype.onCalendarUpdateResponse = function (oData, oParameters)
{
	var
		oCalendar = null
	;
	if (oData.Result)
	{
		oCalendar = this.calendars.getCalendarById(oParameters.Id);
		if (oCalendar)
		{
			oCalendar.name(oParameters.Name);
			oCalendar.description(oParameters.Description);
			oCalendar.color(oParameters.Color);
			this.refetchEvents();
		}
	}
};

/**
 * @param {string} sColor
 * @param {string} sId
 */
CCalendarViewModel.prototype.updateCalendarColor = function (sColor, sId)
{
	if (!this.isPublic)
	{
		App.Ajax.send({
				'Color': sColor,
				'Id': sId,
				'Action': 'CalendarUpdateColor'
			}, this.onCalendarUpdateColorResponse, this
		);
	}
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CCalendarViewModel.prototype.onCalendarUpdateColorResponse = function (oData, oParameters)
{
	if (oData.Result)
	{
		var oCalendar = this.calendars.getCalendarById(oParameters.Id);
		if (oCalendar)
		{
			oCalendar.color(oParameters.Color);
			this.refetchEvents();
		}
	}
};

/**
 * @param {Object} oCalendar
 */
CCalendarViewModel.prototype.openGetLinkCalendarForm = function (oCalendar, eEvent)
{
	eEvent.preventDefault();
	eEvent.stopPropagation();
	if (!this.isPublic)
	{
		App.Screens.showPopup(CalendarGetLinkPopup, [_.bind(this.publicCalendar, this), oCalendar]);
	}
};

/**
 * @param {Object} oCalendar
 */
CCalendarViewModel.prototype.openShareCalendarForm = function (oCalendar)
{
	if (!this.isPublic)
	{
		App.Screens.showPopup(CalendarSharePopup, [_.bind(this.shareCalendar, this), oCalendar]);
	}
};

/**
 * @param {string} sId
 * @param {boolean} bIsPublic
 * @param {Array} aShares
 * @param {boolean} bShareToAll
 * @param {number} iShareToAllAccess
 */
CCalendarViewModel.prototype.shareCalendar = function (sId, bIsPublic, aShares, bShareToAll, iShareToAllAccess)
{
	if (!this.isPublic)
	{
		App.Ajax.send({
				'Action': 'CalendarShareUpdate',
				'Id': sId,
				'IsPublic': bIsPublic ? 1 : 0,
				'Shares': JSON.stringify(aShares),
				'ShareToAll': bShareToAll ? 1 : 0, 
				'ShareToAllAccess': iShareToAllAccess
			}, this.onCalendarShareUpdateResponse, this
		);
	}
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CCalendarViewModel.prototype.onCalendarShareUpdateResponse = function (oData, oParameters)
{
	if (oData.Result)
	{
		var	oCalendar = this.calendars.getCalendarById(oParameters.Id);
		if (oCalendar)
		{
			oCalendar.shares(JSON.parse(oParameters.Shares));
			if (oParameters.ShareToAll === 1)
			{
				oCalendar.isShared(true);
				oCalendar.isSharedToAll(true);
				oCalendar.sharedToAllAccess = oParameters.ShareToAllAccess;
			}
			else
			{
//				oCalendar.isShared(false);
				oCalendar.isSharedToAll(false);
			}
		}
	}
};

/**
 * @param {string} sId
 * @param {boolean} bIsPublic
 */
CCalendarViewModel.prototype.publicCalendar = function (sId, bIsPublic)
{
	if (!this.isPublic)
	{
		App.Ajax.send({
				'Action': 'CalendarPublicUpdate',
				'Id': sId,
				'IsPublic': bIsPublic ? 1 : 0
			}, this.onCalendarPublicUpdateResponse, this
		);
	}
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CCalendarViewModel.prototype.onCalendarPublicUpdateResponse = function (oData, oParameters)
{
	if (oData.Result)
	{
		var	oCalendar = this.calendars.getCalendarById(oParameters.Id);
		if (oCalendar)
		{
			oCalendar.isPublic(oParameters.IsPublic);
		}
	}
};

/**
 * @param {string} sId
 * @param {boolean} bIsUnsubscribe
 */
CCalendarViewModel.prototype.deleteCalendar = function (sId, bIsUnsubscribe)
{
	var
		oCalendar = this.calendars.getCalendarById(sId),
		sConfirm = oCalendar ?
				bIsUnsubscribe ? Utils.i18n('CALENDAR/CONFIRM_UNSUBSCRIBE_CALENDAR', {'CALENDARNAME': oCalendar.name()}) : Utils.i18n('CALENDAR/CONFIRM_REMOVE_CALENDAR', {'CALENDARNAME' : oCalendar.name()})
			: '',
		fRemove = _.bind(function (bRemove) {
			if (bRemove)
			{
				App.Ajax.send({
						'Id': sId,
						'Action': 'CalendarDelete'
					}, this.onCalendarDeleteResponse, this
				);
			}
		}, this)
	;
	
	if (!this.isPublic && oCalendar)
	{
		App.Screens.showPopup(ConfirmPopup, [sConfirm, fRemove]);
	}	
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CCalendarViewModel.prototype.onCalendarDeleteResponse = function (oData, oParameters)
{
	if (oData.Result)
	{
		var oCalendar = this.calendars.getCalendarById(oParameters.Id);
		if (oCalendar && !oCalendar.isDefault)
		{
			if (this.calendars.currentCal().id === oCalendar.id)
			{
				this.calendars.currentCal(null);
			}

			this.calendars.removeCalendar(oCalendar.id);
			this.refetchEvents();
		}
	}
};

CCalendarViewModel.prototype.onEventDragStart = function ()
{
	this.dragEventTrigger = true;
	this.refreshDatePicker();
};

CCalendarViewModel.prototype.onEventDragStop = function (oEvent)
{
	var self = this;
	this.dragEventTrigger = false;
	if (this.delayOnEventResult && this.delayOnEventResultData && 0 < this.delayOnEventResultData.length)
	{
		this.delayOnEventResult = false;
		
		_.each(this.delayOnEventResultData, function (aData) {
			self.onEventActionResponse(aData[0], aData[1], false);
		});
		
		this.delayOnEventResultData = [];
		this.refreshView();
	}
	else
	{
		this.refreshDatePicker();
	}
};

CCalendarViewModel.prototype.onEventResizeStart = function ()
{
	this.dragEventTrigger = true;
};

CCalendarViewModel.prototype.onEventResizeStop = function ()
{
	var self = this;
	this.dragEventTrigger = false;
	if (this.delayOnEventResult && this.delayOnEventResultData && 0 < this.delayOnEventResultData.length)
	{
		this.delayOnEventResult = false;

		_.each(this.delayOnEventResultData, function (aData) {
			self.onEventActionResponse(aData[0], aData[1], false);
		});

		this.delayOnEventResultData = [];
		this.refreshView();
	}
	else
	{
		this.refreshDatePicker();
	}
};

CCalendarViewModel.prototype.createEventInCurrentCalendar = function ()
{
	this.createEventToday(this.calendars.currentCal());
};

/**
 * @param {string} sCalendarId
 */
CCalendarViewModel.prototype.createEventInCalendar = function (sCalendarId)
{
	this.createEventToday(this.calendars.getCalendarById(sCalendarId));
};

/**
 * @param {Object} oCalendar
 */
CCalendarViewModel.prototype.createEventToday = function (oCalendar)
{
	var oToday = this.getFCObject().moment();
	
	if (oToday.minutes() > 30)
	{
		oToday.add(60 - oToday.minutes(), 'minutes');
	}
	else
	{
		oToday.minutes(30);
	}
	oToday
		.seconds(0)
		.milliseconds(0);
	
	this.openEventPopup(oCalendar, oToday, oToday.clone().add(30, 'minutes'), false);
};

/**
 * @param {Object} oEventData
 */
CCalendarViewModel.prototype.getParamsFromEventData = function (oEventData)
{
	return {
		id: oEventData.id,
		uid: oEventData.uid,
		calendarId: oEventData.calendarId,
		newCalendarId: !Utils.isUnd(oEventData.newCalendarId) ? oEventData.newCalendarId : oEventData.calendarId,
		subject: oEventData.subject,
		allDay: oEventData.allDay ? 1 : 0,
		location: oEventData.location,
		description: oEventData.description,
		alarms: oEventData.alarms ? JSON.stringify(oEventData.alarms) : '[]',
		attendees: oEventData.attendees ? JSON.stringify(oEventData.attendees) : '[]',
		owner: oEventData.owner,
		recurrenceId: oEventData.recurrenceId,
		excluded: oEventData.excluded,
		allEvents: oEventData.allEvents,
		modified: oEventData.modified ? 1 : 0,
		start: oEventData.start.local().toDate(),
		end: oEventData.end.local().toDate(),
		startTS: oEventData.start.unix(),
		endTS: oEventData.end ? oEventData.end.unix() : oEventData.end.unix(),
		rrule: oEventData.rrule ? JSON.stringify(oEventData.rrule) : null
	};
};

/**
 * @param {Array} aParameters
 */
CCalendarViewModel.prototype.getEventDataFromParams = function (aParameters)
{
	var	oEventData = aParameters;
	
	oEventData.alarms = aParameters.alarms ? JSON.parse(aParameters.alarms) : [];
	oEventData.attendees = aParameters.attendees ? JSON.parse(aParameters.attendees) : [];

	if(aParameters.rrule)
	{
		oEventData.rrule = JSON.parse(aParameters.rrule);
	}

	return oEventData;
};

/**
 * @param {Object} oStart
 * @param {Object} oEnd
 */
CCalendarViewModel.prototype.createEventFromGrid = function (oStart, oEnd)
{
	var 
		bAllDay = !oStart.hasTime()
	;
	this.openEventPopup(this.calendars.currentCal(), oStart.local(), oEnd.local(), bAllDay);
};

/**
 * @param {Object} oCalendar
 * @param {Object} oStart
 * @param {Object} oEnd
 * @param {boolean} bAllDay
 */
CCalendarViewModel.prototype.openEventPopup = function (oCalendar, oStart, oEnd, bAllDay)
{
	if (!this.isPublic && oCalendar)
	{
		App.Screens.showPopup(CalendarEventPopup, [{
			CallbackSave: _.bind(this.createEvent, this),
			CallbackDelete: _.bind(this.deleteEvent, this),
			FCMoment: this.getFCObject().moment,
			Calendars: this.calendars,
			SelectedCalendar: oCalendar ? oCalendar.id : 0,
			Start: oStart,
			End: oEnd,
			AllDay: bAllDay,
			TimeFormat: this.timeFormat,
			DateFormat: this.dateFormat,
			CallbackAttendeeActionDecline: _.bind(this.attendeeActionDecline, this)/*,
			Owner: oSelectedCalendar.owner()*/
		}]);
	}
};

/**
 * @param {Object} oEventData
 */
CCalendarViewModel.prototype.createEvent = function (oEventData)
{
	var 
		aParameters = this.getParamsFromEventData(oEventData)
	;

	if (!this.isPublic)
	{
		aParameters.calendarId = oEventData.newCalendarId;
		aParameters.selectStart = this.getDateFromCurrentView('start');
		aParameters.selectEnd = this.getDateFromCurrentView('end');
		aParameters.Action = 'CalendarEventCreate';
		App.Ajax.send(aParameters, this.onEventActionResponseWithSubThrottle, this);
	}
};

/**
 * @param {Object} oEventData
 */
CCalendarViewModel.prototype.eventClickCallback = function (oEventData)
{
	var
		/**
		 * @param {number} iResult
		 */
		fCallback = _.bind(function (iResult) {
			var oParams = {
					ID: oEventData.id,
					Uid: oEventData.uid,
					RecurrenceId: oEventData.recurrenceId,
					Calendars: this.calendars,
					SelectedCalendar: oEventData.calendarId,
					AllDay: oEventData.allDay,
					Location: oEventData.location,
					Description: oEventData.description,
					Subject: oEventData.subject,
					Alarms: oEventData.alarms,
					Attendees: oEventData.attendees,
					RRule: oEventData.rrule ? oEventData.rrule : null,
					Excluded: oEventData.excluded ? oEventData.excluded : false,
					Owner: oEventData.owner,
					Appointment: oEventData.appointment,
					OwnerName: oEventData.ownerName,
					TimeFormat: this.timeFormat,
					DateFormat: this.dateFormat,
					AllEvents: iResult,
					CallbackSave: _.bind(this.updateEvent, this),
					CallbackDelete: _.bind(this.deleteEvent, this),
					CallbackAttendeeActionDecline: _.bind(this.attendeeActionDecline, this)
				}
			;
			if (iResult !== Enums.CalendarEditRecurrenceEvent.None)
			{
				if (iResult === Enums.CalendarEditRecurrenceEvent.AllEvents && oEventData.rrule)
				{
					oParams.Start = moment.unix(oEventData.rrule.startBase);
					oParams.End = moment.unix(oEventData.rrule.endBase);
				}
				else
				{
					oParams.Start = oEventData.start.clone();
					oParams.Start = oParams.Start.local();
					
					oParams.End = oEventData.end.clone();
					oParams.End = oParams.End.local();
				}
				App.Screens.showPopup(CalendarEventPopup, [oParams]);
			}
		}, this)
	;
	
	if (oEventData.rrule)
	{
		if (oEventData.excluded)
		{
			fCallback(Enums.CalendarEditRecurrenceEvent.OnlyThisInstance);
		}
		else
		{
			App.Screens.showPopup(CalendarEditRecurrenceEventPopup, [fCallback]);
		}
	}
	else
	{
		fCallback(Enums.CalendarEditRecurrenceEvent.AllEvents);
	}
};

/**
 * @param {string} sAction
 * @param {Object} oParameters
 * @param {Function=} fRevertFunc = undefined
 */
CCalendarViewModel.prototype.eventAction = function (sAction, oParameters, fRevertFunc)
{
	var oCalendar = this.calendars.getCalendarById(oParameters.calendarId);
	
	if (oCalendar.access() === Enums.CalendarAccess.Read)
	{
		if (fRevertFunc)
		{
			fRevertFunc();		
		}
	}
	else
	{
		if (!this.isPublic)
		{
			if (fRevertFunc)
			{
				this.revertFunction = fRevertFunc;
			}
			
			oParameters.Action = sAction;
			App.Ajax.send(
				oParameters,
				this.onEventActionResponseWithSubThrottle, this
			);
		}
	}
};

/**
 * @param {Object} oEventData
 */
CCalendarViewModel.prototype.updateEvent = function (oEventData)
{
	var 
		oParameters = this.getParamsFromEventData(oEventData)
	;
	
	oParameters.selectStart = this.getDateFromCurrentView('start');
	oParameters.selectEnd = this.getDateFromCurrentView('end');
	
	if (oEventData.modified)
	{
		this.calendars.setDefault(oEventData.newCalendarId);
		this.eventAction('CalendarEventUpdate', oParameters);
	}
};

/**
 * @param {Object} oEventData
 * @param {number} delta
 * @param {Function} revertFunc
 */
CCalendarViewModel.prototype.moveEvent = function (oEventData, delta, revertFunc)
{
/*	oEventData.dayDelta = dayDelta ? dayDelta : 0;
	oEventData.minuteDelta = minuteDelta ? minuteDelta : 0;
*/
	var 
		oParameters = this.getParamsFromEventData(oEventData)
//		iNewStart = oParameters.startTimestamp,
//		iAllEvStart,
//		iAllEvEnd,

//		sConfirm = Utils.i18n('With drag-n-drop you can change the date of this single instance only. To alter the entire series, open the event and change its date.'),
//		fConfirm = _.bind(function (bConfirm) {
//			if (bConfirm)
//			{
//				oParameters.allEvents = Enums.CalendarEditRecurrenceEvent.OnlyThisInstance;
//				this.eventAction('CalendarEventUpdate', oParameters, revertFunc);
//			}
//			else if (revertFunc)
//			{
//				revertFunc();
//			}
//		}, this)
	;
	
	oParameters.selectStart = this.getDateFromCurrentView('start');
	oParameters.selectEnd = this.getDateFromCurrentView('end');
	if (!this.isPublic)
	{
		if (oParameters.rrule)
		{
			revertFunc(false);

/*			iAllEvStart = JSON.parse(oParameters.rrule).startBase;
			iAllEvEnd = JSON.parse(oParameters.rrule).until;

			if (iAllEvStart <= iNewStart && iNewStart <= iAllEvEnd)
			{
				if (oParameters.excluded)
				{
					oParameters.allEvents = Enums.CalendarEditRecurrenceEvent.OnlyThisInstance;
					this.eventAction('CalendarEventUpdate', oParameters, revertFunc);
				}
				else
				{
					App.Screens.showPopup(ConfirmPopup, [sConfirm, fConfirm, '', 'Update this instance']);
				}
			}
			else 
			{
				revertFunc(false);
			}
*/
		}
		else
		{
			oParameters.allEvents = Enums.CalendarEditRecurrenceEvent.AllEvents;
			this.eventAction('CalendarEventUpdate', oParameters, revertFunc);
		}
	}	
	
};

/**
 * @param {Object} oEventData
 * @param {number} delta
 * @param {Function} revertFunc
 */
CCalendarViewModel.prototype.resizeEvent = function (oEventData, delta, revertFunc)
{
	var
		oParameters = this.getParamsFromEventData(oEventData),
		/**
		 * @param {number} iResult
		 */
		fCallback = _.bind(function (iResult) {
			if (iResult !== Enums.CalendarEditRecurrenceEvent.None)
			{
				oParameters.allEvents = iResult;
				this.eventAction('CalendarEventUpdate', oParameters, revertFunc);
			}
			else
			{
				revertFunc();
			}
		}, this)
	;
	
	oParameters.selectStart = this.getDateFromCurrentView('start');
	oParameters.selectEnd = this.getDateFromCurrentView('end');
	if (oEventData.rrule)
	{
		if (oParameters.excluded)
		{
			fCallback(Enums.CalendarEditRecurrenceEvent.OnlyThisInstance);
		}
		else
		{
			App.Screens.showPopup(CalendarEditRecurrenceEventPopup, [fCallback]);
		}
	}
	else
	{
		fCallback(Enums.CalendarEditRecurrenceEvent.AllEvents);
	}
};

/**
 * @param {Object} oEventData
 */
CCalendarViewModel.prototype.deleteEvent = function (oEventData)
{
	this.eventAction('CalendarEventDelete', this.getParamsFromEventData(oEventData));
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 */
CCalendarViewModel.prototype.onEventActionResponseWithSubThrottle = function (oData, oParameters)
{
	if (this.dragEventTrigger)
	{
		this.delayOnEventResult = true;
		this.delayOnEventResultData.push([oData, oParameters]);
	}
	else
	{
		this.onEventActionResponse(oData, oParameters);
	}
};

/**
 * @param {Object} oData
 * @param {Object} oParameters
 * @param {boolean=} bDoRefresh
 */
CCalendarViewModel.prototype.onEventActionResponse = function (oData, oParameters, bDoRefresh)
{
	var
		oCalendar = this.calendars.getCalendarById(oParameters.calendarId),
		oEventData = null,
		oEvent = null,
		iScrollTop = 0
	;
	bDoRefresh = Utils.isUnd(bDoRefresh) ? true : !!bDoRefresh;
	if (oData && oData.Result && !Utils.isUnd(oCalendar))
	{
		iScrollTop = $('.calendar .fc-widget-content .scroll-inner').scrollTop();
		if (oParameters.Action === 'CalendarEventCreate' || oParameters.Action === 'CalendarEventUpdate')
		{
			//this.customscrollTop(parseInt($('.calendar .scroll-inner').scrollTop(), 10));
			oEvent = oCalendar.getEvent(oParameters.id);
			
			if (((!Utils.isUnd(oEvent) && !Utils.isUnd(oEvent.rrule)) || oParameters.rrule) && 
					oParameters.allEvents === Enums.CalendarEditRecurrenceEvent.AllEvents)
			{
				oCalendar.removeEventByUid(oParameters.uid, true);
			}
			else
			{
				oCalendar.removeEvent(oParameters.id);
			}
			
			if (oParameters.newCalendarId && oParameters.newCalendarId !== oParameters.calendarId)
			{
				oCalendar = this.calendars.getCalendarById(oParameters.newCalendarId);			
			}

			_.each(oData.Result.Events, function (oEventData) {
				oCalendar.addEvent(oEventData);
			}, this);
			
			oCalendar.cTag = oData.Result.CTag;
			
			if (!oCalendar.active())
			{
				oCalendar.active(true);
			}

			if (bDoRefresh)
			{
				this.refreshView();
			}

			this.restoreScroll(iScrollTop);
			//this.customscrollTop.valueHasMutated();
			this.calendars.currentCal(oCalendar);
		}
		else if (oParameters.Action === 'CalendarEventDelete')
		{
			oCalendar.cTag = oData.Result; 
			if(oParameters.allEvents === Enums.CalendarEditRecurrenceEvent.OnlyThisInstance)
			{
				oCalendar.removeEvent(oParameters.id);
			}
			else
			{
				oCalendar.removeEventByUid(oParameters.uid);
			}

			if (bDoRefresh)
			{
				this.refreshView();
			}

			this.restoreScroll(iScrollTop);
		}
		else if (oParameters.Action === 'CalendarEventBase')
		{
			oEventData = oData.Result;
			App.Screens.showPopup(CalendarEventPopup, [{
				CallbackSave: _.bind(this.updateEvent, this),
				CallbackDelete: _.bind(this.deleteEvent, this),
				ID: oEventData.id,
				Uid: oEventData.uid,
				RecurrenceId: oEventData.recurrenceId,
				Calendars: this.calendars,
				SelectedCalendar: oEventData.calendarId,
				Start: moment(oEventData.start * 1000),
				End: moment(oEventData.end * 1000),
				AllDay: oEventData.allDay,
				Location: oEventData.location,
				Description: oEventData.description,
				Subject: oEventData.subject,
				Alarms: oEventData.alarms,
				Attendees: oEventData.attendees,
				RRule: oEventData.rrule ? oEventData.rrule : null,
				Excluded: oEventData.excluded ? oEventData.excluded : false,
				Owner: oEventData.owner,
				Appointment: oEventData.appointment,
				TimeFormat: this.timeFormat,
				DateFormat: this.dateFormat,
				AllEvents: Enums.CalendarEditRecurrenceEvent.AllEvents
			}]);
		}
	}
	else if (oParameters.Action === 'CalendarEventUpdate' && !oData.Result &&
		1155 === Utils.pInt(oData.ErrorCode))
	{
		this.revertFunction = null;
	}
	else if (this.revertFunction)
	{
		this.revertFunction();
	}
	
	this.revertFunction = null;
};

/**
 * @param {Object} oCalendar
 * @param {string} sId
 */
CCalendarViewModel.prototype.attendeeActionDecline = function (oCalendar, sId)
{
	oCalendar.removeEvent(sId);
	this.refreshView();
};

CCalendarViewModel.prototype.refetchEvents = function ()
{
	this.$calendarGrid.fullCalendar('refetchEvents');
};

CCalendarViewModel.prototype.refreshViewSingle = function ()
{
	this.refetchEvents();
	this.refreshDatePicker();
};

CCalendarViewModel.prototype.refreshView = function () {};

/**
 * Initializes file uploader.
 */
CCalendarViewModel.prototype.initUploader = function ()
{
	var 
		self = this
	;
	
	if (this.uploaderArea())
	{
		this.oJua = new Jua({
			'action': '?/Upload/Calendars/',
			'name': 'jua-uploader',
			'queueSize': 2,
			'dragAndDropElement': this.uploaderArea(),
			'disableAjaxUpload': false,
			'disableFolderDragAndDrop': false,
			'disableDragAndDrop': false,
			'disableAutoUploadOnDrop': true,
			'hidden': {
				'Token': function () {
					return AppData.Token;
				},
				'AccountID': function () {
					return AppData.Accounts.currentId();
				},
				'AdditionalData':  function () {

					return JSON.stringify({
						'CalendarID': self.uploadCalendarId()
					});
				}
			}
		});

		this.oJua
			.on('onDrop', _.bind(this.onFileDrop, this))
			.on('onComplete', _.bind(this.onFileUploadComplete, this))
			.on('onBodyDragEnter', _.bind(this.bDragActive, this, true))
			.on('onBodyDragLeave', _.bind(this.bDragActive, this, false))
		;
	}
};

CCalendarViewModel.prototype.onFileDrop = function (oFile, oEvent, fProceedUploading) {

	var aEditableCalendars = _.filter(
		this.calendars.collection(),
		function(oItem){
			return oItem.isEditable();
		}
	);

	if (aEditableCalendars.length > 1) {
		App.Screens.showPopup(CalendarSelectCalendarsPopup, [{
			CallbackSave: _.bind(this.uploadToSelectedCalendar, this),
			ProceedUploading: fProceedUploading,
			Calendars: this.calendars,
			EditableCalendars: aEditableCalendars,
			DefaultCalendarId: this.defaultCalendarId()
		}]);
	}
	else
	{
		this.uploadToSelectedCalendar(this.defaultCalendarId(), fProceedUploading);
	}
};

CCalendarViewModel.prototype.onFileUploadComplete = function (sFileUid, bResponseReceived, oResponse)
{
	var bError = !bResponseReceived || !oResponse || oResponse.Error|| oResponse.Result.Error || false;

	if (!bError)
	{
		this.reloadAll();
	}
	else
	{
		if (oResponse.ErrorCode && oResponse.ErrorCode === Enums.Errors.IncorrectFileExtension)
		{
			App.Api.showError(Utils.i18n('CONTACTS/ERROR_INCORRECT_FILE_EXTENSION'));
		}
		else
		{
			App.Api.showError(Utils.i18n('WARNING/ERROR_UPLOAD_FILE'));
		}
	}
};

CCalendarViewModel.prototype.uploadToSelectedCalendar = function (selectedCalendarId, fProceedUploading)
{
	this.uploadCalendarId(selectedCalendarId);
	this.checkStarted(true);
	fProceedUploading();
};

CCalendarViewModel.prototype.restoreScroll = function (iScrollTop)
{
	if (this.domScrollWrapper) {
		this.domScrollWrapper.data('customscroll')['vertical'].set(iScrollTop);
	}
};
