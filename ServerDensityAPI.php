<?php


/**
 *
 *	Implementation Class for the Server Density API
 *
 *	Server Density is a monitoring suite for your servers. This class allows you to
 *	interact seamlessly with the {@link http://developer.serverdensity.com API} so that you can build your own interface, 
 *	perform postbacks etc. This class has the advantage of client side verification of the call.
 *
 *	Requires PHP 5 and cURL
 *
 *	<code>
 *	<?php
 *
 *		$api = new ServerDensityAPI;
 *		$api->setCall("devices", "list")->call();
 *
 *	?>
 *	</code>
 *
 *	@author 	Andrew Waters <andrew@band-x-media.co.uk>
 *	@copyright 	Andrew Waters 2011-08-30
 *	@version 	1.0
 *	@license	http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */
class ServerDensityAPI {


	/* Your deets here: */
	const SD_ACCOUNT_SUBDOMAIN	= "";
	const SD_ACCOUNT_API_KEY	= "";
	const SD_ACCOUNT_USERNAME	= "";
	const SD_ACCOUNT_PASSWORD	= "";

	/* You probably don't want to be changing these */
	const API_URL				= "api.serverdensity.com";
	const API_VERSION			= "1.3";
	const SD_DOMAIN				= "serverdensity.com";


	/* These are set through setCall() */
	public $module				= "";
	public $method				= "";
	public $params				= array();

	/* These are built dynamically */
	protected $url				= "";
	protected $requestMethod	= "GET";
	protected $log				= array();


	/**
	 *
	 *	Set the Call up
	 *
	 *	@param	string	$module		The 'module' name
	 *	@param	string	$method		The method to call on the module
	 *	@param	array	$params		A keyed array of parameters to call with
	 *
	 *	@return $this
	 *
	 */
	public function setCall($module, $method, $params = array()) {

		$this->module = strtolower($module);
		$this->method = strtolower($method);
		$this->params = $params;

		if(!$this->verifyCall()) {

			$message = "";
			foreach($this->log as $log => $messages)
				foreach($messages as $messageContent)
					$message .= "[$log] $messageContent ";

			throw new ServerDensityAPIException($message);

		}

		return $this;
		
	}


	/**
	 *
	 *	Build the URL for the API call
	 *
	 */
	public function buildURL() {
	
		$url  = "https://" . self::SD_ACCOUNT_USERNAME . ":" . self::SD_ACCOUNT_PASSWORD . "@";
		$url .= self::API_URL . "/" . self::API_VERSION . "/";
		$url .= $this->module . "/" . $this->method;
		$url .= "?account=" . self::SD_ACCOUNT_SUBDOMAIN . "." . self::SD_DOMAIN . "&apikey=" . self::SD_ACCOUNT_API_KEY;

		if(!empty($this->params) && $this->setRequestMethod()->requestMethod == 'GET') {

			// add any method parameters to the URL (if we're GETting content)
			$url .= "&" . http_build_query($this->params, "\n");

		} else {

			// if we're including a deviceId we need it in the URL
			if(!empty($this->params['deviceId']))
				$url .= "&deviceId=" . $this->params['deviceId'];

		}

		$this->url = $url;

		return $this;

	}


	/**
	 *
	 *	Set Request Method
	 *
	 *	Set a well formatted requestMethod for comparitive operations
	 *
	 */
	 public function setRequestMethod() {

		$this->requestMethod = strtoupper(ltrim($this->requestMethod));

		return $this;

	 }


	/**
	 *
	 *	Make the call to SD
	 *
	 */
	public function call() {

		$this->buildURL();

		$handle = curl_init();
		curl_setopt($handle, CURLOPT_URL, $this->url);
		curl_setopt($handle, CURLOPT_USERAGENT, "Freedom/1.0");
		curl_setopt($handle, CURLOPT_HEADER, 0);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($handle, CURLOPT_TIMEOUT, 10);

		// if this method requires a POST build cURL differently
		if($this->setRequestMethod()->requestMethod == 'POST') {
			foreach($this->params as $key => $value)
				// deviceId needs to be place in the URL, not POST data
				if($key != 'deviceId') {
					if($key != 'payload') $value = urlencode($value);
					$postedContent[$key] = $value;
				}
			curl_setopt($handle, CURLOPT_POST, TRUE);
			curl_setopt($handle, CURLOPT_POSTFIELDS, $postedContent);
		}

		$response = json_decode(curl_exec($handle));
		curl_close($handle);

		$this->response = $response;

		return $this;

	}


