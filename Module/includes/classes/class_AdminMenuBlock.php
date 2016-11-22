<?php
class AdminMenuBlock {

	public function addLink($url, $text) {
		$this->links[] = array($url, $text);
	}

	public function display() {
		if (count($this->links) > 0) {
			foreach ($this->links as $link)
				$items[] = "<a href=\"{$link[0]}\">{$link[1]}</a>";

			$content .= HTMLHelper::wrapArrayInUl($items);
		}
		else
			$content = '&nbsp;';
			
		return $content;
	}
}
?>