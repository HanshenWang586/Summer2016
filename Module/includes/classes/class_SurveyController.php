<?php
class SurveyController {

	public function display() {

		if (func_num_args())
			$survey_code = func_get_arg(0);
		
		global $user;
		$p = new Page;
		$survey = new Survey(Survey::findByCode($survey_code));

		if ($survey->userHasResponded($user->getUserID())) {
			$body = "<div id=\"survey\"><h1>".$survey->getTitle()."</h1>
			You've already voted in this survey. Thank you.</div>";
		}
		else if ($user->getUserID() != 0) {
			$body = $survey->display();
		}
		else {
			$body = "<div id=\"survey\"><h1>".$survey->getTitle()."</h1>
			".$survey->getGuideText()."
			Please <a href=\"/en/users/login/\">login</a> to complete the survey.
			If you need to register, please <a href=\"/en/users/register/\">click here</a>.
			It's free and only takes a minute!</div>";
		}

		//$survey_right = new View;
		//$survey_right->setPath('gochengdoo/survey/right.html');
		//$p->setTag('#SURVEY_RIGHT#', $survey_right->getOutput());

		$p->setTag('page_title', "Survey: ".$survey->getTitle());
		$p->setTag('main', $body);
		$p->output();
	}

	public function survey_proc() {

		global $user;
		header('Content-type: text/plain; charset=utf-8');

		$survey = new Survey($_POST['survey_id']);
		$survey->setPost($_POST);
		$survey->setUserID($user->getUserID());
		$survey->save();

		HTTP::redirect('/en/survey/complete/'.$survey->getCode().'/');
	}

	public function complete() {

		if (func_num_args())
			$survey_code = func_get_arg(0);

		global $user;
		$p = new Page;
		$survey = new Survey(Survey::findByCode($survey_code));

		$body = "<div id=\"survey\"><h1>".$survey->getTitle()."</h1>
		Thank you for completing the survey. Your name will be entered into a random drawing
		to be eligible for prizes from local businesses. All winners will be contacted
		via e-mail by January 30, 2012.<br />
		<br />
		You must be physically present in Chengdu in order to claim a prize.
		Any winner who does not claim his or her prize within the specified
		time period forfeits his or her right to the prize.</div>";

		//$survey_right = new View;
		//$survey_right->setPath('gochengdoo/survey/right.html');
		//$p->setTag('#SURVEY_RIGHT#', $survey_right->getOutput());

		$p->setTag('page_title', "Survey: ".$survey->getTitle());
		$p->setTag('main', $body);
		$p->output();
	}

}
?>