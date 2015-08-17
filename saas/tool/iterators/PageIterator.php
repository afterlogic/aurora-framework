<?php

namespace saas\tool\iterators;

class PageIterator implements \Iterator
{
	private $aPage; ///< Массив данных

	function __construct()
	{
		$this->aPage = false;
	}

	function rewind()
	{
		$this->aPage = $this->rewind_page();
	}

	function current()
	{
		return current($this->aPage);
	}

	function key()
	{
		return key($this->aPage);
	}

	function next()
	{
		if (next($this->aPage) === false)
		{
			$this->aPage = $this->next_page();
		}
	}

	function valid()
	{
		return $this->aPage !== false && current($this->aPage) !== false;
	}
}
