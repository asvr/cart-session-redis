<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Session_MY_D {

	var $sess_encrypt_cookie	= FALSE;
	var $sess_use_database		= TRUE;
	var $sess_table_name		= 'ci_session';
	var $sess_expiration		= 7200;
	var $sess_expire_on_close	= FALSE;
	var $sess_match_ip		= FALSE;
	var $sess_match_useragent	= TRUE;
	var $sess_cookie_name		= 'ci_session';
	var $cookie_prefix		= '';
	var $cookie_path		= '';
	var $cookie_domain		= '';
	var $cookie_secure		= FALSE;
	var $sess_time_to_update	= 300;
	var $encryption_key		= '';
	var $flashdata_key		= 'flash';
	var $time_reference		= 'time';
	var $gc_probability		= 5;
	var $userdata			= array();
	var $CI;
	var $now;
	
	var $cookie_fields		= array('session_id','ip_address','user_agent','last_activity');
	
	var $redis;

	/**
	 * Session Constructor
	 *
	 * The constructor runs the session routines automatically
	 * whenever the class is instantiated.
	 */
	public function __construct($params = array())
	{
		//$this->load->library('redis_helper');
		
		log_message('debug', "Session Class Initialized");

		// Set the super object to a local variable for use throughout the class
		$this->CI =& get_instance();
		
		// Set all the session preferences, which can either be set
		// manually via the $params array above or via the config file
		foreach (array('sess_encrypt_cookie', 'sess_use_database', 'sess_table_name', 'sess_expiration', 'sess_expire_on_close', 'sess_match_ip', 'sess_match_useragent', 'sess_cookie_name', 'cookie_path', 'cookie_domain', 'cookie_secure', 'sess_time_to_update', 'time_reference', 'cookie_prefix', 'encryption_key') as $key)
		{
			$this->$key = (isset($params[$key])) ? $params[$key] : $this->CI->config->item($key);
		}

		if ($this->encryption_key == '')
		{
			show_error('In order to use the Session class you are required to set an encryption key in your config file.');
		}

		// Load the string helper so we can use the strip_slashes() function
		$this->CI->load->helper('string');

		// Do we need encryption? If so, load the encryption class
		if ($this->sess_encrypt_cookie == TRUE)
		{
			$this->CI->load->library('encrypt');
		}

		// Are we using a database?  If so, load it
		if ($this->sess_use_database === TRUE AND $this->sess_table_name != '')
		{
			echo $this->CI->redis;
			if ( empty($this->CI->redis) ) {
				$this->CI->redis = redis_autoconnect();
			}
		}

		// Set the "now" time.  Can either be GMT or server time, based on the
		// config prefs.  We use this to set the "last activity" time
		$this->now = $this->_get_time();
		echo " Time : ".$this->now = $this->_get_time()."\n";
		// Set the session length. If the session expiration is
		// set to zero we'll set the expiration two years from now.
		if ($this->sess_expiration == 0)
		{
			$this->sess_expiration = (60*60*24*365*2);
		}

		// Set the cookie name
		$this->sess_cookie_name = $this->cookie_prefix.$this->sess_cookie_name;
		echo "  Cookie Name : " .$this->sess_cookie_name."\n";
		// Run the Session routine. If a session doesn't exist we'll
		// create a new one.  If it does, we'll update it.
		if ( ! $this->sess_read())
		{
			echo "   Creating session \n";
			$this->sess_create();
		}
		else
		{
			echo "   updating session \n ";
			$this->sess_update();
		}

		// Delete 'old' flashdata (from last request)
		$this->_flashdata_sweep();

		// Mark all new flashdata as old (data will be deleted before next request)
		$this->_flashdata_mark();

		log_message('debug', "Session routines successfully run");
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch the current session data if it exists
	 *
	 * @access	public
	 * @return	bool
	 */
	function sess_read()
	{
		// Fetch the cookie
		$session = $this->CI->input->cookie($this->sess_cookie_name);
		
		echo "    read session : ".$session."\n";
		// No cookie?  Goodbye cruel world!...
		if ($session === FALSE)
		{
			log_message('debug', 'A session cookie was not found.');
			return FALSE;
		}

		// Decrypt the cookie data
		if ($this->sess_encrypt_cookie == TRUE)
		{
			$session = $this->CI->encrypt->decode($session);
		}
		else
		{
			// encryption was not used, so we need to check the md5 hash
			$hash	 = substr($session, strlen($session)-32); // get last 32 chars
			$session = substr($session, 0, strlen($session)-32);

			// Does the md5 hash match?  This is to prevent manipulation of session data in userspace
			if ($hash !==  md5($session.$this->encryption_key))
			{
				log_message('error', 'The session cookie data did not match what was expected. This could be a possible hacking attempt.');
				$this->sess_destroy();
				return FALSE;
			}
		}
		
		// Unserialize the session array
		$session = $this->_unserialize($session);
		
		
		// Is the session data we unserialized an array with the correct format?
		if ( 
			! is_array($session) 
			|| ! isset($session['session_id']) 
			|| ! isset($session['last_activity'])
			|| ($this->sess_match_ip==TRUE && !isset($session['ip_address'])) 
			|| ($this->sess_match_useragent==TRUE && !isset($session['user_agent'])) 
			)
		{
			$this->sess_destroy();
			return FALSE;
		}

		// Is the session current?
		if (($session['last_activity'] + $this->sess_expiration) < $this->now)
		{
			$this->sess_destroy();
			return FALSE;
		}

		// Does the IP Match?
		if ($this->sess_match_ip == TRUE AND $session['ip_address'] != $this->CI->input->ip_address())
		{
			$this->sess_destroy();
			log_message('error', 'The session cookie IP did not match. This could be a possible hacking attempt.');
			return FALSE;
		}

		// Does the User Agent Match?
		if ($this->sess_match_useragent == TRUE AND trim($session['user_agent']) != trim(substr($this->CI->input->user_agent(), 0, 120)))
		{
			$this->sess_destroy();
			log_message('error', 'The session cookie USERAGENT did not match. This could be a possible hacking attempt.');
			return FALSE;
		}

		// Is there a corresponding session in the DB?
		if ($this->sess_use_database === TRUE)
		{	
			$sess_key = $this->sess_table_name.':'.$session['session_id'];
			echo '   sess_key: '.$sess_key;
			$row = $this->CI->redis->hgetall($sess_key);
			var_dump($row);
			/*if (!$row) {
				log_message('error', 'No session found by key '.$sess_key.'.');
			}*/
			
			
			if ($row && $this->sess_match_ip == TRUE)
			{	
				if ( empty($row['ip_address']) || $session['ip_address'] != $row['ip_address'] ) {
					$row = FALSE;
					log_message('error', 'The session IP did not match cookie value. This could be a possible hacking attempt.');
				}
			}

			if ($row && $this->sess_match_useragent == TRUE)
			{	
				if (  empty($row['user_agent']) || $session['user_agent'] != $row['user_agent'] ) {
					$row = FALSE;
					log_message('error', 'The session USERAGENT did not match cookie value. This could be a possible hacking attempt.');
				}
			}

			// No result?  Kill it!
			if ( ! $row )
			{
				$this->sess_destroy();
				return FALSE;
			}

			// Is there custom data?  If so, add it to the main session array
			$row = (object)$row;
			if (isset($row->user_data) AND $row->user_data != '')
			{
				$custom_data = $this->_unserialize($row->user_data);

				if (is_array($custom_data))
				{
					foreach ($custom_data as $key => $val)
					{
						$session[$key] = $val;
					}
				}
			}
		}

		// Session is valid!
		$this->userdata = $session;
		unset($session);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Write the session data
	 *
	 * @access	public
	 * @return	void
	 */
	function sess_write()
	{
		// Are we saving custom data to the DB?  If not, all we do is update the cookie
		if ($this->sess_use_database === FALSE)
		{
			$this->_set_cookie();
			return;
		}

		// set the custom userdata, the session data we will set in a second
		$custom_userdata = $this->userdata;
		$cookie_userdata = array();

		// Before continuing, we need to determine if there is any custom data to deal with.
		// Let's determine this by removing the default indexes to see if there's anything left in the array
		// and set the session data while we're at it
		$cookie_userdata = $this->_cookieData();
		foreach ($this->cookie_fields as $val)
		{
			unset($custom_userdata[$val]);
		}


		// Did we find any custom data?  If not, we turn the empty array into a string
		// since there's no reason to serialize and store an empty array in the DB
		if (count($custom_userdata) === 0)
		{
			$custom_userdata = '';
		}
		else
		{
			// Serialize the custom data array so we can store it
			$custom_userdata = $this->_serialize($custom_userdata);
		}

		// Run the update query
		$sess_key = $this->sess_table_name.':'.$this->userdata['session_id'];
		$write = $this->CI->redis->multiExec()
			->hset($sess_key, 'last_activity', $this->userdata['last_activity'])
			->hset($sess_key, 'user_data', $custom_userdata)
			->expire($sess_key, $this->sess_expiration)
			->execute();
			
		if ( ! $write ) {
			log_message('error', 'Failed to write session '.$sess_key);
		}
		
		// Write the cookie.  Notice that we manually pass the cookie data array to the
		// _set_cookie() function. Normally that function will store $this->userdata, but
		// in this case that array contains custom data, which we do not want in the cookie.
		$this->_set_cookie($cookie_userdata);
	}

	// --------------------------------------------------------------------

	/**
	 * Create a new session
	 *
	 * @access	public
	 * @return	void
	 */
	function sess_create()
	{
		$sessid = '';
		while (strlen($sessid) < 32)
		{
			$sessid .= mt_rand(0, mt_getrandmax());
		}

		// To make the session ID even more secure we'll combine it with the user's IP
		$sessid .= $this->CI->input->ip_address();

		$this->userdata = array(
							'session_id'	=> md5(uniqid($sessid, TRUE)),
							'ip_address'	=> $this->CI->input->ip_address(),
							'user_agent'	=> substr($this->CI->input->user_agent(), 0, 120),
							'last_activity'	=> $this->now,
							'user_data'		=> ''
							);


		// Save the data to the DB if needed
		if ($this->sess_use_database === TRUE)
		{
			$sess_key = $this->sess_table_name.':'.$this->userdata['session_id'];
			
			$this->CI->redis->multiExec()
				->hset($sess_key, 'session_id', $this->userdata['session_id'])
				->hset($sess_key, 'ip_address', $this->userdata['ip_address'])
				->hset($sess_key, 'user_agent', $this->userdata['user_agent'])
				->hset($sess_key, 'last_activity', $this->userdata['last_activity'])
				->hset($sess_key, 'user_data', $this->userdata['user_data'])
				->expire($sess_key, $this->sess_expiration)
				->execute();
		}
		
		$cookie_userdata = $this->_cookieData();		

		// Write the cookie
		$this->_set_cookie($cookie_userdata);
	}

	// --------------------------------------------------------------------

	/**
	 * Update an existing session
	 *
	 * @access	public
	 * @return	void
	 */
	function sess_update()
	{
		// We only update the session every five minutes by default
		if (($this->userdata['last_activity'] + $this->sess_time_to_update) >= $this->now)
		{
			return;
		}

		// Save the old session id so we know which record to
		// update in the database if we need it
		$old_sessid = $this->userdata['session_id'];
		$new_sessid = '';
		while (strlen($new_sessid) < 32)
		{
			$new_sessid .= mt_rand(0, mt_getrandmax());
		}

		// To make the session ID even more secure we'll combine it with the user's IP
		$new_sessid .= $this->CI->input->ip_address();

		// Turn it into a hash
		$new_sessid = md5(uniqid($new_sessid, TRUE));

		// Update the session data in the session data array
		$this->userdata['session_id'] = $new_sessid;
		$this->userdata['last_activity'] = $this->now;

		// _set_cookie() will handle this for us if we aren't using database sessions
		// by pushing all userdata to the cookie.
		$cookie_data = NULL;

		// Update the session ID and last_activity field in the DB if needed
		if ($this->sess_use_database === TRUE)
		{
			echo ' \n update session databse  \n ';
			// set cookie explicitly to only have our session data
			$cookie_userdata = $this->_cookieData();
			
			$old_sess_key = $this->sess_table_name.':'.$old_sessid;
			$new_sess_key = $this->sess_table_name.':'.$new_sessid;
			
			$this->CI->redis->multiExec()
				->hset($old_sess_key, 'last_activity', $this->now)
				->hset($old_sess_key, 'session_id', $new_sessid)
				->expire($old_sess_key, $this->sess_expiration)
				->rename($old_sess_key, $new_sess_key)
				->execute();
			

		}
		echo "   cookie data :   ".$cookie_data."\n";
		// Write the cookie
		$this->_set_cookie($cookie_data);
	}

	// --------------------------------------------------------------------

	/**
	 * Destroy the current session
	 *
	 * @access	public
	 * @return	void
	 */
	function sess_destroy()
	{
		// Kill the session DB row
		if ($this->sess_use_database === TRUE && isset($this->userdata['session_id']))
		{	
			$sess_key = $this->sess_table_name.':'.$this->userdata['session_id'];
			$this->CI->redis->delete($sess_key);
		}

		// Kill the cookie
		setcookie(
					$this->sess_cookie_name,
					addslashes(serialize(array())),
					($this->now - 31500000),
					$this->cookie_path,
					$this->cookie_domain,
					0
				);

		// Kill session data
		$this->userdata = array();
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a specific item from the session array
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function userdata($item)
	{
		return ( ! isset($this->userdata[$item])) ? FALSE : $this->userdata[$item];
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch all session data
	 *
	 * @access	public
	 * @return	array
	 */
	function all_userdata()
	{
		return $this->userdata;
	}

	// --------------------------------------------------------------------

	/**
	 * Add or change data in the "userdata" array
	 *
	 * @access	public
	 * @param	mixed
	 * @param	string
	 * @return	void
	 */
	function set_userdata($newdata = array(), $newval = '')
	{
		if (is_string($newdata))
		{
			$newdata = array($newdata => $newval);
		}

		if (count($newdata) > 0)
		{
			foreach ($newdata as $key => $val)
			{
				$this->userdata[$key] = $val;
			}
		}

		$this->sess_write();
	}

	// --------------------------------------------------------------------

	/**
	 * Delete a session variable from the "userdata" array
	 *
	 * @access	array
	 * @return	void
	 */
	function unset_userdata($newdata = array())
	{
		if (is_string($newdata))
		{
			$newdata = array($newdata => '');
		}

		if (count($newdata) > 0)
		{
			foreach ($newdata as $key => $val)
			{
				unset($this->userdata[$key]);
			}
		}

		$this->sess_write();
	}

	// ------------------------------------------------------------------------

	/**
	 * Add or change flashdata, only available
	 * until the next request
	 *
	 * @access	public
	 * @param	mixed
	 * @param	string
	 * @return	void
	 */
	function set_flashdata($newdata = array(), $newval = '')
	{
		if (is_string($newdata))
		{
			$newdata = array($newdata => $newval);
		}

		if (count($newdata) > 0)
		{
			foreach ($newdata as $key => $val)
			{
				$flashdata_key = $this->flashdata_key.':new:'.$key;
				$this->set_userdata($flashdata_key, $val);
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Keeps existing flashdata available to next request.
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function keep_flashdata($key)
	{
		// 'old' flashdata gets removed.  Here we mark all
		// flashdata as 'new' to preserve it from _flashdata_sweep()
		// Note the function will return FALSE if the $key
		// provided cannot be found
		$old_flashdata_key = $this->flashdata_key.':old:'.$key;
		$value = $this->userdata($old_flashdata_key);

		$new_flashdata_key = $this->flashdata_key.':new:'.$key;
		$this->set_userdata($new_flashdata_key, $value);
	}

	// ------------------------------------------------------------------------

	/**
	 * Fetch a specific flashdata item from the session array
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function flashdata($key)
	{
		$flashdata_key = $this->flashdata_key.':old:'.$key;
		return $this->userdata($flashdata_key);
	}

	// ------------------------------------------------------------------------

	/**
	 * Identifies flashdata as 'old' for removal
	 * when _flashdata_sweep() runs.
	 *
	 * @access	private
	 * @return	void
	 */
	function _flashdata_mark()
	{
		$userdata = $this->all_userdata();
		foreach ($userdata as $name => $value)
		{
			$parts = explode(':new:', $name);
			if (is_array($parts) && count($parts) === 2)
			{
				$new_name = $this->flashdata_key.':old:'.$parts[1];
				$this->set_userdata($new_name, $value);
				$this->unset_userdata($name);
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Removes all flashdata marked as 'old'
	 *
	 * @access	private
	 * @return	void
	 */

	function _flashdata_sweep()
	{
		$userdata = $this->all_userdata();
		foreach ($userdata as $key => $value)
		{
			if (strpos($key, ':old:'))
			{
				$this->unset_userdata($key);
			}
		}

	}

	// --------------------------------------------------------------------

	/**
	 * Get the "now" time
	 *
	 * @access	private
	 * @return	string
	 */
	function _get_time()
	{
		if (strtolower($this->time_reference) == 'gmt')
		{
			$now = time();
			$time = mktime(gmdate("H", $now), gmdate("i", $now), gmdate("s", $now), gmdate("m", $now), gmdate("d", $now), gmdate("Y", $now));
		}
		else
		{
			$time = time();
		}

		return $time;
	}

	// --------------------------------------------------------------------

	/**
	 * Write the session cookie
	 *
	 * @access	public
	 * @return	void
	 */
	function _set_cookie($cookie_data = NULL)
	{
		if (is_null($cookie_data))
		{
			$cookie_data = $this->userdata;
		}
		
		$filtered = array();
		foreach ( $this->cookie_fields as $f ) {
		    if (isset($cookie_data[$f])) {
			$filtered[$f] = $cookie_data[$f];
		    }
		}
		$cookie_data = $filtered;

		// Serialize the userdata for the cookie
		$cookie_data = $this->_serialize($cookie_data);

		if ($this->sess_encrypt_cookie == TRUE)
		{
			$cookie_data = $this->CI->encrypt->encode($cookie_data);
		}
		else
		{
			// if encryption is not used, we provide an md5 hash to prevent userside tampering
			$cookie_data = $cookie_data.md5($cookie_data.$this->encryption_key);
		}

		$expire = ($this->sess_expire_on_close === TRUE) ? 0 : $this->sess_expiration + time();

		// Set the cookie
		setcookie(
					$this->sess_cookie_name,
					$cookie_data,
					$expire,
					$this->cookie_path,
					$this->cookie_domain,
					$this->cookie_secure
				);
	}

	// --------------------------------------------------------------------

	/**
	 * Serialize an array
	 *
	 * This function first converts any slashes found in the array to a temporary
	 * marker, so when it gets unserialized the slashes will be preserved
	 *
	 * @access	private
	 * @param	array
	 * @return	string
	 */
	function _serialize($data)
	{
		if (is_array($data))
		{
			foreach ($data as $key => $val)
			{
				if (is_string($val))
				{
					$data[$key] = str_replace('\\', '{{slash}}', $val);
				}
			}
		}
		else
		{
			if (is_string($data))
			{
				$data = str_replace('\\', '{{slash}}', $data);
			}
		}

		return serialize($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Unserialize
	 *
	 * This function unserializes a data string, then converts any
	 * temporary slash markers back to actual slashes
	 *
	 * @access	private
	 * @param	array
	 * @return	string
	 */
	function _unserialize($data)
	{
		$data = @unserialize(strip_slashes($data));

		if (is_array($data))
		{
			foreach ($data as $key => $val)
			{
				if (is_string($val))
				{
					$data[$key] = str_replace('{{slash}}', '\\', $val);
				}
			}

			return $data;
		}

		return (is_string($data)) ? str_replace('{{slash}}', '\\', $data) : $data;
	}
	
	
	function _cookieData($data = NULL)
	{
		if (is_null($data))
		{
			$data = $this->userdata;
		}
		
		$cookie_data = array();
			
		$cookie_fields = $this->cookie_fields;
	
		if ($this->sess_match_ip != TRUE) { unset($cookie_fields['ip_address']); }
		if ($this->sess_match_useragent != TRUE) { unset($cookie_fields['user_agent']); }
		
		foreach ($cookie_fields as $val)
		{
			$cookie_data[$val] = $data[$val];
		}
		
		return $cookie_data;
		
	}

function redis_autoconnect($db = 0) {
	
		$CI =& get_instance();
		$CI->config->load('redis');
		$cnf = $CI->config->item('redis');
		if ( ! $cnf ) {
			$cnf = array();
		}
		
		$library = 'phpredis';
		
		$socket = FALSE;
		$pconnect = FALSE;
		
		//$user = '';
		$password = FALSE;
		$port = 6379;
		$host = '127.0.0.1';
		
		if ( !empty($cnf['library']) ) {
			 $library = $cnf['library']; 
		};
		
		if ( !empty($cnf['use_socket']) && !empty($cnf['socket'])  )  { 
			$socket = $cnf['socket']; 
		};
		
		if ( !empty($cnf['pconnect']) ) {
			 $pconnect = TRUE; 
		};
		
		if ( !empty($cnf['use_password']) && !empty($cnf['password']) ) {
			 $password = $cnf['password']; 
		};
		
		/*if ( !empty($cnf['username']) ) {
			 $user = $cnf['username']; 
		};*/
		
		if ( !empty($cnf['hostname']) ) {
			 $host = $cnf['hostname']; 
		};
		
		if ( !empty($cnf['port']) ) {
			 $port = $cnf['port']; 
		};
		
		$connected = FALSE;
		
		switch ($library) {
			case 'phpredis':
				//$redis = new Redis();
				$connected = 
					$socket 
						? ( $pconnect ? $this->redis->pconnect($socket) :$this->redis->connect($socket) ) 
						: ( $pconnect ? $this->redis->pconnect($host, $port) : $this->redis->connect($host, $port) );
				$redis->select($db);
			break;
			case 'predis':
				// predis doesn't retrun connection status
				$connected = TRUE;
				$params = array(
				    'scheme'   => $socket?'unix':'tcp',
				    'host'     => $host,
				    'port'     => $port,
				    'path'     => $socket,
				    'database' => $db,
				    'connection_persistent' => $pconnect
				);
				if ( $password ) {
					$params['password'] = $password;
				};
				$redis = new Predis\Client($params);
			break;
			default:
				show_error('Unknown redis library');
		}
		
		
		if ( ! $connected || ! is_object($redis) ) {
			show_error('Failed to connect to Redis Server');
			exit();
		}
		
		if ( $password ) {
			$redis->auth($password);
		};
		
		return $redis;
	}

}
// END Session Class

/* End of file Session.php */
/* Location: ./application/libraries/Session.php */