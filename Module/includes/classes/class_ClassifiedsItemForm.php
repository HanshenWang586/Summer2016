<?php
class ClassifiedsItemForm extends PrimordialForm {

	public function __construct() {
		
	}

	public function displayForm() {
		$content = $this->displayErrors('<p>Something didn\'t work out, please check below:</p>');
		//$content .= debug($this);

		$content .= FormHelper::open('/en/classifieds/proc_classified/');
		$content .= FormHelper::hidden('classified_id', $this->getDatum('classified_id'));

		$f[] = FormHelper::select('Section', 'folder_id', array('' => 'Please select...') + ClassifiedsFolderList::getFolders(), $this->getDatum('folder_id'), array('mandatory' => true));
		$f[] = FormHelper::input('Title', 'title', $this->getDatum('title'), array('mandatory' => true, 'minlength' => 10, 'maxlength' => 50));
		$f[] = FormHelper::textarea('Text', 'body', $this->getDatum('body'), array('mandatory' => true));
		$f[] = FormHelper::input('Expiry date', 'ts_end', $this->getDatum('ts_end'), array('type' => 'date', 'placeholder' => 'eg 2014-02-14', 'guidetext' => 'Please enter an expiry date of maximum 31 days. Fill out in the following format: 2014-02-22 for February 22, 2014', 'mandatory' => true));
		$f[] = FormHelper::submit('Save');
		
		$content .= FormHelper::fieldset('', $f);
		$content .= FormHelper::close();
		return $content;
	}

	public function setFolderID($folder_id) {
		if (!ctype_digit($this->getDatum('folder_id')))
			$this->setDatum('folder_id', $folder_id);
	}

	public function setClassifiedID($classified_id) {
		global $user;
		if (ctype_digit($classified_id)) {
			$ci = new ClassifiedsItem($classified_id);
			if ($ci->user_id != $user->getUserID()) HTTP::disallowed();
			$this->setData($ci->getData());
			$this->classifiedsItem = $ci;
		}
	}

	public function processForm() {
		//var_dump($this->classifiedsItem);die();
		if (!$ci = $this->classifiedsItem) $ci = new ClassifiedsItem;
		$ci->setData($this->getData());
		return $ci->save();
	}
}
?>