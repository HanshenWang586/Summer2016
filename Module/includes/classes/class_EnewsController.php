<?php
class eNewsController {
	public function unsubscribe() {
		global $user;

		if (func_num_args() == 0) {
			$p = new Page;

			$body = "<h1>eNews Unsubscribe</h1>
			Please enter your email address below:<br />
			<br />
			<input id=\"email\"> <input type=\"button\" value=\"Remove\" onClick=\"location.href='/en/enews/unsubscribe/' + $('email').value\">";

			$p->setTag('page_title', 'eNews Unsubscribe');
			$p->setTag('main', $body);
			$p->output();
		}
		else {
			$email = func_get_arg(0);
			$p = new Page;
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT user_id
								FROM public_users
								WHERE email='".$db->clean(trim($email))."'");

			while ($row = $rs->getRow()) {
				$puser = new User($row['user_id']);
				$puser->unsubscribeEnews();
			}

			$body = "<h1>eNews Unsubscribe</h1><b>".$email."</b>, you have been unsubscribed";

			$p->setTag('page_title', 'eNews Unsubscribe');
			$p->setTag('main', $body);
			$p->output();
		}
	}
	
	public function view($id = false) {
		if (!$id or !is_numeric($id)) HTTP::throw404();
		global $model;
		
		$message = $model->db()->query('enews', array('enews_id' => $id), array('selectField' => 'message'));
		
		if ($message) {
			$message = str_replace('[enews_id]', $id, $message);
			$message = str_replace('#EMAIL#', '', $message);
			$message = str_replace('[nickname]', 'GoKunming Reader', $message);
		}
		
		echo $message;
		
		die();
	}
}
?>