<?php
class Survey {

	public function __construct($survey_id) {
		$this->survey_id = $survey_id;
		if ($survey_id) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT *
								FROM surveys
								WHERE survey_id = $survey_id");
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
	
	public function findByCode($code) {
		
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT survey_id
						   	FROM surveys
						   	WHERE survey_code = '".$db->clean($code)."'");
		$row = $rs->getRow();
		return $row['survey_id'];
	}
	
	public function getTitle() {
		return $this->survey_en;
	}
	
	public function getCode() {
		return $this->survey_code;
	}
	
	public function userHasResponded($user_id) {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   	FROM survey_respondents
						   	WHERE survey_id = $this->survey_id
							AND user_id = $user_id");
		if ($rs->getNum() == 0)
			return false;
		else
			return true;
	}
	
	public function getGuideText() {
		return ContentCleaner::PWrap(ContentCleaner::linkHashURLs($this->guidetext_en));
	}
	
	public function display() {
		
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   	FROM survey_sections
						   	WHERE survey_id = $this->survey_id
							ORDER BY position");
		
		$content .= "<div id=\"survey\">
		<h1>".$this->getTitle()."</h1><br />";
		
		$content .= $this->getGuideText();

		$content .= "<form method=\"post\" action=\"/en/survey/survey_proc/\">
		<input type=\"hidden\" name=\"survey_id\" value=\"$this->survey_id\">";
		
		while ($row = $rs->getRow()) {
			$content .= "<h1>{$row['section_en']}</h1>";
			$content .= $row['guidetext_en'] != '' ? nl2br($row['guidetext_en'])."<br />" : '';
			
			$rs_q = $db->execute("	SELECT *
									FROM survey_questions
									WHERE section_id = {$row['section_id']}
									ORDER BY position");
			
			while ($row_q = $rs_q->getRow()) {
				$sq = new SurveyQuestion;
				$sq->setData($row_q);
				$content .= $sq->display();
			}
			
			$content .= "<br /><br />";
		}
		
		$content .= "<br /><input type=\"submit\" value=\"Submit\">
		</form>
		</div>";
		
		return $content;		
	}
	
	public function setPost($post) {
		$this->post = $post;	
	}
	
	public function setUserID($user_id) {
		$this->user_id = $user_id;	
	}
	
	public function save() {
	
		$db = new DatabaseQuery;
		foreach ($this->post as $key => $value) {
			if ($key != 'survey_id') {
				
				$bits = explode('_', $key);
				$sq = new SurveyQuestion($bits[1]);
				
				if ($sq->getQuestionType() == 'TEXT' ||
					($sq->getQuestionType() == 'MCSAOO' && $bits[0] == 'other')) {
					// i.e. a text answer of some sort
					
					if ($value != '') {
						$rs = $db->execute("INSERT INTO survey_texts (text_en, user_id, question_id)
											VALUES ('".$db->clean($value)."', $this->user_id, ".$db->clean($bits[1]).")");
					}
				}
				else {
					// i.e. a mc response
					$rs = $db->execute("INSERT INTO survey_answers (choice_id, user_id, question_id)
										VALUES ('".$db->clean($value)."', $this->user_id, ".$db->clean($bits[1]).")");
				}
			}
		}
		
		$db->execute("	INSERT INTO survey_respondents (survey_id, user_id, ts)
						VALUES ($this->survey_id, $this->user_id, NOW())");
	}
	
	public function getResults() {
		
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   	FROM survey_sections
						   	WHERE survey_id = $this->survey_id
							ORDER BY position");
		
		$content .= "<h1>".$this->getTitle()."</h1>";
		
		while ($row = $rs->getRow()) {
			$content .= "<h1>{$row['section_en']}</h1>";
			$content .= $row['guidetext_en'] != '' ? nl2br($row['guidetext_en'])."<br />" : '';
			
			$rs_q = $db->execute("	SELECT *
									FROM survey_questions
									WHERE section_id = {$row['section_id']}
									ORDER BY position");
			
			while ($row_q = $rs_q->getRow()) {
				$sq = new SurveyQuestion;
				$sq->setData($row_q);
				$content .= $sq->displayResults();
			}
			
			$content .= "<br /><br />";
		}
		
		return $content;		
	}
	
	public function getQuestions() {
		
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
						   	FROM survey_sections
						   	WHERE survey_id = $this->survey_id
							ORDER BY position");
		
		while ($row = $rs->getRow()) {
			$content .= "<h1>{$row['section_en']}</h1>";
			$content .= $row['guidetext_en'] != '' ? nl2br($row['guidetext_en'])."<br />" : '';
			
			$rs_q = $db->execute("	SELECT *
									FROM survey_questions
									WHERE section_id = {$row['section_id']}
									ORDER BY position");
			
			while ($row_q = $rs_q->getRow()) {
				$sq = new SurveyQuestion;
				$sq->setData($row_q);
				$content .= $sq->displayAdmin();
			}
			
			$content .= "<br /><br />";
		}
		
		return $content;		
	}

	public function getVoters() {
		
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT nickname, email
						   	FROM survey_respondents r, public_users u
						   	WHERE survey_id = $this->survey_id
							AND u.user_id = r.user_id
							ORDER BY nickname");
		
		$content .= "<h1>".$this->getTitle()." - Voters</h1>
		<table cellspacing=\"1\" class=\"gen_table\">";
		
		while ($row = $rs->getRow()) {
			$content .= "<tr><td>{$row['nickname']}</td><td>{$row['email']}</td></tr>";
		}
		
		$content .= "</table>";
		return $content;		
	}
}
?>