<?php
class PrivateMessageList {

	public function getInbox($pager) {
		global $user;
		$rs = $pager->setSQL('SELECT *, UNIX_TIMESTAMP(ts) AS ts_unix, (status & 4 = 4) AS is_read
							FROM pm_messages
							WHERE to_id = '.$user->getUserID().'
							AND NOT status & 2
							ORDER BY ts DESC');

		if ($rs->getNum() > 0) {
			$pm = new PrivateMessage;
			$messages = '<div class="itemList messagesList">';
			while ($row = $rs->getRow()) {
				$pm->setData($row);
				$messages .= sprintf(
					'<article><a class="item" href="%s">
						<span class="icon icon-%s"> </span>
						%s
						<h1>%s</h1>
						<h2>%s</h2>
						<p>%s</p>
					</a></article>',
					$pm->getURL(),
					$pm->is_read ? 'envelope-2' : 'envelope-3',
					$pm->getDate(),
					$pm->getFromName(),
					$pm->subject,
					$pm->getSummary()
				);
			}
			$messages .= '</div>';
		}
		return $messages . $pager->getNav();
	}

	public function getSent($pager) {
		global $user;
		$rs = $pager->setSQL('SELECT *, UNIX_TIMESTAMP(ts) AS ts_unix, (status & 4 = 4) AS is_read
							FROM pm_messages
							WHERE from_id = '.$user->getUserID().'
							AND NOT status & 1
							ORDER BY ts DESC');

		if ($rs->getNum() > 0) {
			$pm = new PrivateMessage;
			$messages = '<div class="itemList messagesList">';
			while ($row = $rs->getRow()) {
				$pm->setData($row);
				$messages .= sprintf(
					'<article><a class="item" href="%s">
						<span class="icon icon-%s"> </span>
						%s
						<h1>%s</h1>
						<h2>%s</h2>
						<p>%s</p>
					</a></article>',
					$pm->getURL(),
					$pm->is_read ? 'envelope-2' : 'envelope-3',
					$pm->getDate(),
					$pm->getToName(),
					$pm->subject,
					$pm->getSummary()
				);
			}
			$messages .= '</div>';
		}
		return $messages . $pager->getNav();
	}
}
?>