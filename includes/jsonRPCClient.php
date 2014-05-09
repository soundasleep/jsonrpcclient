<?php
/*
					COPYRIGHT

Copyright 2007 Sergio Vaccaro <sergio@inservibile.org>

This file is part of JSON-RPC PHP.

JSON-RPC PHP is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

JSON-RPC PHP is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with JSON-RPC PHP; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * The object of this class are generic jsonRPC 1.0 clients
 * http://json-rpc.org/wiki/specification
 *
 * @author sergio <jsonrpcphp@inservibile.org>
 */
class jsonRPCClient {

	const DEBUG_REQUEST = 1;
	const DEBUG_RESPONSE = 2;

	/**
	 * Debug state
	 *
	 * @var boolean
	 */
	private $debug = 0;
	
	/**
	 * The server URL
	 *
	 * @var string
	 */
	private $url = null;
	/**
	 * The request id
	 *
	 * @var integer
	 */
	private $id = 0;
	/**
	 * If true, notifications are performed instead of requests
	 *
	 * @var boolean
	 */
	private $notification = false;
	/**
	 *
	 * @var string
	 */
	private $proxy = '';
	
	/**
	 * Takes the connection parameters
	 *
	 * @param string $url
	 */
	public function __construct($url) {
		// server URL
		$this->url = $url;
	}

	/**
	 * debug state
	 * @param int $debug
	 * DEBUG_REQUEST = 1;
	 * DEBUG_RESPONSE = 2;
	 */
	public function setDebug($debug) {
		$this->debug = (int) $debug;
	}

	/**
	 * debug state
	 * @param string $proxy
	 */
	public function setProxy($proxy) {
		$this->proxy = $proxy;
	}

	/**
	 * Sets the notification state of the object. In this state, notifications are performed, instead of requests.
	 *
	 * @param boolean $notification
	 */
	public function setRPCNotification($notification) {
		$this->notification = (bool) $notification;
	}

	/**
	 * 
	 * @param int $type
	 *  DEBUG_REQUEST = 1;
	 *  DEBUG_RESPONSE = 2;
	 * @param string $message
	 */
	private function debugLog($type, $message) {
		if ($this->debug & $type) {
			echo $message . PHP_EOL . PHP_EOL;
		}
	}

	/**
	 * Performs a jsonRCP request and gets the results as an array
	 *
	 * @param string $method
	 * @param array $params
	 * @return array
	 */
	public function __call($method, $params) {
		
		++$this->id;
		
		// check
		if (!is_scalar($method)) {
			throw new Exception('Method name has no scalar value');
		}
		
		// check
		if (is_array($params)) {
			// no keys
			$params = array_values($params);
		} else {
			throw new Exception('Params must be given as array');
		}
		
		// sets notification or request task
		if ($this->notification) {
			$currentId = NULL;
		} else {
			$currentId = $this->id;
		}
		
		// prepares the request
		$request = json_encode(array(
						'method' => $method,
						'params' => $params,
						'id' => $currentId
						));
		$this->debugLog(self::DEBUG_REQUEST, '***** Request *****' . "\n" . $request . "\n" . '***** End Of request *****');

		// performs the HTTP POST
		$opts = array ('http' => array (
							'method'  => 'POST',
							'header'  => 'Content-type: application/json',
							'content' => $request
							));

		$context  = stream_context_create($opts);
		$fp = fopen($this->url, 'r', false, $context);
		if (!$fp) {
			throw new Exception('Unable to connect to ' . $this->url);
		}

		$response = '';
		while (!feof($fp)) {
			$response .= trim(fgets($fp)) . "\n";
		}
		fclose($fp);

		$this->debugLog(self::DEBUG_RESPONSE, '***** Server response *****' . "\n" . $response . '***** End of server response *****');
		$response = json_decode($response, true);

		// final checks and return
		if (!$this->notification) {
			// check
			if ($response['id'] != $currentId) {
				throw new Exception('Incorrect response id (request id: ' . $currentId . ', response id: ' . $response['id'] . ')');
			}
			if (!is_null($response['error'])) {
				throw new Exception('Request error: ' . $response['error']);
			}
			
			return $response['result'];
		}

		return true;
	}

}
