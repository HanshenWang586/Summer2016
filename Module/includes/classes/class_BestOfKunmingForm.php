<?php
class BestOfKunmingForm extends PrimordialForm {
	
	public function displayForm() {
		global $user, $model;
		
		$db = $model->db();
		
		$award = $db->query('awards', array('!date_start < NOW() AND date_end >= NOW()'), array('singleResult' => true));
		
		if (!$award or !is_array($award)) return "<h1 class=\"dark\">No awards available</h1><p>No awards avaiable for voting.</p>";
		
		$content = sprintf("<h1 class=\"dark\">%s</h1>\n<div id=\"article\">", $award['name_en']);
		
		if ($user->getUserID()) {
			$categories = $db->query('awards_categories', array('awards_id' => $award['id']));
			$voted = $db->query('awards_votes', array('user_id' => $user->getUserID(), 'category_id' => array_transpose($categories, 'id')));
			
			$count = count($categories);
			
			if (!$voted) {
				$content .= $this->displayErrors('<p>Sorry, there seems to have been problems with your form:</p>');
				$content .= "<p style=\"margin-top: 10px; font-size: 1.2em;\">Welcome to <strong>{$award['name_en']}</strong> - the chance for you to show your appreciation to your favorite places in Kunming.</p>";
				
				if ($img = request($award['image'])) {
					$bi = new BlogImage($award['image']);
					$content .= $bi->getEmbeddable();
				}
				
				$content .= "<h2>This year we have $count categories in which you can vote:</h2><form action=\"$this->action\" method=\"post\" class=\"bokm_form\"><fieldset>";
				
				$listing = new ListingsItem();
				foreach ($categories as $cat) {
					$items = array();
					if ($img = request($cat['image'])) {
						$bi = new BlogImage($cat['image']);
						$content .= '<div style="text-align: center;">' . $bi->getEmbeddable() . '</div>';
					}
					$content .= "<h2 style=\"margin-bottom: 0.5em;\">{$cat['name_en']}</h2>";
					
					$nominees = $db->query('awards_nominees', array('category_id' => $cat['id']));
					foreach ($nominees as $nominee) {
						$listing->load($nominee['listings_id']);
						$item = sprintf("<label><input type=\"radio\" name=\"category[%d]\" value=\"%d\"><span class=\"labelCaption\">%s</span>%s</label>", $cat['id'], $nominee['id'], $listing->getPublicName(), HTMLHelper::link($listing->getURL(), 'See listings page <span class="icon icon-arrow-right-2 icon-right"> <span>', array('external' => true)));
						
						$items[] = $item;
					}

					$items[] = sprintf("<label><input type=\"radio\" name=\"category[%d]\" value=\"0\" checked><span class=\"labelCaption\">I don't want to vote in this category</span></label>", $cat['id']);
					$content .= HTMLHelper::wrapArrayInUl($items, '', 'bokmItems');
				}

				$content .= ContentCleaner::PWrap("
				Thank you for taking the time to vote. Once your votes have been submitted, you will be unable to vote again, or amend your votes, so please check you have made your selections carefully.
				
				Voting will continue until December 31, results will be announced shortly after.
				
				<input style=\"float: left; margin: 0;\" class=\"submit\" type=\"submit\" value=\"Submit your vote!\"></fieldset></form>");
			} else
				$content .= '<div id="article"><h1 style="margin-top: 10px;">Best of Kunming 2014 Awards</h1><p>Thank you for helping choose the Best of Kunming 2014!
				Winners will be announced in early January.</p></div>
			<h2 style="margin-bottom: 10px;">Share this with your friends:</h2>';
			$social = new Social;
			$content .= $social->getSharingList('GoKunming: Vote in the Best of Kunming 2014 Awards');
		}
		else
			$content .= sprintf("
				<div class=\"whiteBox\">
					<h2>Please login to cast your votes.</h2>
				</div>
				<p>
					<a class=\"icon-link\" href=\"/en/users/login/\"><span class=\"icon icon-login\"> </span>%s</a>
					<a class=\"icon-link\" href=\"/en/users/register/\"><span class=\"icon icon-user-add\"> </span>%s</a>
				</p>",
					$model->lang('MENU_LOGIN'),
					$model->lang('MENU_REGISTER')
				);
		
		$content .= "</div>";
		
		return $content;
	}

	function processForm() {
		global $user;
		
		$data = array();
		
		$time = unixToDatetime();
		
		foreach($this->data['category'] as $cat => $nominee) {
			if ($cat and $nominee) $data[] = array(
				'user_id' => (int) $user->getUserID(),
				'category_id' => (int) $cat,
				'nominee_id' => (int) $nominee,
				'time' => $time
			);
		}
		
		$GLOBALS['model']->db()->insert('awards_votes', $data);
	}
}
?>