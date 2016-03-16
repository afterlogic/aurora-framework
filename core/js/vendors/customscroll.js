(function ($) {
	// Augment jQuery prototype.
	$.fn.customscroll = function (options) {
		return this.each(function () {
			if ($(this).data('customscroll'))
			{
				$(this).data('customscroll').destroy();
			}

			$(this).data('customscroll', new $.Customscroll(this, options));
		});
	};

	// Expose constructor.
	$.Customscroll = Customscroll;

	// Customscroll pane constructor.
	function Customscroll (el, opts) {
		this.el = $(el);
		this.options = opts || {};

		this.x = false !== this.options.x;
		this.y = false !== this.options.y;
		this.padding = undefined === this.options.padding ? 2 : this.options.padding;
		this.relativeToInner = undefined === this.options.relativeToInner ? false : !!this.options.relativeToInner;

		this.inner = this.el.find('.scroll-inner');

		var css = {}, scrollWidth = getBrowserScrollbarWidth();

		if (this.x)
		{
			css['padding-bottom'] = scrollWidth;
			css['overflow-x'] = 'scroll';
		}
		else
		{
			css['overflow-x'] = 'hidden';
		}
		
		if (this.y)
		{
			if (this.el.css('direction') === 'rtl')
			{
				css['margin-left'] = -scrollWidth;
			}
			else
			{
				css['margin-right'] = -scrollWidth;
			}
			css['overflow-y'] = 'scroll';
		}
		else
		{
			css['overflow-y'] = 'hidden';
		}
		this.inner.css(css);
	
		this.refresh();
	};

	// refresh scrollbars
	Customscroll.prototype.refresh = function() {
		 var
			needVScroll = true,
			needHScroll = true
		 ;
	
		if (!this.horizontal && needHScroll && this.x)
		{
			this.horizontal = new Scrollbar.Horizontal(this);
		}
		else if (this.horizontal && !needHScroll)
		{
			this.horizontal.destroy();
			this.horizontal = null;
		}

		if (!this.vertical && needVScroll && this.y)
		{
			this.vertical = new Scrollbar.Vertical(this);
		}
		else if (this.vertical && !needVScroll)
		{
			this.vertical.destroy();
			this.vertical = null;
		}
	};
	
	// Cleans up.
	Customscroll.prototype.reset = function () {
		if (this.vertical)
		{
			this.vertical.set(0);
		}
		if (this.horizontal)
		{
			this.horizontal.set(0);
		}
	};

	// Cleans up.
	Customscroll.prototype.destroy = function () {
		if (this.horizontal)
		{
			this.horizontal.destroy();
		}
		if (this.vertical)
		{
			this.vertical.destroy();
		}
		return this;
	};

	// Rebuild Customscroll.
	Customscroll.prototype.rebuild = function () {
		this.destroy();
		this.inner.attr('style', '');
		Customscroll.call(this, this.el, this.options);
		return this;
	};

	Customscroll.prototype.scrollToTop = function () {
		this.reset();
		return this;
	};
	
	Customscroll.prototype.scrollToBottom = function () {
		if (this.vertical)
		{
			this.vertical.set(10000);
		}
		if (this.horizontal)
		{
			this.horizontal.set(0);
		}
		return this;
	};

	Customscroll.prototype.scrollTo = function (sSelector) {
		var aPosition = {
			'top': $(sSelector).offset().top - this.el.offset().top,
			'left': 0
		};
		if (this.vertical && aPosition.top !== undefined)
		{
			this.vertical.set(this.vertical.get() + aPosition.top);
		}
		if (this.horizontal)
		{
			this.horizontal.set(0);
		}
		return this;
	};

	// Scrollbar constructor.
	function Scrollbar (pane) {
		this.pane = pane;
		this.pane.el.append(this.el);
		this.innerEl = this.pane.inner.get(0);

		this.dragging = false;
		this.enter = false;
		this.shown = false;
		this.needed = false;

		// hovering
		this.pane.el.mouseenter($.proxy(this, 'mouseenter'));
		this.pane.el.mouseleave($.proxy(this, 'mouseleave'));
		
		// dragging
		this.el.mousedown($.proxy(this, 'mousedown'));

		// scrolling
		this.pane.inner.scroll($.proxy(this, 'scroll'));

		// wheel -optional-
		this.pane.inner.bind('mousewheel', $.proxy(this, 'mousewheel'));

		// show
		var initialDisplay = this.pane.options.initialDisplay;
		if (initialDisplay !== false)
		{
			this.hiding = setTimeout($.proxy(this, 'hide'), parseInt(initialDisplay, 10) || 3000);
		}
	};

	// Cleans up.
	Scrollbar.prototype.destroy = function () {
		this.el.remove();
		return this;
	};

	// Called upon mouseleave.
	Scrollbar.prototype.mouseleave = function () {
		this.enter = false;

		if (!this.dragging)
		{
			this.hide();
		}
	};

	// Called upon wrap scroll.
	Scrollbar.prototype.scroll = function () {
		if (!this.shown && this.enter)
		{
			this.show();
			if (!this.enter && !this.dragging)
			{
				this.hiding = setTimeout($.proxy(this, 'hide'), 1500);
			}
		}
		
		if (this.pane.options.onStart)
		{
			this.pane.options.onStart.call();
		}
		
		this.update();
	};

	// Called upon scrollbar mousedown.
	Scrollbar.prototype.mousedown = function (ev) {
		ev.preventDefault();

		this.dragging = true;

		this.startPageY = ev.pageY - parseInt(this.el.css('top'), 10);
		this.startPageX = ev.pageX - parseInt(this.el.css('left'), 10);

		// prevent crazy selections on IE
		document.onselectstart = function () { return false; };

		var
			move = $.proxy(this, 'mousemove'),
			self = this
		;

		$(document)
			.mousemove(move)
			.mouseup(function () {
				self.dragging = false;
				document.onselectstart = null;

				$(document).unbind('mousemove', move);

				if (!self.enter) {
					self.hide();
				}
			})
		;
	};

	// Show scrollbar.
	Scrollbar.prototype.show = function (duration) {
		if (!this.shown)
		{
			this.update();
			this.el.addClass('customscroll-scrollbar-shown');
			if (this.hiding)
			{
				clearTimeout(this.hiding);
				this.hiding = null;
			}
			this.shown = true;
		}
	};

	// Hide scrollbar.
	Scrollbar.prototype.hide = function () {
		var autoHide = this.pane.options.autoHide;
		if (autoHide !== false && this.shown)
		{
			// check for dragging
			this.el.removeClass('customscroll-scrollbar-shown');
			this.shown = false;
		}
	};

	// Horizontal scrollbar constructor
	Scrollbar.Horizontal = function (pane) {
		this.el = $('<div class="customscroll-scrollbar customscroll-scrollbar-horizontal" />');
		this.el.append($('<div />'));
		Scrollbar.call(this, pane);
	};

	// Inherits from Scrollbar.
	inherits(Scrollbar.Horizontal, Scrollbar);

	// Updates size/position of scrollbar.
	Scrollbar.Horizontal.prototype.update = function () {
		var
			paneWidth = this.pane.el.width(),
			trackWidth = paneWidth - this.pane.padding * 2,
			innerEl = this.pane.inner.get(0),
			width = trackWidth * paneWidth / innerEl.scrollWidth
		;
		
		if (width < 50)
		{
			trackWidth = trackWidth - (50 - width);
			width = 50;
		}

		this.el
			.css('width', width)
			.css('left', trackWidth * innerEl.scrollLeft / innerEl.scrollWidth)
		;
	};

	// Called upon drag.
	Scrollbar.Horizontal.prototype.mousemove = function (ev) {
		var
			trackWidth = this.pane.el.width() - this.pane.padding * 2,
			pos = ev.pageX - this.startPageX,
			barWidth = this.el.width(),
			innerEl = this.pane.inner.get(0),
			y = Math.min(Math.max(pos, 0), trackWidth - barWidth) // minimum top is 0, maximum is the track height
		;

		innerEl.scrollLeft = (innerEl.scrollWidth - this.pane.el.width()) * y / (trackWidth - barWidth);
	};
  
	// Called upon container mousewheel.
   	Scrollbar.Horizontal.prototype.mousewheel = function (ev, delta, x, y) {
		if (this.pane.inner.get(0).scrollWidth > this.pane.el.width())
		{
			this.enter = true;
			this.show();
		}
		if ((x < 0 && 0 === this.pane.inner.get(0).scrollLeft) ||
			(x > 0 && (this.innerEl.scrollLeft + Math.ceil(this.pane.el.width()) === this.innerEl.scrollWidth)))
		{
			ev.preventDefault();
			return false;
		}
	};

	Scrollbar.Horizontal.prototype.mouseenter = function () {
		if (this.pane.inner.get(0).scrollWidth > this.pane.el.width())
		{
			this.enter = true;
			this.show();
		}
	};
	
	Scrollbar.Horizontal.prototype.set = function (value) {
		if (value !== undefined)
		{
			this.pane.inner.scrollLeft(value);
		}
	};
	
	Scrollbar.Horizontal.prototype.get = function () {
		return this.pane.inner.scrollLeft();
	};
	
	// Vertical scrollbar constructor
	Scrollbar.Vertical = function (pane) {
		this.el = $('<div class="customscroll-scrollbar customscroll-scrollbar-vertical" />');
		this.el.append($('<div />'));
		Scrollbar.call(this, pane);
	};

	// Inherits from Scrollbar.
	inherits(Scrollbar.Vertical, Scrollbar);

	// Updates size/position of scrollbar.
	Scrollbar.Vertical.prototype.update = function () {
		var
			paneHeight = this.pane.relativeToInner ? this.pane.inner.height() : this.pane.el.height(),
			trackHeight = paneHeight - (this.pane.relativeToInner ? 0 : this.pane.padding * 2),
			innerEl = this.innerEl,
			height = trackHeight * paneHeight / innerEl.scrollHeight,
			top = 0
		;
	
		if (height < 50)
		{
			trackHeight = trackHeight - (50 - height);
			height = 50;
		}
		
		top = trackHeight * innerEl.scrollTop / innerEl.scrollHeight + (this.pane.relativeToInner ? this.pane.inner.position().top : 0);
		
		this.el.css({
			'height': height,
			'top':  top
		});
	};

	// Called upon drag.
	Scrollbar.Vertical.prototype.mousemove = function (ev) {
		var
			paneHeight = this.pane.relativeToInner ? this.pane.inner.height() : this.pane.el.height(),
			trackHeight = paneHeight - (this.pane.relativeToInner ? 0 : this.pane.padding * 2),
			pos = ev.pageY - this.startPageY  - (this.pane.relativeToInner ? this.pane.inner.position().top : 0),
			barHeight = this.el.height(),
			innerEl = this.innerEl,
			y = Math.min(Math.max(pos, 0), trackHeight - barHeight) // minimum top is 0, maximum is the track height
		;

		innerEl.scrollTop = (innerEl.scrollHeight - paneHeight) * y / (trackHeight - barHeight);
	};

	// Called upon container mousewheel.
	Scrollbar.Vertical.prototype.mousewheel = function (ev, delta, x, y) {
		if (this.pane.inner.get(0).scrollHeight > this.pane.el.height())
		{
			this.enter = true;
			this.show();
		}
	};
  
	// Called upon mouseenter.
	Scrollbar.Vertical.prototype.mouseenter = function () {
		if (this.pane.inner.get(0).scrollHeight > this.pane.el.height())
		{
			this.enter = true;
			this.show();
		}
	};
	
	Scrollbar.Vertical.prototype.set = function (value) {
		if (value !== undefined)
		{
			this.pane.inner.scrollTop(value);
		}
	};
	
	Scrollbar.Vertical.prototype.get = function () {
		return this.pane.inner.scrollTop();
	};

	/**
	* Cross-browser inheritance.
	*
	* @param {Function} ctorA constructor
	* @param {Function} ctorB constructor we inherit from
	* @api private
	*/
	function inherits (ctorA, ctorB) {
		function f() {};
		f.prototype = ctorB.prototype;
		ctorA.prototype = new f;
	};

	// Scrollbar size detection.
	function getBrowserScrollbarWidth () {
		var outer, outerStyle, scrollbarWidth;
		outer = document.createElement('div');
		outer.className = 'scroll-inner';
		outerStyle = outer.style;
		outerStyle.position = 'absolute';
		outerStyle.width = '100px';
		outerStyle.height = '100px';
		outerStyle.overflow = 'scroll';
		outerStyle.top = '-9999px';
		document.body.appendChild(outer);
		scrollbarWidth = outer.offsetWidth - outer.clientWidth;
		document.body.removeChild(outer);
		
		return scrollbarWidth;
  };

})(jQuery);