	/**
	 *
	 *	Verify the API call
	 *
	 *	We'll see if what kind of request method it is (for cURL), and log any errors or warnings
	 *	that can be interpreted from the call.
	 *
	 *	In addition, values in $this->params will be typecast if required, and json encoded if they
	 *	are arrays (here's looking at you, {@link http://developer.serverdensity.com/docs/read/Methods_Metrics#postback Postback})
	 *
	 *	@return	BOOL	The validity of the call. If not valid $this->log['errors'] will have more info.
	 *
	 */
	public function verifyCall() {

		$validAPICalls = array(
			'alerts' => array(
				'getHistory' => array(
					'request' => 'GET',
					'params' => array(
						'alertId' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				),
				'getLast' => array(
					'request' => 'GET'
				),
				'getOpen' => array(
					'request' => 'GET'
				),
				'getOpenNotified' => array(
					'request' => 'GET'
				),
				'list' => array(
					'request' => 'GET',
					'params' => array(
						'alertId' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				),
				'list' => array(
					'request' => 'GET'
				),
				'pause' => array(
					'request' => 'POST',
					'params' => array(
						'alertId' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				),
				'resume' => array(
					'request' => 'POST',
					'params' => array(
						'alertId' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				)
			),
			'devices' => array(
				'add' => array(
					'request' => 'POST',
					'params' => array(
						'name' => array(
							'type' => 'string',
							'required' => TRUE
						),
						'ip' => array(
							'type' => 'string',
							'required' => FALSE
						),
						'group' => array(
							'type' => 'string',
							'required' => FALSE
						),
						'location' => array(
							'type' => 'string',
							'required' => FALSE
						),
						'provider' => array(
							'type' => 'string',
							'required' => FALSE
						),
						'notes' => array(
							'type' => 'string',
							'required' => FALSE
						),
						'userId' => array(
							'type' => 'string',
							'required' => FALSE
						)
					)
				),
				'addGroup' => array(
					'request' => 'POST',
					'params' => array(
						'name' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				),
				'delete' => array(
					'request' => 'POST',
					'params' => array(
						'deviceId' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				),
				'getByGroup' => array(
					'request' => 'GET',
					'params' => array(
						'group' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				),
				'getByHostName' => array(
					'request' => 'GET',
					'params' => array(
						'hostName' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				),
				'getById' => array(
					'request' => 'GET',
					'params' => array(
						'deviceId' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				),
				'getByIp' => array(
					'request' => 'GET',
					'params' => array(
						'ip' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				),
				'getByName' => array(
					'request' => 'GET',
					'params' => array(
						'name' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				),
				'list' => array(
					'request' => 'GET'
				),
				'listGroups' => array(
					'request' => 'GET'
				),
				'rename' => array(
					'request' => 'GET',
					'params' => array(
						'deviceId' => array(
							'type' => 'string',
							'required' => TRUE
						),
						'newName' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				)
			),
			'metrics' => array(
				'getLatest' => array(
					'request' => 'GET',
					'params' => array(
						'deviceId' => array(
							'type' => 'string',
							'required' => TRUE
						),
						'metricGroup' => array(
							'type' => 'string',
							'required' => FALSE
						),
						'metricName' => array(
							'type' => 'string',
							'required' => FALSE
						)
					)
				),
				'getRange' => array(
					'request' => 'GET',
					'params' => array(
						'deviceId' => array(
							'type' => 'string',
							'required' => TRUE
						),
						'metricGroup' => array(
							'type' => 'string',
							'required' => TRUE
						),
						'metricName' => array(
							'type' => 'string',
							'required' => TRUE
						),
						'rangeStart' => array(
							'type' => 'string',
							'required' => TRUE,
							'note' => "UTC time - ISO 8601 (without timezone) - eg 2011-08-30T20:30:00"
						),
						'rangeEnd' => array(
							'type' => 'string',
							'required' => TRUE,
							'note' => "UTC time - ISO 8601 (without timezone) - eg 2011-08-30T20:30:00"
						)
					)
				),
				'list' => array(
					'request' => 'GET',
					'params' => array(
						'os' => array(
							'type' => 'string',
							'required' => TRUE,
							'note' => 'linux, windows, freebsd or mac'
						)
					)
				),
				'postback' => array(
					'request' => 'POST',
					'params' => array(
						'deviceId' => array(
							'type' => 'string',
							'required' => TRUE
						),
						'payload' => array(
							'type' => 'array',
							'required' => TRUE,
							'note' => 'Cannot post back more frequently than 60s intervals'
						)
					)
				)
			),
			'mongo' => array(
				'getMaster' => array(
					'request' => 'GET',
					'params' => array(
						'replSetName' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				),
				'getReplicaSet' => array(
					'request' => 'GET'
				)
			),
			'processes' => array(
				'getByTime' => array(
					'request' => 'GET',
					'params' => array(
						'deviceId' => array(
							'type' => 'string',
							'required' => TRUE
						),
						'time' => array(
							'type' => 'string',
							'required' => TRUE,
							'note' => "UTC time - ISO 8601 (without timezone) - eg 2011-08-30T20:30:00"
						)
					)
				),
				'getRange' => array(
					'request' => 'GET',
					'params' => array(
						'deviceId' => array(
							'type' => 'string',
							'required' => TRUE
						),
						'rangeStart' => array(
							'type' => 'string',
							'required' => TRUE,
							'note' => "UTC time - ISO 8601 (without timezone) - eg 2011-08-30T20:30:00"
						),
						'rangeEnd' => array(
							'type' => 'string',
							'required' => TRUE,
							'note' => "UTC time - ISO 8601 (without timezone) - eg 2011-08-30T20:30:00"
						)
					)
				)
			),
			'users' => array(
				'add' => array(
					'request' => 'POST',
					'params' => array(
						'username' => array(
							'type' => 'string',
							'required' => TRUE
						),
						'password' => array(
							'type' => 'string',
							'required' => TRUE
						),
						'firstName' => array(
							'type' => 'string',
							'required' => TRUE
						),
						'lastName' => array(
							'type' => 'string',
							'required' => TRUE
						),
						'email' => array(
							'type' => 'string',
							'required' => FALSE
						),
						'apiEnabled' => array(
							'type' => 'integer',
							'required' => FALSE
						),
						'admin' => array(
							'type' => 'integer',
							'required' => FALSE
						),
						'timezone' => array(
							'type' => 'string',
							'required' => FALSE
						),
						'groups' => array(
							'type' => 'string',
							'required' => FALSE,
							'note' => 'Comma separated list of groups this user should belong to. If a specified group does not exist, it will be created.'
						),
						'groupPermissions' => array(
							'type' => 'integer',
							'required' => FALSE,
							'note' => 'unset = access all groups, 2 = access only to specified groups, 3 = access to all but specified groups'
						),
					)
				),
				'delete' => array(
					'request' => 'GET',
					'params' => array(
						'userId' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				),
				'getById' => array(
					'request' => 'GET',
					'params' => array(
						'userId' => array(
							'type' => 'string',
							'required' => TRUE
						)
					)
				)
			)
		);

		if(empty($this->module))
			$this->log['errors'][] = "Missing the module you're trying to call";

		if(empty($this->method))
			$this->log['errors'][] = "Missing the method you're trying to call";

		// check the request exists in the API
		if(!empty($validAPICalls[$this->module][$this->method])) {

			// store the API options
			$call = $validAPICalls[$this->module][$this->method];

			// if we have a request method specified
			if(!empty($call['request'])) $this->requestMethod = ltrim(strtoupper($call['request']));

			// let's make sure we've got the right params
			if(!empty($call['params'])) {

				foreach($call['params'] as $key => $options) {

					$type = $options['type'];

					// if this parameter is required and not set, log the error
					if($options['required'] && !isset($this->params[$key]))
						$this->log['errors'][] = "You must inlude the '$key' parameter in this call (expects a $type)";

					// if this parameter is set but is not the correct type, typecast it
					if(isset($this->params[$key])) {

						switch($type) {

							case('string') :
								if(!is_string($this->params[$key]))	{
									$this->params[$key] = (string) $this->params[$key];
									$this->log['warnings'][] = "Expected '$key' to be a $type";
								}
								break;

							case('integer') :
								if(!is_int($this->params[$key])) {
									$this->params[$key] = (int) $this->params[$key];
									$this->log['warnings'][] = "Expected '$key' to be a $type";
								}
								break;

							case('array') :
								if(!is_array($this->params[$key])) {
									$this->log['errors'][] = "You need to supply an array for '$key'";
								} else {
									$this->params[$key] = json_encode($this->params[$key]);
								}
								break;

						}

					}

				}

			}

			// if we've picked up any errors on our side
			if(!empty($this->log['errors'])) return FALSE;

			return TRUE;

		}

		return FALSE;

	}


}


/**
 *
 *	An SDAPI Exception Extension
 *
 *	@see Exception
 *
 */
class ServerDensityAPIException extends Exception {}

