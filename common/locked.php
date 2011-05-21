<?php
/*
 * $Date$
 * $Revision$
 * $HeadURL$
 */

class pLocked extends pageAssembly
{
	function __construct()
	{
		parent::__construct();

		$this->queue("start");
		$this->queue("content");
	}

	function start()
	{
		$this->page = new Page("Locked");
	}

	function content()
	{
		global $smarty;
		return $smarty->fetch(get_tpl("locked"));
	}

}

$locked = new pLocked();
event::call("locked_assembling", $locked);
$html = $locked->assemble();
$locked->page->setContent($html);

$locked->page->generate();