<?php

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
		$host = 'localhost';
		
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
				$redis = new Redis();
				$connected = 
					$socket 
						? ( $pconnect ? $redis->pconnect($socket) : $redis->connect($socket) ) 
						: ( $pconnect ? $redis->pconnect($host, $port) : $redis->connect($host, $port) );
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