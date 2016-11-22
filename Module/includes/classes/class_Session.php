<?php

class Session implements SessionHandlerInterface {
	private $table;
	
    /**
     *  Constructor of class
     *
     *  @return void
     */
    public function __construct($model, $name = 'my_session', $table = 'session_data') {
		$this->model = $model;
		$this->table = $table;
		
        // get session lifetime
        $this->sessionLifetime = ini_get("session.gc_maxlifetime");
		
		session_name($name);
		
        // register the new handler
        session_set_save_handler($this, true);
        register_shutdown_function('session_write_close');
		
        // start the session
        session_start();
    }
    
    /**
     *  Regenerates the session id.
     *
     *  <b>Call this method whenever you do a privilege change!</b>
     *
     *  @return void
     */
    public function regenerate_id() {
        // saves the old session's id
        $oldSessionID = session_id();
        
        // regenerates the id
        // this function will create a new session, with a new id and containing the data from the old session
        // but will not delete the old session
        session_regenerate_id();
        
        // because the session_regenerate_id() function does not delete the old session,
        // we have to delete it manually
        $this->destroy($oldSessionID);
    }
    
    /**
     *  Get the number of online users
     *
     *  This is not 100% accurate. It depends on how often the garbage collector is run
     *
     *  @return integer     approximate number of users curently online
     */
	public function get_users_online() {
        // counts the rows from the database
		//$count = $db->count($this->table, "!expire > '" . (time() - $maxlifetime) . "'");
    }
    
    /**
     *  Custom open() function
     *
     *  @access private
     */
    public function open($save_path, $session_name) {
        return true;
    }
    
    /**
     *  Custom close() function
     *
     *  @access private
     */
    public function close() {
        return true;
    }
    
    /**
     *  Custom read() function
     *
     *  @access private
     */
    public function read($session_id) {
        // reads session data associated with the session id
        // but only if the HTTP_USER_AGENT is the same as the one who had previously written to this session
        // and if session has not expired
        
		$result = $this->model->db()->query($this->table, array('session_id' => $session_id, 'http_user_agent' => $_SERVER["HTTP_USER_AGENT"], '!expire > NOW()'), array('selectField' => 'data'));
		return $result ? $result : '';
    }
    
    /**
     *  Custom write() function
     *
     *  @access private
     */
    public function write($session_id, $session_data) {
		global $user;
		$db = $this->model->db();
		// first checks if there is a session with this id
		$count = $db->count($this->table, array('session_id' => $session_id));
		$data = array(
			'data' => $session_data,
			'expire' => unixToDatetime(time() + $this->sessionLifetime),
			'updated' => unixToDatetime(time()),
			'request_uri' => $_SERVER['REQUEST_URI'],
			'http_user_agent' => $_SERVER["HTTP_USER_AGENT"],
			'ip' => $_SERVER['REMOTE_ADDR']
		);
		if ($user && $user->isLoggedIn()) $data['user_id'] = $user->getUserID();
		if ($count) {
			$result = $db->update($this->table, array('session_id' => $session_id), $data);
			if ($result) return true;
		} else {
			$data['session_id'] = $session_id;
			
			$result = $db->insert($this->table, $data);
			if ($result) return '';
		}
		return false;       
    }
    
    /**
     *  Custom destroy() function
     *
     *  @access private
     */
	public function destroy($session_id) {
		$result = $this->model->db()->delete($this->table, array('session_id' => $session_id));
		return $result ? true : false;
    }
    
    /**
     *  Custom gc() function (garbage collector)
     *
     *  @access private
     */
	public function gc($maxlifetime) {
    	$this->model->db()->delete($this->table, "!expire < NOW()");
    }
    
}
?>
