<?php
define("LOG_USER_MESSAGE", 1); // 1 => Always show messages to users
define("LOG_USER_WARNING", 2); // 2 => Warnings meant for USERS (!!!) to see
define("LOG_USER_ERROR", 3); // 2 => errors meant for USERS (!!!) to see
define("LOG_SYSTEM_NOTIFY", 4); // 2 => Notifications for debugging purposes
define("LOG_SYSTEM_WARNING", 5); // 3 => Warnings for debugging purposes
define("LOG_SYSTEM_ERROR", 6); // 4 => Errors for debugging only!!!
define("LOG_SYSTEM_CRITICAL", 7); // 4 => Errors for debugging only!!!

class LogTools extends CMS_Class {
	public $history = array();
	var $priorityClasses = array(
		'UserMessage',
		'UserWarning',
		'UserError',
		'SystemNotification',
		'SystemWarning',
		'SystemError',
		'SystemCritical'
	);
	var $highestPriority;
	var $showLog;
	var $messages;

	public function init($args) {
		$loggerMode = $this->model->debug() ? 'debug' : 'default';
		if ($loggerMode == "hide") {
			$this->highestPriority = 0;
			$this->showLog = false;
		} elseif ($loggerMode == "debug") {
			$this->highestPriority = 7;
			$this->showLog = true;
		} else {
			$this->highestPriority = $this->pref('priority');
			if (!$this->highestPriority) $this->highestPriority = constant('LOG_USER_ERROR');
			$this->showLog = $this->pref('show') == 'true';
		}
	}

	public function add($priority, $message, $module = 'system') {
		$this->history[$priority][] = $message;
	}
	
	public function addL($priority, $langKey, $langModule, $module = 'system') {
		$message = $this->lang($langKey, $langModule);
		$this->history[$priority][] = $message;
	}

	function getUserLog() {
		return $this->sprintLog(constant('LOG_USER_MESSAGE'), constant('LOG_USER_ERROR'));
	}
	
	function sprintLog($low = false, $high = false) {
		ifNot($low, 0);
		ifNot($high, $this->highestPriority);
		// By default, show only messages
		$return = "";
		if (empty($this->history) || !$this->showLog) return "";
		$return .= sprintf("<div class=\"logListWrapper\">\n");
		for($i = $this->highestPriority; $i > 0; $i--) {
			if (isset($this->history[$i])) {
				$return .= sprintf("\t<dl class=\"logList\">\n");
				$return .= sprintf("\t\t<dt class=\"logListTitle log%s\">%s</dt>\n", $this->priorityClasses[$i - 1], $this->lang('TITLE_LEVEL_' . $i));
				$return .= sprintf("\t\t<dd class=\"logListItems items%s\"><ul>\n", $this->priorityClasses[$i - 1]);
				foreach ($this->history[$i] as $message) {
					$return .= sprintf("\t\t\t<li class=\"logListItem\">%s</li>\n", $message);
				}
				$return .= sprintf("\t\t</ul></dd>\n");
				$return .= sprintf("\t</dl>\n");
			}
		}
		$return .= sprintf("</div>\n");
		return $return;
	}
}

?>