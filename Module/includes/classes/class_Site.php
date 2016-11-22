<?php
class Site {
	
	private $site_id;
	
	/**
	 * @var string
	 */
	private $site_name;
	
	/**
	 * @var string
	 */
	private $site_desc;
	
	/**
	 * @var string
	 */
	private $site_url;
	
	/**
	 * Hacky, but saves time creating users. We're storing all retreived users here
	 * $var array
	 */
	private $users = array();
	
	/**
	 * Stores the database tools object DbTools
	 * $var DbTools
	 */
	public $db;
	
	public $metaTags = array();
	
	public $session;
	
	// browser info
	private $browserInfo;
	
	private $isBot;
	
	public function __construct() {
		$_SESSION['user'] = !isset($_SESSION['user']) ? new User : $_SESSION['user'];
		$_SESSION['user']->setIP($_SERVER['REMOTE_ADDR']);
		$_SESSION['user']->setSessionId(session_id());
		$GLOBALS['user'] = $user = $_SESSION['user'];
	}
	
	public function db() {
		if (!$this->db) {
			$this->db = new Db(array(
				'server' => constant('DB_HOST2'),
				'user' => constant('DB_USER'),
				'password' => constant('DB_PASS'),
				'name' => constant('DB_DB')
			));
		}
		return $this->db;
	}
	
	/**
	 * add a meta tag to the stack
	 *
	 * @param string $name
	 * @param string $content
	 */
	public function addMeta($name, $content, $type = 'name') {
		$this->metaTags[$name] = array($type, $content);
	}
	
	public function getUser($user_id) {
		if (!$user_id || !filter_var($user_id, FILTER_VALIDATE_INT)) return;
		if ($GLOBALS['user'] and $user_id == $GLOBALS['user']->getUserID()) return $GLOBALS['user'];
		if (!array_key_exists($user_id, $this->users)) $this->users[$user_id] = new User($user_id);
		return $this->users[$user_id];
	}
	
	public function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value)
				$this->$key = $value;
		}
	}

	/**
	 * @return string Key for use in pulling weather XML from Yahoo!
	 */
	public function getYahooWeatherKey() {
		return $this->getHomeCity()->getWeatherCode();
	}

	/**
	 * @return string Site URL direct from database - with http:// and trailing slash
	 */
	public function getURL() {
		return $this->site_url;
	}
	
	/**
	 * @return array city_ids that a) are featured in this site b) have items
	 */
	public function getCityIDs() {
		return $this->db()->query('listings_sitecitycat', false, array('transpose' => 'city_id', 'getFields' => 'DISTINCT city_id'));
	}
	
	/**
	 * @return integer site_id from database
	 */
	public function getSiteID() {
		return 1;
	}

	/**
	 * @return array city_ids for this site as recorded in ccl_cities2site
	 */
	private function getFeaturedCityIDs() {
		$city_ids = array();
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT city_id
							FROM ccl_cities2site');

		while ($row = $rs->getRow())
			$city_ids[] = $row['city_id'];

		return $city_ids;
	}
	/**
	 * @return integer city_id of the 'home' city for that site e.g. GoKunming => Kunming => 1
	 */
	public function getHomeCityID() {
		return 1;
	}
	
	/**
	 * @return object City object representing the home city of the site
	 */
	public function getHomeCity() {
		return new City($this->getHomeCityID());
	}
}
?>
