<?php
class SurveyQuestion {
	
	public function __construct($question_id = '') 	{
		
		if (ctype_digit($question_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT *
								FROM survey_questions
								WHERE question_id = $question_id");
			$this->setData($rs->getRow());
		}
	}
	
	public function setData($data) 	{
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$this->$key = $value;
			}
		}
	}
	
	public function getQuestionType() {
		return $this->qtype_code;
	}
	
	public function display() {
		$content .= "<h2>$this->question_en <span class=\"chinese\">$this->question_zh</span></h2>";

		switch ($this->qtype_code) {
			case 'TEXT':
				$content .= $this->displayText();
			break;
		
			case 'INPUT':
				$content .= $this->displayInput();
			break;
			
			case 'MCSA':
				$content .= $this->displayMCSA();
			break;
			
			case 'MCSAOO':
				$content .= $this->displayMCSA(true);
			break;
		}
		
		return $content;
	}
	
	public function displayAdmin() {
		$content .= "$this->qtype_code <a href=\"options.php?question_id=$this->question_id\">$this->question_en</a><br />";

		switch ($this->qtype_code) {
			case 'MCSA':
			case 'MCSAOO':
				$content .= $this->displayOptions();
			break;
		}
		
		return $content;
	}
	
	public function displayResults() {
		$content .= "<h2 style=\"width:100%;clear:both;\">$this->question_en <span class=\"chinese\">$this->question_zh</span></h2>";

		switch ($this->qtype_code) {
			case 'TEXT':
				$content .= $this->displayResultsText();
			break;
			
			case 'MCSA':
				$content .= $this->displayResultsMCSA();
			break;
			
			case 'MCSAOO':
				$content .= $this->displayResultsMCSA(true);
			break;
		}
		
		return $content;
	}
	
	public function getTextResultsList() {
		$content .= "<h2>$this->question_en <span class=\"chinese\">$this->question_zh</span></h2>";

		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   	FROM survey_texts
						   	WHERE question_id = $this->question_id
							ORDER BY user_id");
				
		while ($row = $rs->getRow()) {
			$content .= nl2br($row['text_en']).'<br /><br />';
		}
		
		return $content;
	}
	
	private function displayResultsText() {
		return "<a href=\"text_results.php?question_id=$this->question_id\">Click to view text results</a><br />";
	}

	private function displayText() {
		return "<textarea name=\"question_$this->question_id\"></textarea>";
	}
	
	private function displayInput() {
		return "<input name=\"question_$this->question_id\">";
	}
	
	private function displayMCSA($orother = false) {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   	FROM survey_choices c, survey_choices2question c2q
						   	WHERE question_id = $this->question_id
							AND c.choice_id = c2q.choice_id
							ORDER BY position");
		
		while ($row = $rs->getRow()) {
			if ($row['choice_type'] == 'TEXT') {
				$text = ContentCleaner::linkHashURLs($row['choice_en']);//} <span class=\"chinese\">{$row['choice_zh']}</span>";
				if ($row['listings_code'])
					$text = "<a href=\"/en/listings/item/{$row['listings_code']}/\" target=\"_blank\">$text</a>";
				$choices[] = "<input type=\"radio\" name=\"question_$this->question_id\" value=\"{$row['choice_id']}\">$text";
			}
			else
				$choices[] = "<div style=\"width:100%;clear:both;\"><input type=\"radio\" name=\"question_$this->question_id\" value=\"{$row['choice_id']}\" style=\"float: left;\"><img src=\"/images/survey/{$row['choice_id']}.jpg\" style=\"float:left;\"></div><br /><br />";
		}
		
		if ($orother)
			$choices[] = "Other: <input name=\"other_$this->question_id\">";
		
		return HTMLHelper::wrapArrayInUl($choices, in_array($this->question_id, array(42,43)) ? 'survey_covers' : '');
	}
	
	private function displayOptions() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   	FROM survey_choices c, survey_choices2question c2q
						   	WHERE question_id = $this->question_id
							AND c.choice_id = c2q.choice_id
							ORDER BY position");
		
		while ($row = $rs->getRow()) {
			if ($row['choice_type'] == 'TEXT') {
				$text = $row['choice_en'];//} <span class=\"chinese\">{$row['choice_zh']}</span>";
				if ($row['listings_code'])
					$text = "<a href=\"/en/listings/item/{$row['listings_code']}/\" target=\"_blank\">$text</a>";
				$choices[] = $text;
			}
			else
				$choices[] = "<div style=\"text-align: center; background-color: #333;\"><img src=\"/images/survey/{$row['choice_id']}.jpg\"><br /><input type=\"radio\" name=\"question_$this->question_id\" value=\"{$row['choice_id']}\"></div>";
		}
		
		return HTMLHelper::wrapArrayInUl($choices, $this->question_id == 33 ? 'survey_covers' : '');
	}

	public function getOptionChooser() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   	FROM survey_choices
							ORDER BY choice_en");
		
		$option_ids = array();
		$rs_2 = $db->execute("SELECT choice_id
						   	FROM survey_choices2question
							WHERE question_id = $this->question_id");
		while ($row_2 = $rs_2->getRow()) {
			$option_ids[] = $row_2['choice_id'];
		}
		
		while ($row = $rs->getRow()) {
			//if ($row['choice_type'] == 'TEXT') {
				$text = $row['choice_en'];
				$choices[] = "<input type=\"checkbox\" name=\"choice_ids[]\" value=\"{$row['choice_id']}\"".(in_array($row['choice_id'], $option_ids) ? ' checked' : '').">$text";
			//}
		}

		return '<form action="options_proc.php" method="post"><input type="hidden" name="question_id" value="'.$this->question_id.'">'.HTMLHelper::wrapArrayInUl($choices, $this->question_id == 33 ? 'survey_covers' : '').'<input type="submit" value="Save"></form>';
	}

	private function displayResultsMCSA($orother = false) {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *, COUNT(*) AS tally
						   	FROM survey_choices c, survey_answers a
						   	WHERE question_id = $this->question_id
							AND c.choice_id = a.choice_id
							GROUP BY c.choice_id
							ORDER BY tally DESC");
		
		while ($row = $rs->getRow())
			$content .= "{$row['choice_en']} <span class=\"chinese\">{$row['choice_zh']}</span>: {$row['tally']}<br />";
		
		if ($orother)
			$content .= '<br />'.$this->displayResultsText();
		
		return $content;
	}
	
	public function saveOptions($choice_ids) {
		$db = new DatabaseQuery;
		$db->execute("DELETE
						   	FROM survey_choices2question
						   	WHERE question_id = $this->question_id");
		foreach ($choice_ids as $choice_id) {
		$db->execute("INSERT INTO survey_choices2question (question_id, choice_id, position)
					 VALUES ($this->question_id, $choice_id, ".++$i.")");
		}
		
	}
}
?>