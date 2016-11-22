<?php
class BookSection {
	
	function __construct($section_id = '') {
	
		if (ctype_digit($section_id)) {
			$this->section_id = $section_id;
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT *
								FROM book_sectionversions
								WHERE section_id = $section_id
								ORDER BY revision DESC
								LIMIT 1");
			
			if ($rs->getNum() == 0) {
				$this->initialise();
			}
			else {
				$this->setData($rs->getRow());
			}
		}
	}

	function load($section_id = '') {
	
		if ($section_id) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT *
								FROM book_sections
								WHERE section_id = $section_id");
			
			$this->setData($rs->getRow());
		}
	}

	function setData($row) {
		if (is_array($row)) {
			foreach($row as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	public function getTitle() {
		return $this->title;
	}

	public function getBody() {
		return $this->body;
	}

	private function getParentID() {
		return $this->parent_id;
	}

	private function getSectionID() {
		return $this->section_id;
	}

	private function getPosition() {
		return $this->position;
	}

	private function initialise() {
		
		$this->title = md5(time());
		$this->body = '';
		$this->save();
	}

	private function generateHash() {
		
		return md5($this->title.$this->body);
	}

	private function getLastRevisionData($section_id) {
		
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM book_sectionversions
							WHERE section_id = $section_id
							ORDER BY revision DESC
							LIMIT 1");
		$row = $rs->getRow();
		return $row;
	}

	public function save() {
	
		if (ctype_digit($this->section_id)) {
		
			$this->title = stripslashes(ContentCleaner::cleanForDatabaseBook($this->title));
			$this->body = stripslashes(ContentCleaner::cleanForDatabaseBook($this->body));
		
			$last_revision_data = $this->getLastRevisionData($this->section_id);
			
			if ($this->generateHash() != $last_revision_data['hash']) {
				$this->saveRevision($last_revision_data['revision'] + 1);
			}
		}
		else if (isset($this->tag)) {
			
			$tag_bits = explode('.', trim($this->tag));
			$new_position = array_pop($tag_bits);
			$parent_tag = implode('.', $tag_bits);
			
			$parent_id = BookSection::getParentIDByTag($parent_tag);
			
			// if the proposed parent exists
			if ($parent_id !== false) {
			
				$db = new DatabaseQuery;
				$rs = $db->execute("SELECT *
									FROM book_sections
									WHERE parent_id = $parent_id
									AND position = $new_position");
				
				if ($rs->getNum() > 0) { // section already exists
					// shift everything up to make space for it
					$db->execute("	UPDATE book_sections
									SET position = position + 1
									WHERE parent_id = $parent_id
									AND position >= $new_position");
				}
				
				// insert the new section
				$db->execute("	INSERT INTO book_sections (parent_id, position, section_tag, live)
								VALUES ($parent_id, $new_position, '$this->tag', 1)");
				$this->section_id = $db->getNewID();
				$this->title = md5(time());
				$this->saveRevision(1);
			}
			else
			echo 'fail';
			
			
			$this->reTag();
			//$this->saveRevision(1);
		}
	}

	private function reTag($section_id = '') {
	
		if ($section_id == '') {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT section_id
								FROM book_sections
								WHERE live = 1");
								
			while ($row = $rs->getRow()) {
				$this->reTag($row['section_id']);
			}
		}
		else {
			$bs = new BookSection;
			$bs->load($section_id);
			$tag_parts = array();
			
			while ($bs->getParentID() != 0) {
				$tag_parts[] = $bs->getPosition();
				$parent_id = $bs->getParentID();
				$bs = new BookSection;
				$bs->load($parent_id);
			}
			
			$tag_parts[] = $bs->getPosition();
			
			if (count($tag_parts)) {
				$tag_parts = array_reverse($tag_parts);
				
				$db = new DatabaseQuery;
				$rs = $db->execute("UPDATE book_sections
									SET section_tag = '".implode('.', $tag_parts)."'
									WHERE section_id = $section_id
									AND live = 1");
			}
		}
	}

	private function getParentIDByTag($tag) {
	
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT section_id
							FROM book_sections
							WHERE section_tag = '$tag'
							AND live = 1");
							
		if ($rs->getNum() == 1) {
			$row = $rs->getRow();
			return $row['section_id'];
		}
		else {
			return false;
		}
	}

	private function saveRevision($rev) {
	
		$db = new DatabaseQuery;
		$db->execute("	INSERT INTO book_sectionversions (	section_id,
															revision,
															title,
															body,
															hash,
															ts)
						VALUES ($this->section_id,
								$rev,
								'".$db->clean($this->title)."',
								'".$db->clean($this->body)."',
								'".$this->generateHash()."',
								NOW())");
	}

	public function getNewSectionForm() {
		$content = "<br />
<br />
<br />
<form action=\"new_section_proc.php\" method=\"post\">
		<strong>Create New Section</strong><br />
		Tag <input name=\"tag\" value=\"\" size=\"20\"> <input type=\"submit\" value=\"Save\">
		</form>";
		
		return $content;
	}

	public function getForm() {
	
		$body = "<form action=\"section_proc.php\" method=\"post\">";
		
		if (ctype_digit($this->section_id)) {
			$body .= "<input type=\"hidden\" name=\"section_id\" value=\"$this->section_id\">";
		}
		
		$body .= "<table class=\"gen_table\" cellspacing=\"1\">
		<tr>
		<td>Title</td>
		<td><input name=\"title\" value=\"$this->title\" size=\"100\"></td>
		</tr>
		
		<tr valign=\"top\">
		<td>Body</td>
		<td><textarea name=\"body\" cols=\"100\" rows=\"30\">".htmlspecialchars($this->body, ENT_NOQUOTES, 'UTF-8')."</textarea></td>
		</tr>
		
		</table>
		<br />
		<input type=\"submit\" value=\"Save\">
		
		</form>";
		
		return $body;
	}
}
?>