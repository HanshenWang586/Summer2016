<?php
class FromthewebController {

	public function index() {
		$p = new Page;
		
		$pager = new Pager;
		$pager->setLimit(15);
		
		$rs = $pager->setSQL('SELECT *
							 FROM fromtheweb
							 ORDER BY ts DESC');

		$items = array();
		while ($row = $rs->getRow()) $items[] = $row;
		
		$ftw = new FromTheWeb;
		
		$view = new View;
		$view->setPath('fromtheweb/all.html');
		$view->setTag('pagination', $pager->getNav());
		$view->setTag('content', $ftw->sprintItems($items));
		
		$body = $view->getOutput();
		
		$p->setTag('main', $body);
		$p->output();
	}
}
?>