<?php
class BokmController {
	public function index() {
		global $user, $site;
		
		$p = new Page;
		
		$site->addMeta('description', 'Vote for the Best of Kunming Awards!');
		$site->addMeta('keywords', 'awards, Best of Kunming, voting, vote');
		$site->addMeta('og:description', $descr, 'Vote for the Best of Kunming Awards!');
		
		$form = isset($_SESSION['bokm_form']) ? $_SESSION['bokm_form'] : new BestOfKunmingForm;
		$form->setAction('/en/bokm/proc_bokm/');
		$body .= $form->display();
		unset($_SESSION['bokm_form']);
		
		$p->setTag('page_title', 'Vote for the Best of Kunming Awards!');
		$p->setTag('main', $body);
		$p->setTag('scripts_lower',
			"<script>
				$(function() {
					$('ul.bokmItems label').addClass('processed').hover( 
						function () { $(this).addClass('hover'); },
						function () { $(this).removeClass('hover'); }
					);
				});
			</script>"
		);
		$p->output();
	}

	function proc_bokm() {
		$form = new BestOfKunmingForm;
		$form->setData($_POST);
		//print_r($form);
		
		if (!$form->getErrorCount()) {
			$form->processForm();
		}
		
		$_SESSION['bokm_form'] = $form;
		HTTP::redirect('/en/bokm/');
	}
}
?>