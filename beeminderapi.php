<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file provides a basic interface to the Beeminder API:
 * https://www.beeminder.com/api
 * It allows users to:
 * <ul>
 * <li>Get user info</li>
 * <li>Get goal info</li>
 * <li>Get goal datapoints</li>
 * <li>Add goal datapoint</li>
 * </ul>
 * 
 * PHP version 5.4
 *
 * @author     Manuela Hutter <manu@manooh.com>
 * @copyright  2015 Manuela Hutter
 * @license    https://opensource.org/licenses/MIT  The MIT License (MIT)
 * @version    0.1
 */


// {{{ Enable error reporting, if necessary
error_reporting(E_ALL);
ini_set("display_errors", 1);
// }}}


// {{{ Start session
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}
// }}}


// {{{ Handle access token callback: save token, redirect
if (isset($_GET['access_token'])) {
	$session = new BeeminderSession();
	$session->setAccessToken($_GET['access_token']);

	$session->redirect();
}
// }}}


/**
 * BeeminderSession handles global session parameters,
 * for both personal authentication token and client OAuth
 */
class BeeminderSession {
	const REDIRECT  = 'http://www.mysite.com/myproject/beeminderapi.php';

	/** Checks if a token is set for this session. */
	public function isEstablished() {
		$token = $this->getTokenPair();
		return isset($token);
	}

	/**
	 * Returns name and value of the token set. The name is either
	 * 'auth_token' (for personal authentication token) or
	 * 'access_token' (for client OAuth). 
	 * Returns null if no token is set and no connection is established.
	 */
	public function getTokenPair() {
		if (isset($_SESSION['auth_token'])) {
			return array ('auth_token'   => $_SESSION['auth_token']);
		}
		if (isset($_SESSION['access_token'])) {
			return array ('access_token' => $_SESSION['access_token']);
		}
		return null;
	}

	/**
	 * Set the personal authentication token. Set directly by library user.
	 * Calling this function is enough to set up connection to Beeminder.
	 * 
	 * @link https://www.beeminder.com/api/v1/auth_token.json Personal token retrieval
	 * @link https://www.beeminder.com/api#auth Beeminder auth documentation
	 */
	public function setAuthToken($authToken) {
		$_SESSION['auth_token'] = $authToken;
		unset($_SESSION['access_token']);
	}

	/**
	 * Set the OAuth access token. Not set directly, but when getting the callback
	 * from Beeminder. (See GET parameter handling on top of file.)
	 * Starting the OAuth authentication is done via BeeminderConnector::initOAuth()
	 * In order to use OAuth, you need to create a Beeminder app. Make sure the
	 * redirect URI matches REDIRECT
	 * 
	 * @see ::REDIRECT
	 * 
	 * @link https://www.beeminder.com/apps/new Create new app here
	 * @link https://www.beeminder.com/api#auth Beeminder auth documentation
	 */
	public function setAccessToken($accessToken) {
		$_SESSION['access_token'] = $accessToken;
		unset($_SESSION['auth_token']);
	}
	
	/**
	 * Init OAuth connection to Beeminder.
	 * Make sure app is created and you have the right redirect URI set.
	 * 
	 * @link https://www.beeminder.com/apps/new Create new app here
	 * @link https://www.beeminder.com/api#auth Beeminder auth documentation
	 */
	public function initOAuth($redirect_uri, $clientId) {
		$this->setRedirectURI($redirect_uri);
		$values = array(
			'client_id'     => $clientId,
			'redirect_uri'  => self::REDIRECT,
			'response_type' => 'token'
		);
		$uri = BeeminderConnector::AUTHURL . '?' . http_build_query($values);
		header('Location: ' . $uri);
		exit();
	}


	/**
	 * Set where this file will redirect to after successful OAuth connection.
	 * Usually set via BeeminderConnector::initOAuth().
	 * 
	 * @see BeeminderConnector::initOAuth()
	 */
	public function setRedirectURI($uri) {
		$_SESSION['redirect'] = $uri;
	}

	/**
	 * Redirect to redirect URI after successful authentication.
	 */
	public function redirect() {
		$uri = $this->getSessionParam('redirect');
		if (isset($uri)) {
			header('Location: ' . $uri);
		}
		exit();
	}

	/**
	 * Return session parameter. If no session is active or if no 
	 * entry for the specified key, return null.
	 */
	protected function getSessionParam($key) {
		$value = null;
		if (session_status() == PHP_SESSION_ACTIVE &&
			array_key_exists($key, $_SESSION)) {
			$value = $_SESSION[$key];
		}
		return $value;
	}
}


/**
 * BeeminderConnector is the main access point for Beeminder interaction.
 * To set up, just create session and connector classes, and then either set 
 * private token on session or call BeeminderSession::initOAuth(). 
 * Then you are ready to retrieve or set any info you'd need.
 */
class BeeminderConnector {
	const BASEURL   = 'https://www.beeminder.com/api/v1';
	const AUTHURL   = 'https://www.beeminder.com/apps/authorize';

	/** Session */
	private $_session;

	/** Constructor. Takes a session and saves it. */
	function __construct(BeeminderSession $session) {
		$this->_session = $session;
	}

	/**
	 * Get all info for a user.
	 * @link https://www.beeminder.com/api#user
	 */
	public function getUser($username) {
		$url = self::BASEURL . '/users/' . $username . '.json';
		$values = $this->_session->getTokenPair();
		
		return $this->callAPI($url, $values, 'GET');
	}

	/**
	 * Get all goal info
	 * @link https://www.beeminder.com/api#goal
	 */
	public function getGoal($username, $goalname) {
		$url = self::BASEURL . '/users/' . $username . '/goals/' . $goalname . '.json';
		$values = $this->_session->getTokenPair();
		
		return $this->callAPI($url, $values, 'GET');
	}

	/**
	 * Get datapoints for one goal
	 * @link https://www.beeminder.com/api#datapoint
	 */
	public function getDatapoints($username, $goalname) {
		$url = self::BASEURL . '/users/' . $username . '/goals/' . $goalname . '/datapoints.json';
		$values = $this->_session->getTokenPair();
		
		return $this->callAPI($url, $values, 'GET');
	}

	/**
	 * Create a new datapoint for existing goal
	 * @link https://www.beeminder.com/api#datapoint
	 */
	public function createDatapoint($username, $goalname, $timestamp, $value, $comment=' ', $sendmail='false') {
		$url = self::BASEURL . '/users/' . $username . '/goals/' . $goalname . '/datapoints.json';

		$values = array(
			'timestamp'  => $timestamp, 
			'value'      => $value, 
			'comment'    => $comment, 
			'sendmail'   => $sendmail);
		$values = array_merge($this->_session->getTokenPair(), $values);

		return $this->callAPI($url, $values, 'POST');
	}

	/**
	 * Create GET/POST connection using cURL, the actual Beeminder API call
	 */
	protected function callAPI($url, $values, $method = 'GET') {
		$result = array();

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		if ($method == 'POST') {
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $url,
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => $values
			));
		} else {
			$url .= "?" . http_build_query($values);
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $url
			));
		}

/*
		echo "Call to API: <br>";
		var_dump($url);
		echo "<br>";
*/
		$result = curl_exec($curl);
		if(curl_errno($curl)){
			echo 'Request Error:' . curl_error($ch);
		}
		curl_close($curl);

		return json_decode($result, true);
	}
}

?>
