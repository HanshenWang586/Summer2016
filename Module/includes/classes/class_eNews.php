<?php
class eNews {
	private $googleCampaign;
	private $googleCampaignArgs;
	
	public function __construct($enews_id = false) {
		global $model;
		if ($enews_id) $this->load($enews_id);
		
		$this->googleCampaignArgs = array(
			'utm_source' => 'newsletter',
			'utm_medium' => 'email',
			'utm_campaign' => 'enews-' . unixToDate()
		);
		$this->googleCampaign = '?' . http_build_query($this->googleCampaignArgs);
	}
	
	public function load($enews_id) {
		if (is_numeric($enews_id)) {
			if ($data = $GLOBALS['model']->db()->query('enews', array('enews_id' => $enews_id), array('singleResult' => true))) {
				$this->setData($data);
				$this->message_text = $this->text;
				return true;
			}
		}
		return false;
	}
	
	public static function getStartForm() {
		global $admin_user;
		$content = FormHelper::open('build.php', array('method' => 'get'));
		$content .= FormHelper::fieldset('Select Site', $f);
		$content .= FormHelper::submit('Continue >');
		$content .= FormHelper::close();
		return $content;
	}

	function seteNewsID($enews_id) {
		$this->enews_id = $enews_id;
	}

	public function setData($row) {
		if (is_array($row)) {
			foreach($row as $key => $value)
				$this->$key = $value;
		}
	}

	function getSubject() {
		global $model;
		return $model->lang('SITE_NAME').' Weekend Update: '.date('l, F j, Y');
	}

	function getEditedSubject() {
		return stripslashes($this->subject);
	}

	function getBaseURL() {
		return 'http://' . $GLOBALS['model']->module('preferences')->get('url');
	}

	private function getColour() {
		return '#555';
	}

	private function getLinkColour() {
		return '#0982df';
	}

	private function sortWrappedChinese($text) {
		return str_replace('class="chinese"', 'style="font-size:1.2em;"', $text);
	}

	public function displayForm() {
		
		$content .= FormHelper::open('build_proc.php');
		$content .= FormHelper::submit('Continue >');
		$content .= FormHelper::hidden('enews_id', $this->enews_id);
		
		$f[] = FormHelper::input('Subject', 'subject', $this->getSubject());
		$f[] = FormHelper::textarea('Message', 'message_text', $this->getMessageText());
		$f[] = FormHelper::submit('Continue >');
		$content .= FormHelper::fieldset('eNews', $f);
		
		$content .= FormHelper::close();
		return $content;
	}

	function getMessage() {
		global $model;
		return "This week...";
	}

	private function getMessageText() {
		return $this->message_text ? $this->message_text : "This week...";
	}

	function buildBlogItems($items) {
		$bi = new BlogItem;
		$content = '<table  border="0" cellspacing="0" cellpadding="0"><tbody>';
		foreach ($items as $item) {
			$bi->setData($item);
			$url = $bi->getAbsoluteURL() . $this->googleCampaign;
			$comments = $bi->getCommentsLink('color: #df5858; text-decoration: none;', array('extraParams' => $this->googleCampaignArgs), true);
			if ($comments) $comments = ' • ' . $comments;
			//echo "<br /><br />";
			//$content .= "<a href=\"".$bi->getAbsoluteURL()."\" target=\"_blank\" style=\"font-weight: bold; color:".$this->getLinkColour()."; text-decoration:none\">".$bi->getTitle()."</a><br />".date('l, jS F Y', $bi->ts_unix);
			$content .= sprintf('
				<tr>
					<td><a href="%s"><img border="0" width="50" height="50" src="%s" alt="Article image" /></a></td>
					<td width="5">&nbsp;</td>
					<td>
						<a href="%s" target="_blank" style="font-weight: 600; color: %s; text-decoration:none">%s</a><br />
						<span style="font-size: 12px;">
							<span style="color: #777;">%s</span>
							%s
						</span>
					</td>
				</tr>
				<tr><td height="5" valign="top"></td></tr>
			',
				$url,
				$GLOBALS['model']->urls['root'] . $bi->getImage(120, 120),
				$url,
				$this->getLinkColour(),
				$bi->getTitle(),
				date('l, F j', strtotime($bi->ts)),
				$comments
			);
		}
		$content .= '</tbody></table>';

		return $content;
	}

