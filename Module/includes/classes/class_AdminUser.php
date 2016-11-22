<?php
class AdminUser {

	/**
	 * @var array
	 */
	private $permissions = array();
	
	/**
	 *	@var int default live value
	 */
	private $live = 1;

	public function __construct($user_id = '') {
		if (is_numeric($user_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('	SELECT *
									FROM admin_users
									WHERE user_id = '.$user_id);
			$data = $rs->getRow();
			
			if (!$data) return;
			
			$this->setData($data);
			
			// get open-to-all module access permissions
			$rs = $db->execute('	SELECT module_id
									FROM admin_modules
									WHERE open_to_all = 1');
			
			while ($row = $rs->getRow())
				$this->permissions[] = $row['module_id'];

			// get regular module access permissions
			$rs = $db->execute('	SELECT module_id
									FROM admin_permissions
									WHERE user_id = '.$this->user_id);

			while ($row = $rs->getRow())
				$this->permissions[] = $row['module_id'];
		}
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value)
				$this->$key = $value;
		}
	}

	public function displayForm() {
		$content = FormHelper::open('form_user_proc.php');
		$content .= FormHelper::submit();
		$content .= FormHelper::hidden('user_id', $this->user_id);
		
		$f[] = FormHelper::radio('Live', 'live', array(0 => 'No', 1 => 'Yes'), $this->live);
		$content .= FormHelper::fieldset('Site', $f);
		
		$f[] = FormHelper::input('Email address', 'username', $this->username);
		$f[] = FormHelper::password('Password', 'password', $this->password);
		$f[] = FormHelper::input('Given name', 'given_name', $this->given_name);
		$f[] = FormHelper::input('Family name', 'family_name', $this->family_name);
		$f[] = FormHelper::input('Display name', 'display_name', $this->display_name);
		$content .= FormHelper::fieldset('User', $f);
		
		$f[] = FormHelper::checkbox_array('Modules', 'permissions', AdminModuleList::getArray(), $this->permissions);
		$content .= FormHelper::fieldset('Permissions', $f);
		$content .= FormHelper::submit();
		$content .= FormHelper::close();
		
		return $content;
	}

	function save() {
		$db = new DatabaseQuery;
		$this->live = $this->live==1 ? 1 : 0;

		if ($this->user_id!='') {
			$db->execute("	UPDATE admin_users
							SET given_name='$this->given_name',
								family_name='$this->family_name',
								display_name='$this->display_name',
								username='$this->username',
								password='$this->password',
								live=$this->live
							WHERE user_id=$this->user_id");
		}
		else {
			$db->execute("	INSERT INTO admin_users (	given_name,
														family_name,
														display_name,
														username,
														password,
														live)
							VALUES (	'$this->given_name',
										'$this->family_name',
										'$this->display_name',
										'$this->username',
										'$this->password',
										$this->live)");
			$this->user_id = $db->getNewID();
		}

		// set module access permissions
		$db->execute("	DELETE FROM admin_permissions
						WHERE user_id=$this->user_id");

		foreach ($this->permissions as $module_id) {
			$db->execute("	INSERT INTO admin_permissions (user_id, module_id)
							VALUES ($this->user_id, $module_id)");
		}
	}

	function displayPublic() {
		$size = @getimagesize(TEAM_PHOTO_STORE_FILEPATH.$this->user_id.'.jpg');
		
		$content = '';
		
		if ($size) {
			$imageURL = TEAM_PHOTO_STORE_URL . $this->user_id . '.jpg';
			$content = sprintf('<span class="imageWrapper"><img class="photo" src="%s" %s></span>', $imageURL, $size[3]);
		}

		$content .= sprintf('<h2><a class="url fn" href="/en/blog/poster/%d/">%s</a></h2>', $this->user_id, $this->display_name);
		if ($this->bio) $content .= sprintf('<p class="note bio">%s</p>', ContentCleaner::linkHashURLs($this->bio));
		return $content;
	}
	
	public function displayFormer() {
		$content = "<a href=\"/en/blog/poster/$this->user_id/\" class=\"arrow_right\">$this->display_name</a>";
		return $content;
	}

	function getUserID() {
		return $this->user_id;
	}

	function getName() {
		return $this->given_name.' '.$this->family_name;
	}

	public function getDisplayName() {
		return $this->display_name;
	}

	function canPostAsOthers() {
		return $this->post_as;
	}

	function getPermissions() {
		return $this->permissions;
	}

	public function getAuthorLinked() {
		return "<a itemprop=\"author\" class=\"url fn author\" rel=\"author\" href=\"/en/blog/poster/$this->user_id/\">".$this->getDisplayName().'</a>';
	}

	function getCallbacks() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM contact_todos
							WHERE user_id = $this->user_id
							AND type = 'callback'
							AND ts_target <= NOW()
							ORDER BY ts_target DESC");

		while ($row = $rs->getRow()) {
			$contact  = new Contact($row['contact_id']);
			$content .= '<b>'.$contact->getBriefName().'</b><br />';
			$content .= nl2br($row['todo']).'<br />'.
			$row['ts_target'].'<br /><br />';
		}

		return $content;
	}
	
	/**
	 * @return bool Based on the permissions array, whether this user is allowed access to this module
	 */
	public function isAllowedAccess($module_id) {
		return in_array($module_id, $this->permissions) ? true : false;
	}
}
?>