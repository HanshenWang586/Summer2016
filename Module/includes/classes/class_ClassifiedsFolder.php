<?php
class ClassifiedsFolder {

	private $folder_id;
	private $parent_id;
	public $icons = array(
		4 => 'shop',
		8 => 'home',
		1 => 'suitcase',
		16 => 'info3',
		7 => 'comments',
		18 => 'graduate',
		22 => 'home',
		5 => 'uniE05D',
		6 => 'pig',
		22 => 'bed',
		13 => 'office-2',
		14 => 'key',
		17 => 'tie',
		3 => 'businesscard2',
		26 => 'female',
		9 => 'male',
		25 => 'man',
		27 => 'woman',
		11 => 'smiley',
		19 => 'book-2',
		20 => 'book',
		12 => 'bubbles'
	);

	public function __construct($folder_id = '') {
		if (ctype_digit($folder_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
								FROM classifieds_folders
								WHERE folder_id = '.$folder_id);
			$this->setData($rs->getRow());
		}
		else
			$this->folder_id = 0;
	}

	public function setData($row) {
		if (is_array($row)) {
			foreach($row as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	public function getFolderID() {
		return $this->folder_id;
	}

	public function getNumSubscriptions() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT COUNT(*) AS tally
							FROM classifieds_subscriptions
							WHERE folder_id = '.$this->folder_id);
		$row = $rs->getRow();
		return $row['tally'];
	}

	public function hasChildren() {
		if (!isset($this->hasChildren)) {
			$this->hasChildren = $GLOBALS['model']->db()->count('classifieds_folders', array('parent_id' => $this->folder_id));
		}
		return $this->hasChildren;
	}
	
	public function showFolderListing() {
		global $model;
		$content = '';
		$folders = $model->db()->query('classifieds_folders', array('parent_id' => $this->folder_id), array('orderBy' => 'folder_en'));
		$cf = new ClassifiedsFolder;
		foreach ($folders as $row) {
			$cf->setData($row);
			$content .= $cf->displayMenuLink(true);
		}
		return $content;
	}

	public function displayPosts($pager = '', $search = false) {
		global $model;
		$content = '';
		
		if ($this->hasChildren()) {
			$ids = $this->getRecursiveChildren();
			array_unshift($ids, $this->folder_id);
			$ids = 'IN (\'' . implode('\',\'', $ids) . '\')';
		} else $ids = '= ' . $this->folder_id;
		
		if ($search) {
			$q = $model->db()->escape_clause($search);
			$select = sprintf("
				CASE WHEN title LIKE '%%%s%%' THEN 1 ELSE 0 END AS titlematch,
				MATCH (title, body) AGAINST ('%s') AS score,
			", $q, $q);
			$args = sprintf("AND MATCH (title, body) AGAINST ('%s')", $q);
			$order = 'HAVING score > 1 ORDER BY titlematch DESC, score DESC';
		} else $order = 'ORDER BY ts DESC';
		
		$sql = "SELECT *,
					d.status AS status,
					$select
					UNIX_TIMESTAMP(ts) AS ts_unix,
					UNIX_TIMESTAMP(ts_end) AS ts_end_unix
				FROM classifieds_data d
				LEFT JOIN public_users u ON u.user_id = d.user_id
				WHERE folder_id $ids
				$args
				AND d.status = 1
				AND ts_end > NOW()
				$order";
		
		$rs = $pager->setSQL($sql);
		
		if ($rs->getNum() == 0) {
			if ($search) $content .= '<p class="pageWrapper infoMessage">Your search query did not give any results.</p>';
			else $content .= '<p class="pageWrapper infoMessage">This category is currently empty.</p>';
		}
		
		$ci = new ClassifiedsItem;
		while ($row = $rs->getRow()) {
			$ci->setData($row);
			$content .= $ci->displayPublic(true);
		}
		
		return $content;
	}

	public function getURL() {
		return $GLOBALS['model']->url(array('m' => 'classifieds', 'view' => 'folder', 'id' => $this->folder_id, 'name' => $this->getTitle()));
	}
	
	public function getCreateNewURL() {
		return $GLOBALS['model']->url(array('m' => 'classifieds', 'view' => 'post', 'id' => $this->folder_id));
	}

	public function getTitle() {
		return $this->folder_en;
	}

	public function getLink() {
		return '<a href="'.$this->getURL().'">'.$this->getTitle().'</a>';
	}

	public function getDescription() {
		return $this->description;
	}
	
	public function getIcon() {
		$icon = array_get_set($this->icons, array($this->folder_id, $this->parent_id), '');
		return $icon ? sprintf('<span class="icon icon-%s"> </span>', $icon) : '';
	}
	
	public function displayMenuLink() {
		return sprintf('
			<article>
				<a href="%s">
					%s
					<h1>%s (%d)</h1>
					<p>%s</p>
				</a>
			</article>',
				$this->getURL(),
				$this->getIcon(),
				$this->getTitle(),
				$this->getRecursiveCount(),
				$this->getDescription()
			);
	}

	/*public function displayMenuLinkMobile(){
		return '<h4>'.$this->getLink().'</h4>'.$this->getDescription();
	}*/
	
	private function getRecursiveChildren($folder_id = false) {
		global $model;
		$folder_id = $folder_id ? $folder_id : $this->folder_id;
		$ids = $model->db()->query('classifieds_folders', array('parent_id' => $folder_id), array('transpose' => 'folder_id'));
		if ($ids) {
			$_ids = array();
			foreach($ids as $id) {
				$__ids = $this->getRecursiveChildren($id);
				if ($__ids) $_ids = array_merge($_ids, $__ids);
			}
			$ids = array_merge($ids, $_ids);
		}
		return $ids;
	}
	
	private function getRecursiveCount() {
		$db = new DatabaseQuery;
		$count = 0;
		$rs = $db->execute('SELECT folder_id
							FROM classifieds_folders
							WHERE parent_id = '.$this->folder_id);

		if ($rs->getNum() == 0) {
			$count += $this->getCount();
		} else {
			while ($row = $rs->getRow()) {
				$cf = new ClassifiedsFolder;
				$cf->setData($row);
				$count += $cf->getRecursiveCount();
			}
		}

		return $count;
	}

	private function getCount() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT COUNT(*) AS tally
							FROM classifieds_data
							WHERE folder_id = $this->folder_id
							AND ts_end > NOW()
							AND status = 1");
		$row = $rs->getRow();
		return $row['tally'];
	}

	public function getPath() {
		$parent_id = $this->folder_id; // we know this is non-zero
		$folder_id = $this->folder_id;
		$db = new DatabaseQuery;

		while ($parent_id > 0) {
			$rs = $db->execute('SELECT *
								FROM classifieds_folders
								WHERE folder_id = '.$folder_id);
			$row = $rs->getRow();

			// for the benefit of this loop
			$parent_id = $row['parent_id'];
			$folder_id = $row['parent_id'];

			// for the benefit of doing something useful
			$cf = new ClassifiedsFolder;
			$cf->setData($row);
			$path[] = '<a href="'.$cf->getURL().'">'.$cf->getTitle().'</a>';
		}

		$path[] = '<a href="/en/classifieds/">Classifieds</a>';
		return "<span class=\"breadcrum\">" . implode(' > ', array_reverse($path)) . "</span>";
	}

	private function getAds() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT 	*,
									UNIX_TIMESTAMP(ts) AS ts_unix
							FROM classifieds_data d, public_users u
							WHERE folder_id = '.$this->folder_id.'
							AND d.status = 1
							AND u.user_id = d.user_id
							AND ts_end > NOW()
							ORDER BY ts DESC');

			while ($row = $rs->getRow()) {
				$ci = new ClassifiedsItem;
				$ci->setData($row);
				$ads[] = $ci;
			}

		return $ads;
	}

	public function getRSS() {
		$ads = $this->getAds();

		foreach ($ads as $ad) {
			$items[] = $ad->getRSS();
		}

		return implode('', $items);
	}

	public function userIsSubscribed($user) {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT *
							FROM classifieds_subscriptions
							WHERE user_id = '.$user->getUserID().'
							AND folder_id = '.$this->folder_id);
		return $rs->getNum();
	}


	public function subscribeUser($user) {
		if (!$this->userIsSubscribed($user)) {
			$db = new DatabaseQuery;
			$db->execute('INSERT INTO classifieds_subscriptions (user_id, folder_id)
						 VALUES ('.$user->getUserID().', '.$this->folder_id.')');
		}
	}

	public function unsubscribeUser($user) {
		$db = new DatabaseQuery;
		$db->execute('DELETE FROM classifieds_subscriptions
					WHERE user_id = '.$user->getUserID().'
					AND folder_id = '.$this->folder_id);
	}

	public function sendSubscribeEmails($classified_id) {
		global $user;
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT user_id
							FROM classifieds_subscriptions
							WHERE folder_id = '.$this->folder_id);

		$classified = new ClassifiedsItem($classified_id);
		$subject = 'New Classified Ad: '.$classified->getTitle();

		$notification = new View;
		$notification->setPath('classifieds/notification.html');
		$notification->setTag('url', $classified->getAbsoluteURL());
		$notification->setTag('title', $classified->getTitle());
		$notification->setTag('body', $classified->getBody());
		$message = $notification->getOutput();

		while ($row = $rs->getRow()) {
			if ($row['user_id'] != $user->getUserID()) {
				$p_user = new User($row['user_id']);
				$p_user->sendEmail($subject, $message);
			}
		}
	}
}
?>