	private function buildClassifieds($items) {
		$content = '<table  border="0" cellspacing="0" cellpadding="0"><tbody>';
		$ci = new ClassifiedsItem;
		foreach ($items as $item) {
			$ci->setData($item);
			$content .= 
			"<tr>
				<td>
					<a href=\"".$ci->getAbsoluteURL() . $this->googleCampaign."\" target=\"_blank\" style=\"font-weight: 600; color:".$this->getLinkColour()."; text-decoration:none\">".$this->sortWrappedChinese($ci->getTitle())."</a><br />
					<span style=\"font-size: 12px; color: #777;\">" . date('l, F j', strtotime($ci->ts)) . "</span>
				</td>
			</tr><tr><td height=\"5\" valign=\"top\"></td></tr>";
		}
		return $content . '</tbody></table>';
	}
	
	private function buildForums($threads) {
		$content = '<table  border="0" cellspacing="0" cellpadding="0"><tbody>';
		$ft = new ForumThread;
		foreach($threads as $thread) {
			$ft->setData($thread);
			$content .= sprintf(
				'<tr>
					<td>
						<a href="%s" target="_blank" style="font-weight: 600; text-decoration:none;color:%s">%s</a><br />
						<a href="%s" target="_blank" style="font-size: 12px; text-decoration:none;color:#df5858;">latest on %s</a><br />
						<span style="font-size: 12px;color: #777;">%d posts</span>
					</td>
				</tr><tr><td height="5" valign="top"></td></tr>',
				$ft->getURL() . $this->googleCampaign,
				$this->getLinkColour(),
				$this->sortWrappedChinese($ft->getTitle()),
				$ft->getURL(true, false, array('extraParams' => $this->googleCampaignArgs)),
				date('l, F j', strtotime($ft->getLatestPost('ts'))),
				$ft->getNumberPosts()
			);
		}
		return $content . '</tbody></table>';
	}
	
	private function getEvents($events, $showDate = false) {
		$content = '<table  border="0" cellspacing="0" cellpadding="0"><tbody>';
		$cal = new Calendar;
		foreach ($events as $event) {
			if ($image = $cal->getImage($event['calendar_id'], 120, 120)) {
				$img = sprintf('<img border="0" width="50" height="50" alt="Event poster" src="%s" />', $GLOBALS['model']->urls['root'] . $image);
			}
			$content .= sprintf('
				<tr>
					<td><a href="%s">%s</a></td>
					<td width="5">&nbsp;</td>
					<td>
						<a href="%s" target="_blank" style="color: #df5858; text-decoration:none">%s</a><br />
						<a href="%s" target="_blank" style="font-weight: 600; color: %s; text-decoration:none">%s</a><br />
						<span style="color: #777; font-size: 12px;">%s</span>
					</td>
				</tr>
				<tr><td height="5" valign="top"></td></tr>
			',
				$event['url'] . $this->googleCampaign,
				$img,
				$event['listing_url'] . $this->googleCampaign,
				$event['venue'],
				$event['url'] . $this->googleCampaign,
				$this->getLinkColour(),
				$event['title'],
				$event[$showDate ? 'date_formatted' : 'starting_time_formatted']
			);
		}
		$content .= '</tbody></table>';

		return $content;
	}

