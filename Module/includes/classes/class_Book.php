<?php
class Book {

	public function getTOC() {
		return $this->makeList();
	}
	
	public function output() {
		return $this->makeOutput();
	}
	
	private function makeList($current_tag = 0) {
	
		$db = new DatabaseQuery;
		
		if ($current_tag == 0) {
			$rs = $db->execute("SELECT *
								FROM book_sections
								WHERE parent_id = 0
								AND live = 1
								ORDER BY position ASC");
		}
		else {
			$rs = $db->execute("SELECT *
								FROM book_sections
								WHERE parent_id = $current_tag
								AND live = 1
								ORDER BY position ASC");
		}
		
		if ($rs->getNum()) {
			while ($row = $rs->getRow()) {
				$sub_levels[] = $row;
			}
		
			$body .= "<ul class=\"book\">";
			
			foreach ($sub_levels as $sl) {
				$section = new BookSection($sl['section_id']);
				$subsections = $this->makeList($sl['section_id']);
				
				$body .= "<li>
				<div>
				<!--<a href=\"delete.php?section_id={$sl['section_id']}\" onclick=\"return conf_del();\">Delete</a>
				<a href=\"insert.php?section_tag={$sl['section_tag']}\">Insert</a>
				".($subsections == '' ? "<a href=\"subsection.php?section_tag={$sl['section_tag']}\">SubSection</a>" : '')."-->
				</div>
				<strong>{$sl['section_tag']}) <a href=\"section.php?section_id={$sl['section_id']}\">".$section->getTitle()."</a></strong> $subsections</li>";
			}
			
			//$body .= "<li><a href=\"add_section.php?section_tag=$current_tag\">Add Section Here</a></li>";
			$body .= "</ul>";
		}

		
		return $body;
	}
	
	private function makeOutput($current_tag = 0) {
	
		$db = new DatabaseQuery;
		
		if ($current_tag == 0) {
			$rs = $db->execute("SELECT *
								FROM book_sections
								WHERE parent_id = 0
								AND live = 1
								ORDER BY position ASC");
		}
		else {
			$rs = $db->execute("SELECT *
								FROM book_sections
								WHERE parent_id = $current_tag
								AND live = 1
								ORDER BY position ASC");
		}
		
		if ($rs->getNum()) {
			while ($row = $rs->getRow()) {
				$section = new BookSection($row['section_id']);
				$body .= $section->getTitle()."\n";
				$body .= $section->getBody()."\n\n\n";
				$body .= $this->makeOutput($row['section_id']);
			}
		}

		return $body;
	}
	
	function addSection($section_tag) {
	
		$db = new DatabaseQuery;
		
		if ($section_tag == 0) {
			$rs = $db->execute("SELECT MAX(section_tag) + 1 AS new_section_tag
								FROM book_sections
								WHERE section_tag NOT LIKE '%.%'
								AND live = 1");
								
			$row = $rs->getRow();
			$db->execute("INSERT INTO book_sections (section_tag) VALUES ('{$row['new_section_tag']}')");
		}
		else {
			$rs = $db->execute("SELECT section_tag
								FROM book_sections
								WHERE section_tag REGEXP '^{$section_tag}\.[[:digit:]]+$'
								AND live = 1");
								
			while ($row = $rs->getRow()) {
				$st = explode('.', $row['section_tag']);
				$used[] = $st[count($st)-1];
			}
			
			$db->execute("INSERT INTO book_sections (section_tag) VALUES ('$section_tag.".(max($used)+1)."')");
		}
							
		while ($row = $rs->getRow()) {
			if (strpos($row['section_tag'], '.')) {
				$st = explode('.', $row['section_tag']);
				$used[] = $st[count($st)-1];
			}
			else {
				$used[] = $row['section_tag'];
			}	
		}
	}
}
?>