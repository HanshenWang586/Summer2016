<?php

/**
 * @author yereth
 *
 * This class contains the login pages, which can be selected as a sitetool from ewyse.
 *
 * Most of the actual logging in is handled by the tool Security, found in the tools folder.
 *
 * TODO: Abstract some of the contact handling to a new Contact Tool class.
 *
 */
class AdsModel extends CMS_Model {
	private $locations;
	
	public function init($args) {
		$this->locations = $this->db()->query('ads_locations', false, array('transpose' => array('tag', true)));
	}
	
	public function get($position) {
		if ($pos = request($this->locations[$position]) and $id = $pos['location_id']) {
			$ads = $this->getAds(array('location_id' => $id), array('orderBy' => 'RAND()'));
			
			// If no ads are found, return empty string
			if (!$ads) return '';
			
			// Check if shown ads are already logged in the session
			$shownAds = request($_SESSION['shownAds'][$id]);
			if (!is_array($shownAds)) $shownAds = array();
			
			// Remove shown ads. If the list is empty, reset and restart
			if (!$ads2 = array_remove_keys($ads, $shownAds)) {
				$ads2 = $ads;
				$shownAds = array();
			}
			
			// Get ad
			$ad = array_shift($ads2);
			// Add to shown ads
			$shownAds[] = $ad['deployment_id'];
			$_SESSION['shownAds'][$id] = $shownAds;
			
			if ($url = request($ad['website']));
			elseif ($ad['listing_id']) {
				$listing = new ListingsItem($ad['listing_id']);
				$url = $listing->getURL();
			} else $url = $ad['ad_text'];
			//$url = array_get_set($ad, array('website', 'listing_url', 'ad_text'));
			
			//$url = request($ad['ad_text']);
			
			switch($ad['type']) {
				case 'jpg':
				case 'png':
				case 'gif':
					$html = sprintf('<img width="%d" height="%d" src="/images/pro/%d.%s" alt="%s">',
						$ad['width'],
						$ad['height'],
						$ad['media_id'],
						$ad['type'],
						$ad['advertiser']
					);
					break;
				case 'swf':
					$html = sprintf(
						'<object type="application/x-shockwave-flash" data="/images/pro/%d.swf" width="%d" height="%d">
							<param name="wmode" value="transparent">
							<!--[if IE]><param name="movie" value="/images/pro/%d.swf"><![endif]--> 
						</object>', $ad['media_id'], $ad['width'], $ad['height'], $ad['media_id']);
				break;
				case 'text':
					$html = $ad['ad_text'];
					$url = false;
				break;
			}
			 
			if ($url) $html = sprintf('<a class="mainLink img" href="%s">%s</a>', $url, $html);
			
			/*
			$lu = request($ad['listing_url']);
			$site = request($ad['website']);
			if (request($listing) or ($lu and $site)) {
				$html .= '<span class="infoBox">';
				if (request($listing)) $html .= sprintf('<span class="listingName">%s</span>', $listing->getName());
				if ($lu and $site) {
					$html .= '<span class="extraLinks">';
					$html .= sprintf('<a href="%s" class="listingLink"><span class="icon icon-star"> </span><span class="caption">%s</span></a>', $lu, $this->lang('LISTING_LINK'));
					$html .= sprintf('<a href="%s" class="siteLink"><span class="icon icon-logout"> </span><span class="caption">%s</span></a>', $site, $this->lang('EXTERNAL_SITE_LINK'));
					$html .= '</span>';
				}
				$html .= '</span>';
			}
			*/
			return sprintf('<div class="promWrapper %s">%s</div>', '', $html);
			
			return $html;
		} else {
			$this->logL('LOG_SYSTEM_WARNING', 'E_AD_LOCATION_NOT_FOUND');
			return false;
		}
	}
	
	public function getContent($options = array(), $bla = false) {
		global $user;
		
		if (!$user->getPower()) HTTP::disallowed(); 
		
		$args = array();
		if ($ad_id = $this->arg('advertiser_id')) $args['advertiser_id'] = $ad_id;
		$ads = $this->getAds($args);
		
		ob_start();
	?>
		<h1 class="dark">Ad management</h1>
		<table><tr>
			<th>ID</th>
			<th>Current Advertiser</th>
			<th>Location</th>
			<th>Start date</th>
			<th>End date</th>
			<th>Fee</th>
			<th></th>
		</tr>
	<?
		foreach($ads as $ad) {
	?>
		<tr>
			<td><?=$ad['deployment_id']?></td>
			<td><a href="<?=$this->url(array('advertiser_id' => $ad['advertiser_id']), false, true)?>"><?=$ad['advertiser']?></a></td>
			<td><?=$ad['description']?></td>
			<td><?=$ad['start_date']?></td>
			<td><?=$ad['end_date']?></td>
			<td><?=$ad['fee']?></td>
			<td><a href="<?=$this->url(array('m' => 'ads', 'view' => 'deployAd', 'deployment_id' => $ad['deployment_id']))?>">Edit</a></td>
		</tr>
	<? } ?>
	</table>
	<?
		return ob_get_clean();
	}
	
	public function getAds($where = array(), $options = array()) {
		$db = $this->db();
		
		if ($options['showExpired']) $where[] = '!TO_DAYS(end_date) < TO_DAYS(NOW())';
		else $where[] = '!TO_DAYS(end_date) >= TO_DAYS(NOW())';
		
		$order = request($options['orderBy']) ? $options['orderBy'] : 'end_date ASC, start_date DESC, a.advertiser_id';
		
		$ads = $db->query(
			'ads_deployments',
			$where,
			array(
				'orderBy' => $order,
				'callback' => array($this, 'processAd'),
				'join' => array(
					array(
						'table' => 'ads_media',
						'alias' => 'm',
						'fields' => 'm.ad_text, m.width, m.height, m.type, m.website AS media_url, m.listing_id AS media_listing_id',
						'on' => array('media_id', 'media_id')
					),
					array(
						'table' => 'ads_advertisers',
						'on' => array('m.advertiser_id', 'advertiser_id'),
						'alias' => 'a',
						'fields' => 'a.advertiser_id, a.advertiser, a.url AS advertiser_url, a.listing_id AS advertiser_listing_id'
					),
					array(
						'table' => 'ads_locations',
						'alias' => 'l',
						'fields' => 'description AS location',
						'on' => array('location_id', 'location_id')
					),
				), 'transpose' => array('selectKey' => 'deployment_id', 'selectValue' => true)
			)
		);
		//echo $db->getQuery();
		return $ads;
	}
	
	public function processAd($ad) {
		return $ad;
	}
}

?>