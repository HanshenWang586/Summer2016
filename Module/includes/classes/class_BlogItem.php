<?php
class BlogItem {
	private	$property_key = array(	'live' => 1,
		'allow_unregistered_user_comments' => 2,
		'allow_registered_user_comments' => 4);
	private $date_options = array('show_year' => true);

	public function __construct($blog_id = false) {
		$this->load($blog_id);
	}
	
	public function load($blog_id = false) {
		if ($blog_id && ctype_digit($blog_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT 	c.*,
				u.user_id,
				u.display_name,
				UNIX_TIMESTAMP(ts) AS ts_unix
				FROM blog_content c
				LEFT JOIN admin_users u ON (u.user_id = c.user_id)
				WHERE blog_id = '.$blog_id);
			$this->setData($rs->getRow());
		}
		else {
			global $admin_user;
			$this->ts_unix = time();
			$this->properties = 7;
		}

		$this->loadProperties();
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	public function getBlogID() {
		return $this->blog_id;
	}

	public function getGoogleNewsSiteMapEntry() {
		return '<url>
		<loc>'.$this->getAbsoluteURL().'</loc>
		<news:news>
		<news:publication>
		<news:name>'.htmlspecialchars($input, ENT_XML1) . '</news:name>
		<news:language>en</news:language>
		</news:publication>
		<news:title>'.htmlspecialchars($this->getTitle(), ENT_XML1).'</news:title>
		<news:publication_date>'.DateManipulator::convertUnixToFormat('c', $this->ts_unix).'</news:publication_date>
		<news:keywords>'.htmlspecialchars(implode(', ', $this->getTagsArray()), ENT_XML1).'</news:keywords>
		</news:news>
		</url>';
	}
	
	public function getImageByID($imageID) {
		$exts = array('jpg', 'png', 'gif');
		foreach ($exts as $ext) {
			$path = BLOG_PHOTO_STORE_FILEPATH.$imageID.'.'.$ext;
			if (file_exists($path)) return $path;
		}
		return false;
	}
	
	public function getImage($width = false, $height = false) {
		$path = false;
		preg_match("/#([0-9]+)#/", $this->content, $match);
		if ($match and $id = $match[1] and ($path = $this->getImageByID($id)) or $path = $GLOBALS['rootPath'] . '/assets/logo/logo-bg.png') {
			if ($path and ($width or $height)) {
				$path = $GLOBALS['model']->tool('image')->resize($path, $width, $height, false, true);
			}
		}
		return str_replace($GLOBALS['rootPath'], '', $path);
	}
	
	public function getCommentsLink($style = '', $options = array(), $newWindow = false) {
		$num = $this->getNumComments();
		if ($num) {
			$text = sprintf('comment%s', $num > 1 ? 's' : '');
			$style = $style ? sprintf(' style="%s"', $style) : '';
			return sprintf('<a%s%s class="commentsLink" href="%s" title="%d %s">%d <span>%s</span></a>', $newWindow ? ' target="_blank"' : '', $style, $this->getCommentsURL(false, $options), $num, $text, $num, $text);
		}
		return '';
	}
	
	public function displayBrief() {
		$content .= sprintf(
				'<article itemscope itemtype="http://schema.org/Article">
					<a itemprop="url" class="item clearfix" href="%s">
						<img itemprop="thumbnailUrl" width="90" height="90" src="%s" alt="image">
						<span class="blogCategory" itemprop="articleSection">%s</span>
						<h1 itemprop="name">%s</h1>
						<span class="bottomDescr">%s %s <span class="author" itemprop="author">%s</span></span>
					</a>
					%s
				</article>',
				$this->getURL(),
				$this->getImage(150, 150),
				$this->getCategory(),
				$this->getTitle(),
				$GLOBALS['model']->tool('datetime')->getDateTag($this->ts, 'published', 'datePublished'),
				$GLOBALS['model']->lang('BY_TEXT', 'BlogModel'),
				$this->getAuthor()->getDisplayName(),
				$this->getCommentsLink()
			);
		return $content;
	}
	
	public function DisplayHome() {
		$img = $this->getImage(403, 253);
		$category = $this->getCategory();
		
		$content = sprintf('
			<article itemscope itemtype="http://schema.org/Article">
				<a itemprop="url" class="blogImage img" href="%s">
					<header>
						<h2 itemprop="name">%s</h2>
						<span class="details">
							<span class="author" itemprop="author">%s</span>
							%s
						</span>
					</header>
					<span class="blogCategory" itemprop="articleSection">%s</span>
					<img itemprop="thumbnailUrl" width="403" height="253" src="%s" alt="image">
				</a>
				%s
			</article>',
			$this->getURL(),
			$this->getTitle(),
			$this->getAuthor()->getDisplayName(),
			$GLOBALS['model']->tool('datetime')->getDateTag($this->ts, 'published', 'datePublished'),
			$this->getCategory(),
			$img,
			$this->getCommentsLink()
		);
		
		return $content;
	}

	public function getLink() {
		return '<a href="'.$this->getURL().'">'.$this->getTitle().'</a>';
	}

	public function getDate() {
		return DateManipulator::convertUnixToFriendly($this->ts_unix, $this->date_options);
	}

	private function isFuture() {
		return $this->ts_unix > time() ? true : false;
	}

	public function getYMDate() {
		return DateManipulator::convertUnixToFormat('Y-m', $this->ts_unix);
	}

	public function getAuthorLinked() {
		return "<a itemprop=\"author\" class=\"url fn author\" rel=\"author\" href=\"/en/blog/poster/$this->user_id/\">".$this->getAuthor()->getDisplayName().'</a>';
	}

	public function getAuthor() {
		return new AdminUser($this->user_id);
	}

	public function getURL($usePageArgs = false, $options = array()) {
		return $GLOBALS['model']->url(array('m' => 'blog', 'view' => 'item', 'id' => $this->blog_id, 'name' => $this->getTitle()), $options, $usePageArgs);
	}

	public function getCommentsURL($usePageArgs = false, $options = array()) {
		return $this->getURL($usePageArgs, $options).'#comments';
	}

	public function getAbsoluteURL() {
		return $this->getURL();
	}

	public function getPrevNext() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
			FROM blog_content
			WHERE ts > '$this->ts'
			AND ts < NOW()
			ORDER BY ts ASC
			LIMIT 1");
		if ($rs->getNum()) {
			$bi = new BlogItem;
			$bi->setData($rs->getRow());
			$links[] = 'Next article: '.$bi->getLink();
		}

		$rs = $db->execute("SELECT *
			FROM blog_content
			WHERE ts < '$this->ts'
			ORDER BY ts DESC
			LIMIT 1");
		$bi = new BlogItem;
		$bi->setData($rs->getRow());
		$links[] = 'Previous article: '.$bi->getLink();

		return implode('<br />', $links);
	}

	public function getTitle() {
		return $this->title;
	}

	public function getBody() {
		return $this->processContent($this->content);
	}

	public function getCachedBody() {
		return $this->content_cache;
	}

	public function getTitleLinked($with_comments = false) {
		if ($with_comments)
			return '<a href="'.$this->getCommentsURL().'">'.$this->getTitle().'</a>';
		else
			return '<a href="'.$this->getURL().'">'.$this->getTitle().'</a>';
	}

	public function getAdminTitleLinked() {
		return '<a href="'.$this->getURL().'" target="_blank">'.$this->getTitle().'</a>';
	}

	function getTitleForURL() {
		return ContentCleaner::processForURL($this->getTitle());
	}

	public function displayComments() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT	c.*,
			IF(u.user_id = 0, c.nickname, u.nickname) AS nickname,
			UNIX_TIMESTAMP(ts) AS ts_unix
			FROM blog_comments c
			LEFT JOIN public_users u ON c.user_id = u.user_id
			WHERE blog_id = '.$this->blog_id.'
			AND live = 1
			ORDER BY ts ASC');

		$content = '';
		
		if ($rs->getNum() == 0)
			$content .= sprintf('<p>%s</p>', $GLOBALS['model']->lang('NO_COMMENTS', 'BlogModel'));
		else {
			$blog_comment = new BlogComment;
			while ($row = $rs->getRow()) {
				$blog_comment->setData($row);
				$content .= $blog_comment->displayPublic();
			}
		}

		return sprintf("<div class=\"userContentList\">%s</div>\n", $content);
	}
	
	public function displayCommentsForm() {
		$blog_comment = new BlogComment;
		$content .= $blog_comment->displayForm($this);
		return $content;
	}

	public function displayAdminRow() {
		//$bgcolor = $this->live == 1 ? '#ffffff' : '#dddddd';
		$content = "<tr valign=\"top\"".($this->isFuture() ? ' class="fadeout"' : '').">
		<td>$this->blog_id</td>
		<td>$this->site_name</td>
		<td>$this->title</td>
		<td>".str_replace(' ', '&nbsp;', date('j F Y', $this->ts_unix))."</td>
		<td>".str_replace(' ', '&nbsp;', $this->display_name)."</td>
		<td><a href=\"".$this->getAbsoluteURL()."\" target=\"_blank\">View</a></td>
		<td><a href=\"form_item.php?blog_id=$this->blog_id\">Edit</a></td>
		<td><a href=\"form_images.php?blog_id=$this->blog_id\">Images</a></td>
		<td><a href=\"delete_item.php?blog_id=$this->blog_id\" onClick=\"return conf_del()\">Delete</a></td>
		</tr>";
		return $content;
	}

	public function displayForm() {
		global $admin_user;

		$content = FormHelper::open('form_item_proc.php');
		$content .= FormHelper::hidden('blog_id', $this->blog_id);
		$content .= FormHelper::submit('Save');

		$s[] = FormHelper::select('Category', 'category_id', array(1 => 'News', 2 => 'Features', 3 => 'Travel'), $this->category_id);
		$content .= FormHelper::fieldset('Site', $s);

		$b[] = FormHelper::element('Posted&nbsp;by', $this->getPostAs($this->blog_id ? $this->user_id : $admin_user->getUserID()));
		$b[] = FormHelper::input('Title', 'title', $this->title);

		$dt_control = new DateTimeControl($this->ts_unix);
		$dt_control->setYearType('select');
		$dt_control->setPrefix('posted');
		$dt_control->setTimeLabel('Time ');

		$b[] = FormHelper::element('Date', $dt_control->display(), array('guidetext' => 'Beijing time'));
		$b[] = FormHelper::textarea('Content', 'content', $this->content, array('guidetext' => "&lt;b&gt;bold&lt;/b&gt;<br />
			&lt;i&gt;italic&lt;/i&gt;<br />
			<br />
			Link: #text#link# e.g. #Google#http://www.google.com#<br />
			Please remember the 'http://' part.<br />
			<br />
			Youku: #youku#Video ID# e.g. #youku#XMTA3MTgxOTk2#<br />
			<br />
			Tudou: #tudou#Video ID# e.g. #tudou#GMunl0piCMU#"));

		$content .= FormHelper::fieldset('Item', $b);

		$m[] = FormHelper::textarea('Tags',
			'tags',
			implode(', ', $this->getTagsArray()),
			array('onkeyup' => "blogSuggestTags()",
				'guidetext' => "<div id=\"suggested_tags\"></div>Please separate tags with commas",
				'style' => 'height: 150px;'));

		if ($this->blog_id) {
			$m[] = FormHelper::element('Related', FormHelper::button('Refresh', array('onclick' => "blogLoadRelated($this->blog_id)")), array('guidetext' => "<div id=\"related_articles\"></div>"));
		}
		else {
			$m[] = FormHelper::element('Related', 'Please save blog item first');
		}

		$m[] = FormHelper::element('Properties', $this->getPropertiesCheckboxes(), array());

		$content .= FormHelper::fieldset('Meta-information', $m);
		$content .= FormHelper::submit('Save');
		$content .= FormHelper::close().'<br />';

		return $content;
	}

	private function getPostAs($user_id) {

		global $admin_user;

		if ($admin_user->canPostAsOthers()) {
			$content .= '<select name="user_id">';
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT	user_id,
				CONCAT(given_name, ' ', family_name) AS name
				FROM admin_users
				ORDER BY family_name");

			while ($row = $rs->getRow()) {
				$content .= "<option value=\"{$row['user_id']}\"".($row['user_id'] == $user_id ? ' selected' : '').">{$row['name']}</option>";
			}

			$content .= '</select>';
		}
		else {
			$user = new AdminUser($user_id);
			$content .= $user->getName();
			$content .= FormHelper::hidden('user_id', $user_id);
		}

		return $content;
	}

	public function save() {
		$ts = $this->posted_yyyy.'-'.$this->posted_mm.'-'.$this->posted_dd.' '.$this->posted_hh.':'.$this->posted_min.':00';

		$this->content = ContentCleaner::cleanForDatabase($this->content);
		$this->title = ContentCleaner::cleanForDatabase($this->title);
		$db = new DatabaseQuery;

		if ($this->blog_id) {
			$db->execute("	UPDATE blog_content
				SET title = '".$db->clean($this->title)."',
				ts = '$ts' + INTERVAL 0 DAY,
				category_id = $this->category_id,
				content = '".$db->clean($this->content)."',
				user_id = $this->user_id,
				properties = ".$this->getPropertyScore().",
				content_cache = '".$db->clean($this->getBody())."',
				content_stripped = '".trim($db->clean(strip_tags($this->getBody())))."'
				WHERE blog_id = $this->blog_id");
			$this->deleteTags();
		}
		else {
			$db->execute("	INSERT INTO blog_content (	title,
				ts,
				category_id,
				content,
				user_id,
				properties,
				content_cache,
				content_stripped)
			VALUES (	'".$db->clean($this->title)."',
				'$ts' + INTERVAL 0 DAY,
				$this->category_id,
				'".$db->clean($this->content)."',
				$this->user_id,
				".$this->getPropertyScore().",
				'".$db->clean($this->getBody())."',
				'".trim($db->clean(strip_tags($this->getBody())))."')");
			$this->blog_id = $db->getNewID();
		}

		$this->saveTags();
	}

	public function rebuild() {
		if ($this->blog_id) {
			$db = new DatabaseQuery;
			$db->execute("	UPDATE blog_content
				SET content_cache = '".$db->clean($this->getBody())."',
				content_stripped = '".trim($db->clean(strip_tags($this->getBody())))."'
				WHERE blog_id = $this->blog_id");
		}
	}

	function delete() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT image_id
			FROM blog_images
			WHERE blog_id = '.$this->blog_id);

		while ($row = $rs->getRow()) {
			$bi = new BlogImage($row['image_id']);
			$bi->delete();
		}

		$db->execute('	DELETE FROM blog_content
			WHERE blog_id = '.$this->blog_id);
		$db->execute('	DELETE FROM blog_tags
			WHERE blog_id = '.$this->blog_id);
		$db->execute('	DELETE FROM blog_related
			WHERE blog_id = '.$this->blog_id);
	}

	private function processContent($content) {
		$content = ContentCleaner::cleanForDatabase($content);
		$content = ContentCleaner::wrapChinese($content);
		
		$content = $this->processVideo($content);
		$content = $this->processYouku($content);
		$content = $this->processYoutube($content);
		$content = $this->processTudou($content);
		$content = $this->processLinks($content);
		$content = $this->processImages($content);
		$content = $this->processBlogGalleries($content);
		$content = $this->processQuotes($content);
		$content = ContentCleaner::PWrap($content);

		$content = str_replace('<p><div', '<div', $content);
		$content = str_replace('</div></p>', '</div>', $content);
		$content = str_replace('<p>', "\n\n<p>", $content);
		$content = trim($content);
		return $content;
	}

	private function processLinks($content) {
		global $model;
		preg_match_all("/#.+?#.+?#/", $content, $matches);

		foreach($matches[0] as $match) {
			$target = '';
			$match_ip = explode('#', trim($match, '#'));
			$match_ip[1] = trim($match_ip[1]);
			
			$siteURL = $model->module('preferences')->get('url');
			
			if (strpos($match_ip[1], 'http://') === 0 && strpos($match_ip[1], 'http://' . $siteURL) !== 0)
				$target = ' target="_blank"';
			else
				$match_ip[1] = str_replace('http://' . $siteURL, '', $match_ip[1]);

			$link = '<a href="'.trim(str_replace('||', '#', $match_ip[1]))."\"$target>".trim($match_ip[0]).'</a>';
			$content = str_replace($match, $link, $content);
		}

		return $content;
	}

	private function processImages($content) {
		preg_match_all('/#([0-9]+)#/', $content, $matches);

		foreach ($matches[1] as $image_id) {
			$ni = new BlogImage($image_id);
			$content = preg_replace("/\s*#$image_id#\s*/", "\n\n#$image_id#\n\n", $content);
			$content = preg_replace("/#$image_id#/", $ni->getEmbeddable(), $content);
		}

		return trim($content);
	}

	private function processQuotes($content) {
		$content = preg_replace("/\s*\[quote\]\s*/", "\n\n<div class=\"blog_quote\"><p>", $content);
		$content = preg_replace('%\s*\[/quote\]\s*%', "</p></div>\n\n", $content);
		return trim($content);
	}

	private function processYouku($content) {
		preg_match_all("/#youku#.+?#/", $content, $matches);

		foreach($matches[0] as $match) {
			$match_ip = explode('#', trim($match, '#'));
			$id = trim($match_ip[1]);
			$content = preg_replace("|\s*$match\s*|", "\n\n$match\n\n", $content);
			$code = "<div class=\"blog_video\"><iframe height=300 width=\"100%\" src=\"http://player.youku.com/embed/$id\" frameborder=0 allowfullscreen></iframe></div>";
			$content = str_replace($match, $code, $content);
		}

		return trim($content);
	}

	private function processYoutube($content) {
		preg_match_all("/#youtube#.+?#/", $content, $matches);

		foreach($matches[0] as $match) {
			$match_ip = explode('#', trim($match, '#'));
			$youtube_code = trim($match_ip[1]);
			$content = preg_replace("/\s*$match\s*/", "\n\n$match\n\n", $content);
			$code = "<div class=\"blog_video\"><object width=\"450\" height=\"370\"><param name=\"movie\" value=\"http://www.youtube.com/v/$youtube_code\"></param><param name=\"wmode\" value=\"transparent\"></param><embed src=\"http://www.youtube.com/v/$youtube_code\" type=\"application/x-shockwave-flash\" wmode=\"transparent\" width=\"340\" height=\"280\"></embed></object></div>";
			$content = str_replace($match, $code, $content);
		}

		return trim($content);
	}

	private function processTudou($content) {
		preg_match_all("/#tudou#.+?#/", $content, $matches);

		foreach($matches[0] as $match) {
			$match_ip = explode('#', trim($match, '#'));
			$id = trim($match_ip[1]);
			$content = preg_replace("/\s*$match\s*/", "\n\n$match\n\n", $content);
			$code = "<div class=\"blog_video\"><object width=\"450\" height=\"389\"><param name=\"movie\" value=\"http://www.tudou.com/v/$id\"></param><param name=\"allowFullScreen\" value=\"true\"></param><param name=\"allowscriptaccess\" value=\"always\"></param><param name=\"wmode\" value=\"opaque\"></param><embed src=\"http://www.tudou.com/v/$id\" type=\"application/x-shockwave-flash\" allowscriptaccess=\"always\" allowfullscreen=\"true\" wmode=\"opaque\" width=\"368\" height=\"318\"></embed></object></div>";
			$content = str_replace($match, $code, $content);
		}

		return trim($content);
	}

	private function processVideo($content) {
		preg_match_all("/#video#[0-9]+?#/", $content, $matches);

		foreach ($matches[0] as $match) {
			$match_ip = explode('#', trim($match, '#'));
			$video_id = trim($match_ip[1]);
			$video = new BlogVideo($video_id);
			$content = preg_replace("/\s*$match\s*/", "\n\n$match\n\n", $content);
			$content = str_replace($match, '<div class="blog_video_inhouse">'.$video->getEmbeddable().'</div>', $content);
		}

		return trim($content);
	}

	private function processBlogGalleries($content) {
		preg_match_all("/#gallery#[0-9]+?#/", $content, $matches);

		foreach ($matches[0] as $match) {
			$match_ip = explode('#', trim($match, '#'));
			$blog_gallery_id = trim($match_ip[1]);
			$blog_gallery = new BlogGallery($blog_gallery_id);
			$content = preg_replace("/\s*$match\s*/", "\n\n$match\n\n", $content);
			$content = str_replace($match, $blog_gallery->getEmbeddable(), $content);
		}

		return trim($content);
	}

	private function stripImages($content) {
		preg_match_all("/#([0-9]+)#/", $content, $matches);
		foreach ($matches[1] as $image_id) {
			$content = preg_replace("/#$image_id#\s*/", '', $content);
		}

		return $content;
	}

	private function stripVideo($content) {
		preg_match_all("/#tudou#(.+?)#/", $content, $matches);
		foreach ($matches[1] as $image_id) {
			$content = preg_replace("/#tudou#$image_id#\s*/", '', $content);
		}

		preg_match_all("/#youku#(.+?)#/", $content, $matches);
		foreach ($matches[1] as $image_id) {
			$content = preg_replace("/#youku#$image_id#\s*/", '', $content);
		}

		preg_match_all("/#youtube#(.+?)#/", $content, $matches);
		foreach ($matches[1] as $image_id) {
			$content = preg_replace("/#youtube#$image_id#\s*/", '', $content);
		}

		preg_match_all("/#video#(.+?)#/", $content, $matches);
		foreach ($matches[1] as $image_id) {
			$content = preg_replace("/#video#$image_id#\s*/", '', $content);
		}

		return $content;
	}

	private function getRSSVersion($content) {
		// images
		preg_match_all("/#([0-9]+)#/", $content, $matches);

		foreach ($matches[1] as $image_id) {
			$content = str_replace("#$image_id#", '', $content);
		}

		/* youtube */
		preg_match_all("/#youtube#.+?#/", $content, $matches);

		foreach($matches[0] as $match)
		{
			$match_ip = explode('#', trim($match, '#'));
			$youtube_code = trim($match_ip[1]);
			$youtube = "<center><object width=\"340\" height=\"280\"><param name=\"movie\" value=\"http://www.youtube.com/v/$youtube_code\"></param><param name=\"wmode\" value=\"transparent\"></param><embed src=\"http://www.youtube.com/v/$youtube_code\" type=\"application/x-shockwave-flash\" wmode=\"transparent\" width=\"340\" height=\"280\"></embed></object></center>";
			$content = str_replace($match, $youtube, $content);
		}

		/* links */
		preg_match_all("/#.+?#.+?#/", $content, $matches);

		foreach($matches[0] as $match) {
			$match_ip = explode('#', trim($match, '#'));
			$content = str_replace($match, "<a href=\"{$match_ip[1]}\" target=\"_blank\">{$match_ip[0]}</a>", $content);
		}
		return nl2br(trim($content));
	}

	public function getRSS() {
		$view = new View;
		$view->setPath('blog/rss/item.html');
		$view->setTag('title', $this->title);
		$view->setTag('absolute_url', $this->getAbsoluteURL());
		$view->setTag('description', $this->getRSSVersion($this->content));
		$view->setTag('pubdate', date('r', strtotime($this->ts)));
		return $view->getOutput();
	}

	private function deleteTags() {
		$db = new DatabaseQuery;
		$db->execute('	DELETE FROM blog_tags
			WHERE blog_id = '.$this->blog_id);
	}

	private function saveTags() {
		$tags = explode(',', $this->tags);

		foreach($tags as $tag)
			$tags_trimmed[] = trim($tag);

		$tags = array_unique($tags_trimmed);

		if (is_array($tags)) {
			foreach ($tags as $tag) {
				if (strlen($tag)) {
					$db = new DatabaseQuery;
					$db->execute("	INSERT INTO blog_tags (blog_id, tag, classic)
						VALUES ($this->blog_id, '".$db->clean($tag)."', 0)");
				}
			}
		}
	}

	public function getTagsArray() {
		if (ctype_digit($this->blog_id)) {
			return $GLOBALS['model']->db()->query('blog_tags', array('blog_id' => $this->blog_id), array('transpose' => 'tag', 'orderBy' => 'tag'));
		}

		return array();
	}

	function loadTags() {
		$tags = array();

		if (ctype_digit($this->blog_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT tag
				FROM blog_tags
				WHERE blog_id = $this->blog_id
				ORDER BY tag");

			while ($row = $rs->getRow()) {
				$tags[] = $row['tag'];
			}
		}

		$this->tags = $tags;
	}

	function hasTags() {
		return count($this->tags);
	}

	private function getTagsLinkedArray($itemprop = false) {
		$tags_linked = array();

		if (!is_array($this->tags)) {
			$this->tags = $this->getTagsArray();
		}
		
		$itemprop = $itemprop ? ' itemprop="keywords"' : '';
		foreach ($this->tags as $tag) {
			$tags_linked[] = '<a' . $itemprop . ' rel="tag" href="/en/blog/tag/'.urlencode($tag).'">'.ContentCleaner::wrapChinese($tag).'</a>';
		}

		return $tags_linked;
	}

	public function getRelatedArticles() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT c.title, c.content, c.blog_id, c.category_id
			FROM blog_content c
			LEFT JOIN blog_related r ON (r.related_id = c.blog_id)
			WHERE r.blog_id = $this->blog_id
			ORDER BY ts DESC");

		while ($row = $rs->getRow()) {
			$bi = new BlogItem;
			$bi->setData($row);
			$img = sprintf('<img itemprop="thumbnailUrl" src="%s" alt="%s">', $bi->getImage(180, 180), addslashes(strip_tags($bi->getTitle())));
			
			$content .= sprintf('
				<article itemscope itemtype="http://schema.org/Article">
					<a itemprop="url" href="%s">
						<h1 itemprop="name">%s</h1>
						<span class="blogCategory" itemprop="articleSection">%s</span>
						%s
					</a>
				</article>',
				$bi->getURL(),
				$bi->getTitle(),
				$bi->getCategory(),
				$img
			);
			
			$ra[] = sprintf('<a class="img" href="%s">%s<span class="blogCategory">%s</span><span class="caption">%s</span></a>',
				$bi->getURL(),
				$img,
				$bi->getCategory(),
				$bi->getTitle()
			);
		}
		return $content;
		if (count($ra)) return HTMLHelper::wrapArrayInUl($ra, false, 'relatedArticles horizontalScroll');
	}

	public function getTags() {
		$tags = $this->getTagsLinkedArray(true);
		
		return HTMLHelper::wrapArrayInUl($tags);
	}

	public function getNumComments() {
		if (!isset($this->num_comments)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT COUNT(*) AS num_comments
				FROM blog_comments
				WHERE blog_id = '.$this->blog_id.'
				AND live = 1');

			$row = $rs->getRow();
			$this->num_comments = $row['num_comments'];
		}

		return $this->num_comments;
	}

	function getPropertiesCheckboxes() {
		foreach ($this->property_key as $key => $value) {
			$content .= "<input type=\"checkbox\" name=\"properties[$key]\" value=\"1\"".($this->checkProperty($key) ? ' checked' : '')."> ".ucfirst(str_replace('_', ' ', $key))."<br />";
		}

		return $content;
	}

	function loadProperties() {
		$properties = array();

		foreach ($this->property_key as $key => $value) {
			$properties[$key] = $this->properties & $value ? true : false;
		}

		$this->properties = $properties;
	}

	function getPropertyScore() {
		$property_score = 0;

		if (is_array($this->properties)) {
			foreach ($this->property_key as $key => $value) {
				if (in_array($key, array_keys($this->properties))) {
					$property_score += $value;
				}
			}
		}

		return $property_score;
	}

	function checkProperty($tag) {
		return $this->properties[$tag];
	}

	function addComment($comment) {
		if ($this->checkProperty('live')) {
			if ($comment->user->getUserID() == 0 && $this->checkProperty('allow_unregistered_user_comments')) {
				$comment->save();
			}
			else if ($comment->user->getUserID() != 0 && $this->checkProperty('allow_registered_user_comments')) {
				$comment->save();
			}
		}
	}

	public function displaySTFForm() {
		$content = FormHelper::open('/en/blog/proc_stf/');
		$content .= FormHelper::hidden('blog_id', $this->blog_id);
		$content .= FormHelper::input('', 'email', '');
		$content .= FormHelper::submit('Send');
		$content .= FormHelper::close();
		return $content;
	}

	public function getFirstPara() {
		$content = trim($this->stripImages($this->content));
		$content = $this->stripVideo($content);
		$paras = explode("\n\n", $content);
		return ContentCleaner::wrapChinese($this->processLinks($paras[0]));
	}
	
	public function getCategory() {
		$types = array(1 => 'News',
			2 => 'Features',
			3 => 'Travel');
		return $this->category_id ? $types[$this->category_id] : '';
	}

	private function getCategorySelect($category_id) {
		$types = array(1 => 'News',
			2 => 'Features',
			3 => 'Travel');
		$content = "";
		foreach ($types as $type) {

		}
	}
}
?>