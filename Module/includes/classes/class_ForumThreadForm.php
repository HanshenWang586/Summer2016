<?php
class ForumThreadForm extends PrimordialForm {
	public function displayForm() {
		global $model;
		
		$content = $this->displayErrors('<p>Sorry, there seems to have been problems with your form:</p>');
		
		$content .= FormHelper::open('/en/forums/proc_thread/');
		
		$f[] = FormHelper::select('Board', 'board_id', $this->args['boards'], $this->args['board_id'], array('mandatory' => true, 'emptyCaption' => $model->lang('SELECT_BOARD_EMPTY', 'ForumsModel')));
		$f[] = FormHelper::input('Title', 'thread', $this->getDatum('thread'), array('mandatory' => true, 'minlength' => 10, 'maxlength' => 50, 'guidetext' => 'Please enter a title for your new forum thread between 10 and 50 characters.'));
		$f[] = FormHelper::textarea('Post', 'post', $this->getDatum('post'), array('mandatory' => true));
		$subscribe = $this->getDatum('subscribe');
		if ($subscribe == 1) $subscribe = ' checked="checked"';
		else $subscribe = '';
		$f[] = FormHelper::element('&nbsp;', "<label class=\"noStyle\"><input class=\"checkbox\" type=\"checkbox\" name=\"subscribe\"$subscribe value=\"1\"> Subscribe to this thread by email</label>");
		$f[] = FormHelper::submit('Post');
		
		$content .= FormHelper::fieldset('', $f);
		$content .= FormHelper::close();
		
		return $content;
	}

	public function processForm() {
		
	}
}
?>