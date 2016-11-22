<?php
class FromTheWeb {

	/**
	 * Pulls FTW item from database if $ftw_id is provided
	 * @param string $ftw_id ID of requested FTW item
	 */
	public function __construct($ftw_id = '') {
		if (ctype_digit($ftw_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
								FROM fromtheweb
								WHERE ftw_id = '.$ftw_id);
			$this->setData($rs->getRow());
		}
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value)
				$this->$key = $value;
		}
	}

	public function sprintItems($items, $class = '') {
		global $model;
		$return = '';
		if ($items and is_array($items)) foreach($items as $row) {
			$return .= sprintf('
				<article class="%s %s">
					<a class="item" rel="nofollow" href="%s">
						%s
						<span class="source">%s</span>
						<h1>%s</h1>
						<p class="descr">%s</p>
					</a>
				</article>',
				$row['language_id'] == 2 ? 'cn' : 'en',
				$class,
				$row['url'],
				$model->tool('datetime')->getDateTag($row['ts']),
				array_get(parse_url($row['url']), 'host'),
				$row['title'],
				$row['body']
			);
		}
		return $return;
	}
	
	/**
	 * @return string Form for admin side add/edit of FTW
	 */
	public function getForm() {
		global $admin_user;
		$content = FormHelper::open('form_ftw_proc.php');
		$content .= FormHelper::submit();
		$content .= FormHelper::hidden('ftw_id', $this->ftw_id);
		$f[] = FormHelper::input('URL', 'url', $this->url, array('style' => 'width:500px'));
		$f[] = FormHelper::input('Title', 'title', $this->title, array('style' => 'width:500px'));
		$f[] = FormHelper::select('Language', 'language_id', array(1 => 'English', 2 => 'Chinese'), $this->language_id);
		$f[] = FormHelper::textarea('Body', 'body', $this->body);
		$content .= FormHelper::fieldset('FromTheWeb', $f);
		$content .= FormHelper::submit();
		return $content;
	}

	/**
	 * Saves FTW to database
	 */
	public function save() {
		$db = new DatabaseQuery;
		$this->title = ContentCleaner::cleanForDatabase($this->title);
		$this->body = ContentCleaner::cleanForDatabase($this->body);

		if (ctype_digit($this->ftw_id)) {
			$db->execute("	UPDATE fromtheweb
							SET url = '".$db->clean(trim($this->url))."',
								title = '".$db->clean($this->title)."',
								body = '".$db->clean($this->body)."',
								language_id = $this->language_id
							WHERE ftw_id = $this->ftw_id");
		}
		else {
			$db->execute("INSERT INTO fromtheweb (url, title, body, language_id, ts)
							VALUES ('".$db->clean(trim($this->url))."',
									'".$db->clean($this->title)."',
									'".$db->clean($this->body)."',
									$this->language_id,
									NOW())");
		}
	}
	
	/**
	 * @return string HTML layout of a single FTW item
	 */
	public function getPublic() {
		return "<a href=\"$this->url\"><b>$this->title</b></a><br /><em>".
		DateManipulator::convertYMDToFriendly($this->ts, array('show_year' => true)).'</em>'.
		ContentCleaner::PWrap($this->body);
	}
	
	/**
	 * Deletes FTW from database
	 */
	public function delete() {
		$db = new DatabaseQuery;
		$db->execute('	DELETE FROM fromtheweb
						WHERE ftw_id = '.$this->ftw_id);
	}
}
?>