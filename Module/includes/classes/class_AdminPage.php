<?php
class AdminPage {

	private $template = 'default.html';

	public function __construct($user) {
		$this->user = $user;
		$this->addSidebar();
		$this->start = microtime(true);
	}

	public function setModuleKey($key) {
		global $admin_user;
		$db = new DatabaseQuery;
		$rs = $db->execute("	SELECT menu_text, module_id
								FROM admin_modules
								WHERE module_key = '$key'");
		$row = $rs->getRow();
		$this->setTitle($row['menu_text']);
		$this->setMenuID($row['module_id']);
			
		if (!$admin_user->isAllowedAccess($row['module_id']))
			HTTP::redirect(ADMIN_ROOT_URL.'modules/login/logout.php');
	}

	public function setTemplate($template) {
		$this->template = $template;
	}

	public function addSidebar() {
		$amb = new AdminMenuBlock;

		if (file_exists('menu.txt')) {
			$lines = file('menu.txt');

			foreach ($lines as $line) {
				$bits = explode('|', trim($line));
				$amb->addLink($bits[0], $bits[1]);
			}
		}

		$this->setTag('left', $amb->display());
	}

	public function setTag($tag, $content) {
		if ($this->tags[$tag] == '')
			$this->tags[$tag] = $content;
		else
			echo 'template warning';
	}

	public function output() {
		$this->tags['menu'] = $this->generateMenu();
		$view = new View;
		$view->replacePath($_SERVER['DOCUMENT_ROOT'].ADMIN_ROOT_URL.'templates/'.$this->template);
		
		foreach($this->tags as $tag => $content)
			$view->setTag($tag, $content);

		echo $view->getOutput();
		//echo microtime(true) - $this->start;
	}

	private function setMenuID($menu_id) {
		$this->menu_id = $menu_id;
	}

	public function generateMenu() {
		$db = new DatabaseQuery;
		$rs = $db->execute("	SELECT *
								FROM admin_modules
								WHERE open_to_all = 1
								OR module_id IN (".implode(', ', $this->user->getPermissions()).")
								ORDER BY position ASC, menu_text ASC");

		while ($row = $rs->getRow())
			$menu_items[$row['module_id']] = array(	'text' => $row['menu_text'],
													'link' => $row['menu_link']);

		$content = '<div id="header_title"><h1>'.ADMIN_TITLE.'</h1></div>
					<div id="header_menu">
					<ul>';

		foreach (array_keys($menu_items) as $i) {
			$link = ADMIN_ROOT_URL.$menu_items[$i]['link'];
			$text = str_replace(' ', '&nbsp;', $menu_items[$i]['text']);

			if ($i == $this->menu_id)
				$content .= "<li class=\"selected\"><a href=\"$link\">$text</a></li>";
			else
				$content .= "<li><a href=\"$link\">$text</a></li>";
		}

		$content .= '	</ul>
						</div>
						<div id="header_bottom"></div>';
		return $content;
	}

	public function setTitle($text) {
		$this->tags['title'] = $text;
	}

	public function appendTitle($text) {
		$this->tags['title'] .= $text;
	}
}
?>