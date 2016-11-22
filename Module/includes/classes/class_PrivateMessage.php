<?php
class PrivateMessage {

	private $replyto_id = 0;
	private $status = 0;

	/*
	$this->status: for bitwise operations
	1 ----- deleted by sender
	2 ----- deleted by recipient
	4 ----- read by recipient
	*/

	public function __construct($pm_id = '') {
		if (ctype_digit($pm_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *, UNIX_TIMESTAMP(ts) AS ts_unix
							   FROM pm_messages
							   WHERE pm_id = '.$pm_id);
			$this->setData($rs->getRow());
		}
	}

	public function getComposeForm($to_id = '') {
		global $user;

		$content .= FormHelper::open('/en/users/pm_send/', array('id' => 'pm_compose'));
		$f[] = FormHelper::input('From', 'from', strip_tags($user->getNickname()), array('mandatory' => true, 'disabled' => true));

		if (ctype_digit($to_id)) {
			$to = new User($to_id);
			$content .= FormHelper::hidden('to_id', $to->getUserID());
			$f[] = FormHelper::input('To', 'to', strip_tags($to->getNickname()), array('mandatory' => true, 'disabled' => true));
		}
		else
			$f[] = FormHelper::input('To', 'to', '', array('disable_autocomplete' => true, 'mandatory' => true, 'onkeyup' => "findPrivateMailRecipients(this.value);"));

		$f[] = FormHelper::input('Subject', 'subject', '', array('mandatory' => true));
		$f[] = FormHelper::textarea('Message', 'message', '', array('mandatory' => true));
		$f[] = FormHelper::submit('Send');
		
		$content .= FormHelper::fieldset('', $f);
		$content .= FormHelper::close();
		return $content;
	}

	public function getReplyForm($replying_to_msg) {
		global $user;
		$to = new User($replying_to_msg->getFromID());
		$subject = (strpos($replying_to_msg->getSubject(), 'Re: ') !== 0 ? 'Re: ' : '').$replying_to_msg->getSubject();
		$message = "\n\n\n".strip_tags($to->getNickname())." wrote:\n".strip_tags(ContentCleaner::unPWrap($replying_to_msg->getMessage()));

		$content .= FormHelper::open('/en/users/pm_send/', array('id' => 'pm_compose'));
		$content .= FormHelper::hidden('to_id', $to->getUserID());
		$content .= FormHelper::hidden('replyto_id', $replying_to_msg->getPrivateMessageID());
		$f[] = FormHelper::input('From', 'from', strip_tags($user->getNickname()), array('mandatory' => true, 'disabled' => true));
		$f[] = FormHelper::input('To', 'to', strip_tags($to->getNickname()), array('mandatory' => true, 'disabled' => true));
		$f[] = FormHelper::input('Subject', 'subject', $subject, array('mandatory' => true));
		$f[] = FormHelper::textarea('Message', 'message', $message, array('mandatory' => true));
		$f[] = FormHelper::submit('Send');
		$content .= FormHelper::fieldset('', $f);
		$content .= FormHelper::close();
		return $content;
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	public function getPrivateMessageID() {
		return $this->pm_id;
	}

	public function getToID() {
		return $this->to_id;
	}

	public function getFromID() {
		return $this->from_id;
	}

	public function getReplyToID() {
		return $this->replyto_id;
	}

	public function getSubject() {
		return $this->subject;
	}

	public function getMessage() {
		return ContentCleaner::PWrap($this->message);
	}
	
	public function getSummary() {
		preg_match('/^([^.!?\s]*[\.!?\s]+){0,15}/', strip_tags($this->message), $abstract);
		return trim($abstract[0]) . ' &hellip;';
	}

	public function getReplyPMID() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT pm_id FROM pm_messages WHERE replyto_id = $this->pm_id");
		$row = $rs->getRow();
		return $row['pm_id'];
	}

	public function getLink() {
		$subject = $this->is_read ? '<strong>'.$this->subject.'</strong>' : $this->subject;
		return '<a href="'.$this->getURL().'">'.$subject.'</a>';
	}

	public function getURL() {
		return $GLOBALS['model']->url(array('m' => 'users', 'view' => 'pm_message', 'id' => $this->pm_id));
	}

	public function getFromName() {
		return $GLOBALS['site']->getUser($this->from_id)->getNickname();
	}

	public function getToName() {
		return $GLOBALS['site']->getUser($this->to_id)->getNickname();
	}

	public function getDate() {
		return $GLOBALS['model']->tool('datetime')->getDateTag($this->ts_unix);
	}

	private function isRead() {
		return $this->status & 4;
	}

	private function isBySenderDeleted() {
		return $this->status & 1;
	}

	private function isByRecipientDeleted() {
		return $this->status & 2;
	}

	public function markAsRead() {
		if (!$this->isRead()) {
			$this->status += 4;
			$this->save();
		}
	}

	public function delete() {
		global $user;
		
		if ($user->getUserID() == $this->from_id && !$this->isBySenderDeleted()) {
			$this->status += 1;
			$this->save();
			return 'sender';
		}

		if ($user->getUserID() == $this->to_id && !$this->isByRecipientDeleted()) {
			$this->status += 2;
			$this->save();
			return 'recipient';
		}
		
		return false;
	}

	public function save() {
		global $model;
		$message = ContentCleaner::cleanForDatabase($this->message);
		$db = new DatabaseQuery;

		if (ctype_digit($this->pm_id)) {
			$db->execute("	UPDATE pm_messages
							SET status = $this->status
							WHERE pm_id = $this->pm_id");
		}
		else {
			$db->execute("INSERT INTO pm_messages (	to_id,
													from_id,
													replyto_id,
													subject,
													message,
													status,
													ts)
							VALUES (".$db->clean($this->to_id).",
									$this->from_id,
									".$db->clean($this->replyto_id).",
									'".$db->clean($this->subject)."',
									'".$db->clean($message)."',
									0,
									NOW())");

			$this->pm_id = $db->getNewID();
			$recipient = new User($this->to_id);

			// if sender is blocked or banned, delete by recipient at send time
			$sender = new User($this->from_id);
			if ($sender->isBanned() || in_array($this->from_id, $recipient->getPMBlockListIDs()) && !$this->isByRecipientDeleted()) {
				$db->execute('	UPDATE pm_messages
								SET status = status + 2
								WHERE pm_id = '.$this->pm_id);
			}
			else { // notify recipient by email
				$sender = new User($this->from_id);
				$url = $model->url(array('m' => 'users', 'view' => 'pm_message', 'id' => $this->pm_id));
				$message = $sender->getNickname()." sent you a PM. Do not reply to this email - view and reply online by following <a href=\"".$url."\">this link</a>.\n\n";
				$message .= $this->getMessage();
				$recipient->sendEmail('New '.$model->lang('SITE_NAME').' PM: '.$this->getSubject(), $message);
			}
		}
	}
}
?>