	private function build() {
		global $model;
		
		$template = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/includes/views/templates/email.html');
		$message = $this->message_text;

		// links
		preg_match_all("/#.+?#.+?#/", $message, $matches);

		foreach($matches[0] as $match) {
			$match_ip = explode('#', trim($match, '#'));
			$match_ip[1] = trim($match_ip[1]);
			$link = "<a style=\"text-decoration: none; color: #99D3FF;\" href=\"".trim($match_ip[1]) . $this->googleCampaign ."\" target=\"_blank\">".trim($match_ip[0])."</a>";
			$message = str_replace($match, $link, $message);
		}
		
		$message = str_replace('<a href', '<a style="text-decoration:none;color:'.$this->getLinkColour().';" href', $message);

		$content = str_replace('#MESSAGE#', nl2br($message), $template);
		
		$content = str_replace('#DATE#', date('l, F j, Y'), $content);
		
		$content = str_replace('#CAMPAIGN_CODE#', $this->googleCampaign, $content);

		// latest
		$bl = new BlogList;
		$popular = $bl->getItems(array('limit' => 2, 'orderBy' => 'num_comments', 'after' => 'DATE_SUB(NOW(), INTERVAL 2 WEEK)'));
		$ids = array_transpose($popular, 'blog_id');
		$latest = $bl->getItems(array('limit' => 10, 'after' => 'DATE_SUB(NOW(), INTERVAL 1 WEEK)', 'clauses' => array('!blog_content.blog_id NOT IN (' . implode(',', $ids) . ')')));
		
		$view = new View;
		$view->setPath('enews/main.html');
		$view->setTag('title', 'Top stories');
		$view->setTag('body', $this->buildBlogItems($popular));
		$content = str_replace('#TOP#', $view->getOutput(), $content);
		
		$view->setTag('title', 'Latest stories');
		$view->setTag('body', $this->buildBlogItems($latest));
		$content = str_replace('#LATEST#', $view->getOutput(), $content);
		
		$cal = new Calendar;
		$events = $cal->getUpcoming();
		
		$view->setTag('title', 'Upcoming events');
		$view->setTag('body', $this->getEvents($events, true));
		$content = str_replace('#UPCOMING#', $view->getOutput(), $content);
		
		$eventsContent = '';
		$date = time();
		for($i = 0; $i < 3; $i++) {
			$date2 = $date + (3600 * 24 * $i);
			$view->setTag('title', 'Events for ' . date('l, F j', $date2));
			$view->setTag('body', $this->getEvents($cal->getEvents($date2, true, true, false)));
			$eventsContent .= $view->getOutput();
		}
		$content = str_replace('#EVENTS#', $eventsContent, $content);
		
		$view = new View;
		$view->setPath('enews/side.html');
		
		$posts = $model->db()->query('classifieds_data', array('folder_id' => 2, 'status' => 1), array('limit' => 10, 'orderBy' => 'ts', 'order' => 'DESC'));
		$view->setTag('title', 'Latest job ads');
		$view->setTag('body', $this->buildClassifieds($posts));
		$classifieds = $view->getOutput();
		
		$posts = $model->db()->query('classifieds_data', array('folder_id' => 14, 'status' => 1), array('limit' => 5, 'orderBy' => 'ts', 'order' => 'DESC'));
		$view->setTag('title', 'Latest housing ads');
		$view->setTag('body', $this->buildClassifieds($posts));
		$classifieds .= $view->getOutput();
		
		$content = str_replace('#CLASSIFIEDS#', $classifieds, $content);
		
		$sql = "SELECT DISTINCT t.*, COUNT(p.post_id) AS posts FROM bb_threads t LEFT JOIN bb_posts p ON (t.thread_id = p.thread_id) WHERE t.live = 1 AND p.live = 1 AND p.ts > DATE_SUB(NOW(), INTERVAL 1 WEEK) GROUP BY t.thread_id ORDER BY posts DESC LIMIT 10";
		$threads = $model->db()->run_select($sql);
		
		$view->setTag('title', 'Top forums');
		$view->setTag('body', $this->buildForums($threads));
		$content = str_replace('#FORUM#', $view->getOutput(), $content);
		$sb = $view->getOutput();
		
		$this->message = $content;
	}

	public function save() {
		global $model;
		$this->build();
		
		$row = array(
			'subject' => $this->getEditedSubject(),
			'message' => $this->message,
			'text' => $this->message_text,
			'ts' => unixToDatetime()
		);
		
		if (ctype_digit($this->enews_id)) {
			return -1 < $model->db()->update('enews', array('enews_id' => $this->enews_id), $row);
		}
		else {
			return $model->db()->insert('enews', $row);
		}
	}
}
?>