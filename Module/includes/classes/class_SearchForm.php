<?php
class SearchForm {

	private $results = 0;

	public function setSearchString($ss) {
		$this->ss = $ss;
		$this->ss_display = htmlentities(stripslashes($this->ss), ENT_COMPAT, 'UTF-8');
	}

	public function displayForm() {
		$content = '<form action="/en/search/redirect/" method="get"><fieldset>';
		$content .= "<input name=\"ss\" value=\"".($this->ss_display ? $this->ss_display : 'search')."\" class=\"ss\" onfocus=\"if (this.value == 'search') {this.value = ''}\" /><input type=\"image\" id=\"siteSearchSubmit\" src=\"/images/gokunming/submit_search.png\" />";
		$content .= '</fieldset></form>';
		return $content;
	}

	public function logSearch() {
		global $user;

		if ($this->ss != '' && $user->user_agent != 'Mediapartners-Google') {
			$db = new DatabaseQuery;
			$db->execute("	INSERT INTO log_searches (ss, user_id, ts, session_id)
							VALUES ('".$db->clean($this->ss)."', ".$user->getUserID().", NOW(), '".$user->getSessionID()."')");
		}
	}
}
?>