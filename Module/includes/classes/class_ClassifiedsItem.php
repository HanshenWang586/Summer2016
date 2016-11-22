<?php
class ClassifiedsItem {

	private $ad_valid_days = 90;
	private $show_path = false;
	private $show_user = true;
	private $show_respond_button = true;
	private $show_title = true;

	private $classified_id;
	private $folder_id;
	private $title;
	private $body;
	private $ts_end;
	private $ts_end_unix;

	/**
	 * Statuses of ads
	 *
	 * @var array
	 */
	private $statuses = array(	1 => 'live',
								2 => 'waiting',
								3 => 'user deleted',
								4 => 'admin deleted',
								5 => 'expired');


	public function __construct($classified_id = '') {
		if (ctype_digit($classified_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT 	*,
										d.status AS status,
										UNIX_TIMESTAMP(ts) AS ts_unix,
										UNIX_TIMESTAMP(ts_end) AS ts_end_unix
										FROM classifieds_data d
										LEFT JOIN public_users u ON (d.user_id = u.user_id)
										WHERE classified_id = '.$classified_id
					);
			$this->setData($rs->getRow());
		}
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value)
				$this->$key = $value;
		}
	}

	public function getData() {
		return array(	'classified_id' => $this->classified_id,
						'folder_id' => $this->folder_id,
						'title' => $this->title,
						'body' => $this->body,
						'ts_end' => $this->ts_end,
						'ts_end_unix' => $this->ts_end_unix);
	}

	public function getBrief() {
		$folder = $this->getFolder();
		return sprintf('<article>
			<a href="%s">
				%s
				<span class="top">%s <span class="postingDate">• %s</span> %s %s</span>
				<h1>%s</h1>
			</a>
		</article>',
			$this->getURL(),
			$folder->getIcon(),
			$this->getUser()->getNickname(),
			$this->getDateTag(),
			$GLOBALS['model']->lang('IN', 'ClassifiedsModel'),
			$folder->getTitle(),
			$this->getTitle()
		);
	}

	public function getPath() {
		$cf = $this->getFolder($this->folder_id);
		return $cf->getPath();
	}

	public function setShowPath($bool) {
		$this->show_path = $bool;
	}

	public function setShowRespondButton($bool) {
		$this->show_respond_button = $bool;
	}

	public function setShowTitle($bool) {
		$this->show_title = $bool;
	}

	public function setShowUser($bool) {
		$this->show_user = $bool;
	}

	public function getTitle() {
		return ContentCleaner::wrapChinese(htmlspecialchars($this->title));
	}

	public function getBody() {
		return $this->body;
	}

	private function getTitleForURL() {
		return ContentCleaner::processForURL($this->title);
	}

	public function getURL() {
		return '/en/classifieds/item/'.$this->getClassifiedID().'/'.$this->getTitleForURL();
	}

	public function getAbsoluteURL() {
		global $model;
		return $model->url(array('m' => 'classifieds', 'view' => 'item', 'id' => $this->getClassifiedID(), 'name' => $this->getTitle()));
	}

	public function getLink() {
		return '<a href="'.$this->getURL().'">'.$this->getTitle().'</a>';
	}

	private function getRespondURL() {
		return '/en/classifieds/item/'.$this->getClassifiedID().'/';
	}

	public function getFolderID() {
		return $this->folder_id;
	}

	public function getFolder() {
		if (!$this->folder or $this->folder_id != $this->folder->getFolderID()) {
			$this->folder = new ClassifiedsFolder($this->folder_id);
		}
		return $this->folder;
	}

	public function getClassifiedID() {
		return $this->classified_id;
	}

	public function getDateTag() {
		return $GLOBALS['model']->tool('datetime')->getDateTag($this->ts_unix);
	}
	
	public function displayPublic($brief = false) {
		global $user, $model;
		
		$view = new View;
		$view->setPath('classifieds/item.html');
		
		$body = ContentCleaner::cleanPublicDisplay($this->body);
		$body = ContentCleaner::linkURLs($body);
		if ($brief) {
			$body = '<p>' . mb_substr(trim(strip_tags($body)), 0, 150, 'UTF-8') . ' &hellip;</p>';
		} else $body = ContentCleaner::PWrap($body);
		$body = ContentCleaner::wrapChinese($body);
		$view->setTag('content', $body);
		
		$view->setTag('url', $this->getURL());
		
		$date = $model->tool('datetime')->getDateTag($this->ts_unix);
		if ($user->isLoggedIn() and $this->user_id == $user->getUserID()) {
			$date_end = date('l, M j, Y', $this->ts_end_unix);
			
			if ($this->isWaiting()) {
				$message = sprintf(
					"You posted this ad %s. It is currently pending Moderator approval. It will expire %s.",
					$date,
					$date_end
				);
				$controls = $this->getControls();
			} elseif ($this->isLive()) {
				$message = sprintf(
					"You posted this ad %s. It will expire %s. It's had %d responses.",
					$date,
					$date_end,
					$this->responses
				);
				$controls = $this->getControls();
			} elseif ($this->isExpired()) $message = sprintf(
					"You posted this ad %s. It expired %s. It had %d responses.",
					$date,
					$date_end,
					$this->responses
				);
			elseif ($this->isAdminDeleted()) $message = sprintf(
					"You posted this ad %s. It was deleted by website Moderators. Please refer to the guidelines before posting.",
					$date
				);
			elseif ($this->isUserDeleted()) $message = sprintf(
					"You posted this ad %s. It received %d responses before you deleted it",
					$date,
					$date_end,
					$this->responses
				);
			$view->setTag('message', sprintf('<span class="userMessage">%s</span>', $message));
			$view->setTag('controls', $controls);
		} else $view->setTag('date', $this->getDateTag());
		
		if ($this->show_user)
			$view->setTag('name', $this->getUser()->getLinkedNickname());

		if ($this->show_path)
			$view->setTag('folder_path', $this->getPath());

		if ($this->show_respond_button)
			$view->setTag('respond', '<a class="icon-link" href="'.$this->getURL().'"><span class="icon icon-pencil"> </span> Respond</a>');

		if ($this->show_title)
			$view->setTag('title', $this->getTitle().'<br />');
			
		if ($user->isLoggedIn() and $user->getUserID() == $this->user_id)
			$view->setTag('controls', $this->getControls());
		
		return $view->getOutput();
	}

	public function getRSS() {
		$content = '<item>
<title>'.htmlspecialchars(nl2br(trim($this->title)), ENT_NOQUOTES, 'UTF-8').' by '.$this->getUser()->getNickname(false, true).'</title>
<link>'.$this->getAbsoluteURL().'</link>
<description>'.htmlspecialchars(nl2br(trim($this->body)), ENT_NOQUOTES, 'UTF-8').'</description>
<pubDate>'.str_replace('-0500', '+0800', date('r', $this->ts_unix)).'</pubDate>
<guid isPermaLink="true">'.$this->getAbsoluteURL().'</guid>
</item>';
		return $content;
	}

	private function getControls() {
		$controls = '';
		if ($this->isLive() || $this->isWaiting()) {
			$controls .= ' <a class="icon-link" href="/en/classifieds/edit/'.$this->classified_id.'/"><span class="icon icon-edit"> </span> Edit</a>
							<a class="icon-link" href="/en/classifieds/delete/'.$this->classified_id.'/"><span class="icon icon-trash"> </span> Delete</a>';
		}
		return $controls;
	}

	public function isLive() {
		return $this->status == 1 ? true : false;
	}

	public function isDeleted() {
		return in_array($this->status, array(3, 4));
	}

	public function isExpired() {
		return ($this->status == 5 or strtotime($this->ts_end) < time()) ? true : false;
	}

	public function isWaiting() {
		return $this->status == 2 ? true : false;
	}

	public function isAdminDeleted() {
		return $this->status == 4 ? true : false;
	}

	public function isUserDeleted() {
		return $this->status == 3 ? true : false;
	}

	public function save() {
		global $user, $model, $site;
		
		$this->title = ContentCleaner::cleanForDatabase($this->title);
		$this->body = ContentCleaner::cleanForDatabase($this->body);
		
		$result = false;
		
		if (
			$user->isLoggedIn() &&
			$this->title &&
			$this->body &&
			is_numeric($this->folder_id)
		) {
			$data = array(
				'folder_id' => $this->folder_id,
				'title' => $this->title,
				'body' => $this->body,
				'ts_end' => $this->ts_end,
				'status' => 2
			);
			
			if ($this->classified_id > 0) {
				$result = -1 < $model->db()->update('classifieds_data', array('classified_id' => $this->classified_id), $data);
			} else {
				$data['user_id'] = $user->getUserID();
				$data['ts'] = unixToDatetime();
				$id = $model->db()->insert('classifieds_data', $data);
				if ($result = $id > 0) $this->classified_id = $id;
			}
		}
		return $result;
	}

	public function saveAdmin() {

		$this->title = ContentCleaner::cleanProse($this->title);
		$this->body = ContentCleaner::cleanProse($this->body);

		$db = new DatabaseQuery;

		if (ctype_digit($this->classified_id)) {
			$db->execute("	UPDATE classifieds_data
							SET title = '".$db->clean($this->title)."',
								body = '".$db->clean($this->body)."',
								folder_id = $this->folder_id,
								status = 1
							WHERE classified_id = $this->classified_id");
		}
		else { // moved from forum - gets approved to boot
			$db->execute("	INSERT INTO classifieds_data (	user_id,
															ts,
															ts_end,
															title,
															body,
															folder_id,
															status)
							VALUES ($this->user_id,
									'$this->ts',
									DATE_ADD('$this->ts', INTERVAL 30 DAY),
									'".$db->clean($this->title)."',
									'".$db->clean($this->body)."',
									$this->folder_id,
									1)");
		}
	}

	public function displayAdminRow($get_values =  array()) {
		$content = "<tr valign=\"top\"".($this->isDeleted() ? ' class="fadeout"' : '').">
		<td>$this->classified_id</td>
		<td>$this->title<br />
		<span class=\"highlight\">".strip_tags($this->getPath())."</span><br />
		".nl2br($this->body)."
		</td>
		<td>$this->ts<br />
			Expires: $this->ts_end</td>
		<td><a target=\"_blank\" href=\"../public_users/form_user.php?user_id=$this->user_id\">$this->nickname</a></td>
		<td>$this->responses</td>
		<td>".$this->statuses[$this->status]."</td>
		<td>";

		$vars = ContentCleaner::buildGetString(array('classified_id' => $this->classified_id) + $get_values);

		$controls[] = "<a href=\"form_classifiedsitem.php$vars\">Edit</a>";

		if ($this->isLive())
			$controls[] = "<a href=\"control.php$vars&action=delete\">Delete</a>";
		else if ($this->isWaiting()) {
			$controls[] = "<a href=\"control.php$vars&action=delete\">Delete</a>";
			$controls[] = "<a href=\"control.php$vars&action=approve\">Approve</a>";
			$controls[] = "<a href=\"control.php$vars&action=always_approve\">Always approve</a>";
		}
		else if ($this->isAdminDeleted())
			$controls[] = "<a href=\"control.php$vars&action=undelete\">Undelete</a>";

		$content .= implode('<br />', $controls).'</td>
		</tr>';
		return $content;
	}

	public function getWaitingRow() {
		$content = "<tr valign=\"top\">
		<td>$this->title<br />
		<span class=\"highlight\">".strip_tags($this->getPath())."</span><br />
		".nl2br($this->body)."</td>
		<td>$this->ts<br />
			Expires: $this->ts_end</td>
		<td>
			<a target=\"_blank\" href=\"../public_users/form_user.php?user_id=$this->user_id\">$this->nickname</a><br />
			<a target=\"_blank\" href=\"list.php?ss=$this->nickname\">$this->nickname's classifieds</a>
		</td>
		<td>".$this->statuses[$this->status]."</td>
		<td><input type=\"checkbox\" name=\"classified_ids[]\" value=\"$this->classified_id\"></td>
		</tr>";
		return $content;
	}

	public function setPage($page) {
		$this->page = $page;
	}

	public function setSearchString($ss) {
		$this->ss = $ss;
	}

	public function displayAdminForm() {
		$content = FormHelper::open('../classifieds/form_classifiedsitem_proc.php')
		.FormHelper::hidden('classified_id', $this->classified_id)
		.FormHelper::hidden('page', $this->page)
		.FormHelper::hidden('ss', $this->ss)
		.FormHelper::hidden('thread_id', $this->thread_id)
		.FormHelper::hidden('user_id', $this->user_id)
		.FormHelper::hidden('ts', $this->ts);

		$f[] = FormHelper::input('Title', 'title', $this->title);
		$f[] = FormHelper::select('Folder', 'folder_id', ClassifiedsFolderList::getFolders(), $this->folder_id);
		$f[] = FormHelper::textarea('Text', 'body', $this->body);

		$content .= FormHelper::fieldset('Edit Classified: '.$this->title, $f)
		.FormHelper::submit('Save')
		.FormHelper::close();
		return $content;
	}

	public function incrementResponses() {
		$db = new DatabaseQuery;
		$db->execute('	UPDATE classifieds_data
						SET responses = responses + 1
						WHERE classified_id = '.$this->classified_id);
	}

	public function setLive($retime = false) {
		$db = new DatabaseQuery;
		$db->execute('	UPDATE classifieds_data
						SET status = 1
							'.($retime ? ", ts=NOW()" : '').'
						WHERE classified_id = '.$this->classified_id);
	}

	public function deleteAdmin() {
		$db = new DatabaseQuery;
		$db->execute('	UPDATE classifieds_data
						SET status = 4
						WHERE classified_id = '.$this->classified_id);
	}

	public function deleteUser($user) {
		$db = new DatabaseQuery;
		$db->execute('	UPDATE classifieds_data
						SET status = 3
						WHERE classified_id = '.$this->classified_id.'
						AND user_id = '.$user->getUserID());
	}

	private function getStatusText() {
		return ucfirst($this->statuses[$this->status]);
	}

	public function getUser() {
		return new User($this->user_id);
	}
}
?>