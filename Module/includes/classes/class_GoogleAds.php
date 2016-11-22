<?php
class GoogleAds {
	public function display() {
		global $site;


		$content[1] = '

		<script type="text/javascript"><!--
		google_ad_client = "pub-0159497169509360";
		/* GoK5_160_600 */
		google_ad_slot = "4783872921";
		google_ad_width = 160;
		google_ad_height = 600;
		//-->
		</script>
		<script type="text/javascript"
		src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
		</script>

		';


		$content[2]  = '

		<script type="text/javascript"><!--
		google_ad_client = "pub-0159497169509360";
		/* GoC5_160_600 */
		google_ad_slot = "3417597626";
		google_ad_width = 160;
		google_ad_height = 600;
		//-->
		</script>
		<script type="text/javascript"
		src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
		</script>

		';

		return '<div id="google_ads">'.$content[$site->getSiteID()].'</div>';
	}
}
?>