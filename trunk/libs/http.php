<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Popy (popy.dev@gmail.com), Touzet David <dtouzet@gmail.com>
*  All rights reserved
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 *
 * @author Popy <popy.dev@gmail.com>
 * @author Touzet David <dtouzet@gmail.com>
 */
class http {
	/**
	 * Data wich will be used to build the next query
	 * @access public
	 * @var array
	 */
	var $output = Array(
		'parseUrl' => Array(), // Parsed target url
		'headers'  => Array(), // Client headers
		'formData' => Array(), // POST data
		'files'    => Array(), // POST files
		'cookies'  => Array(), // Client cookies
		'boundary' => '',      // multipart/form-data boundary
		'method'   => '',      // Query method : GET, POST
		'parts'    => Array(), // Query parts
	);

	/**
	 * Parsed response
	 * @access public
	 * @var array
	 */
	var $input=array(
		'status'  => Array(), // HTTP Status
		'headers' => Array(), // Incomming headers
		'cookies' => Array(), // Received cookies
		'buffer'  => '',      // Received query data
	);

	/**
	 * Cookies storage array : store every received cookies and load them on each new query (if host/path matches)
	 * @access public
	 * @var array
	 */
	var $cookies = array();

	/**
	 * Current connection handler
	 * @access public
	 * @var resource
	 */
	var $handler = null;

	/**
	 * Connection handlers storage : keep alive connections are stored here
	 * @access public
	 * @var array
	 */
	var $handlers = array();

	/**
	 * Saved transactions : each query is stored in this array
	 * @access public
	 * @var array
	 */
	var $pile = array();

	/**
	 * Config array
	 * @access public
	 * @var array
	 */
	var $conf = Array(
		'saveTransaction'  => true, // Should save output/input arrays ?
		'tryBasicHttpAuth' => true, // If basic auth is required, try with stored login ?
		'auth' => Array(
			'login' => '', // Basic auth login
			'pass'  => '', // Basic auth password
		),
		'send' => Array(
			'auto_receive' => true, // if set to true, the function "receive" will be called just after sending request trought s_send.
			'load_cookies' => true, // if set to true, domain cookies are loaded frome $this->cookies
		),
		'receive' => Array(
			'parse_cookies' => true, // if true, cookie header will be parsed and cookies will be stored in $this->input
			'follow_location' => true, // if set to true, the function "receive" will be called just after sending request trought s_send.
			'transfer_cookies_on_location' => true,
			'repost_on_location' => true, // If true, POST data will be reposted after following a Location header
		),
	);

	/**
	 * Open a connection with $host on port $port ONLY IF NEEDED :
	 *   If the handler has already been open and is still valid, then it is returned
	 *
	 * @param string $host = The host name / ip
	 * @param int $port = 
	 * @access public
	 * @return ressource 
	 */
	function &g_getHandler($host, $port = 80) {
		/* Declare */
		$handlerKey = $host . ':' . $port;
		$errno = false;
		$errstr = false;
		$handlerIsOk = isset($this->handlers[$handlerKey]);
	
		/* Begin */
		$handlerIsOk = $handlerIsOk && is_resource($this->handlers[$handlerKey]);
		$handlerIsOk = $handlerIsOk && !feof($this->handlers[$handlerKey]);

		if (!$handlerIsOk) {
			$this->handlers[$handlerKey] = @fsockopen(
				trim($host),
				intval($port),
				$errno,
				$errstr,
				5
			);
		}

		if ($errno || trim($errstr)) {
			$this->g_log('fsockopenError',array($errno,$errstr));
		} elseif (!$this->handlers[$handlerKey]['socket']) {
			$this->g_log('fsockopenUnknownError');		
		}

		return $this->handlers[$handlerKey];
	}

	/**
	 *
	 *
	 * @param 
	 * @access public
	 * @return void 
	 */
	function g_log() {
		/* Declare */
	
		/* Begin */
	}

	/**
	 * Send the HTTP request on web page $url :
	 *   Will build the HTTP query (including added cookies, post params, headers, etc...) and, if $receive
	 *   is set to TRUE, will receive the HTTP response
	 *   Note that GET params must be set in $url
	 *
	 * @param string $url = the url to GET / POST
	 * @access public
	 * @return bool (true if success) 
	 */
	function s_send($url) {
		/* Declare */
		$errno = 0;
		$errstr = '';

		/* Begin */
		$this->output['method'] = '';
		$this->output['boundary'] = '';
		$this->output['parts'] = array();

		$this->input['div'] = '';

		//*** Parsing url
		$this->output['parseUrl'] = $this->g_parseUrl($url);

		//*** Loading domain / path cookies
		if ($this->conf['send']['load_cookies']) {
			$this->s_loadCookies();
		}

		//*** Build query (POST if any params to send, else GET)
		$this->s_buildFullQuery($url);

		//*** Get socket handler
		$this->handler = $this->g_getHandler($this->output['parseUrl']['host'], $this->output['parseUrl']['port']);

		if ($this->handler) {
			//** Sending query
			fputs(
				$this->handler,
				$this->output['parts']['head'].$this->output['parts']['data']
			);

			//** Now receiving response / exiting function
			if ($this->conf['send']['auto_receive']) {
				return $this->r_receive();
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Read the HTTP response (s_send should have been called before)
	 *
	 * @access public
	 * @return bool =  true if everything is ok
	 */
	function r_receive() {
		/* Declare */
		$buff = '';
		$rest = 0;
		$ok = false;
	
		/* Begin */
		//*** Reset receive storage
		$this->r_resetBuffer();
		$this->r_resetHeaders();

		//*** Check connection validity
		if (!is_resource($this->handler) || feof($this->handler)) {
			$this->g_log('connectionClosed');
			return false;
		}

		//*** Read and parse headers
		$this->r_readHeaders();

		//*** Fetching cookies in the input stack
		if ($this->conf['receive']['parse_cookies']) {
			$this->r_getCookies();
		}

		//*** Use headers to read body content
		$this->r_readBody();

		//*** Saving query / response
		if ($this->conf['saveTransaction']) {
			$this->pile[] = array(
				'request' => $this->output,
				'response' => $this->input
			);
		}

		$doBasicAuthenticate = isset($this->input['headers']['www-authenticate']);
		$doBasicAuthenticate = $doBasicAuthenticate && $this->conf['tryBasicHttpAuth'];
		$doBasicAuthenticate = $doBasicAuthenticate && strtolower(substr($this->input['headers']['www-authenticate'],0,5)) == 'basic';
		$doBasicAuthenticate = $doBasicAuthenticate && $this->conf['auth']['login'];

		$locationFollow = isset($this->input['headers']['location']);
		$locationFollow = $locationFollow && trim($this->input['headers']['location']);
		$locationFollow = $locationFollow && $this->conf['receive']['follow_location'];

		if ($doBasicAuthenticate) {
			//** Add auth header
			$this->s_addHeader('Authorization','Basic '.base64_encode($this->conf['auth']['login'].':'.$this->conf['auth']['pass']));

			//** Prevents infinite recursing due to wrong login/password
			$this->conf['tryBasicHttpAuth'] = false;

			//** Retry request
			$this->s_send($url);

			//** Restoring conf
			$this->conf['tryBasicHttpAuth'] = true;

		} elseif ($locationFollow) {
			//** Checking Location url
			$urlParams = $this->g_parseUrl($this->input['headers']['location'],true);

			if (isset($urlParams['host'])) {
				//* Absolute URL
				$theUrl = $this->input['headers']['location'];
			} elseif (substr($urlParams['path'],0,1)=='/') {
				//* Relative to server URL
				$theUrl = $this->output['parseUrl']['host'].':'.$this->output['parseUrl']['port'] . $this->input['headers']['location'];
			} else {
				//* Relative to current path
				$theUrl = $this->output['parseUrl']['host'].':'.$this->output['parseUrl']['port'] . 
					div::resolves(div::dirname($this->output['parseUrl']['path']) . '/' . $this->input['headers']['location']);
			}

			if (!$this->conf['receive']['repost_on_location']) {
				//* If needed, prevents double posting
				$this->s_resetPostParam();
				$this->s_resetFiles();
			}

			//** Sending request
			$this->s_send($theUrl);
		}

		return true;
	}


	/**
	 *
	 *
	 * @param 
	 * @access public
	 * @return void 
	 * @deprecated
	 */
	function s_transferCookies() {
		if (!is_array($this->input['cookies'])) return FALSE;
		foreach ($this->input['cookies'] as $name=>$value) {
			$this->s_addCookie($name,$value['value']);
		}
	}

	/******************************************/
	/******* Sending : Data management ********/
	/******************************************/

	/**
	 * Adds a header to the query (like Referer, User-Agent, etc)
	 *
	 * @param string $name = header name
	 * @param string $value = header value
	 * @access public
	 * @return void 
	 */
	function s_addHeader($name,$value) {
		//*** Cleaning name
		$name = trim($name);
		$name = str_replace("\r", '', $name);
		$name = str_replace("\n", '', $name);
		$name = str_replace("\t", '', $name);
		$name = str_replace(':', '', $name);
		$name = str_replace(' ', '-', $name);

		//*** Cleaning value
		$value = trim($value);
		$value = str_replace("\r", '', $value);
		$value = str_replace("\n", '', $value);

		$this->output['headers'][$name] = $value;
	}


	/**
	 * Add a simple param to the query (like in a form)
	 *
	 * @param string $name = name of the POST param (like in a form)
	 * @param string $value = value of the param
	 * @access public
	 * @return void 
	 */
	function s_addPostParam($name,$value) {
		$this->output['formData'][$name]=$value;
	}



	/**
	 * Add a file to the query. Return FALSE if error
	 *
	 * @param string $name = name of the POST param (like in a form)
	 * @param string $filename = the file name :]
	 * @param string $content = The file content
	 * @access public
	 * @return boolean 
	 */
	function s_addFileByContent($name,$filename,$content) {
		if (!trim($filename)) return FALSE;
		if (!trim($name)) return FALSE;
		$this->output['files'][$name]=array(
			'filename'=>basename($filename),
			'content'=>$content,
			'size'=>strlen($content)
			);
		return TRUE;
	}


	/**
	 * Adds a cookie to next query
	 *
	 * @param string $name = the cookie var name
	 * @param string $value = the cookie value
	 * @access public
	 * @return void
	 */
	function s_addCookie($name,$value) {
		//*** Cleaning name
		$name = preg_replace("/[=,; \t\r\n\013\014]/", '', $name);

		//*** Storing cookie value
		$this->output['cookies'][$name] = $value;
	}

	/**
	 * Read domain/path stored cookies and add them to next query
	 * 
	 * @access public
	 * @return void 
	 */
	function s_loadCookies() {
		$hostKey = $this->output['parseUrl']['host'] . ':' . $this->output['parseUrl']['port'];
		if (isset($this->cookies[$hostKey])) {
			//** Browse "domain" cookies
			foreach ($this->cookies[$hostKey] as $name => $val) {
				//* Check cookie validity and add it
				if (!isset($val['path']) || div::isFirstPartOfStr($this->output['parseUrl']['path'], $val['path'])) {
					$this->s_addCookie($name, $val['value']);
				}
			}
		}
	}

	/******************************************/
	/******* Sending : Query buildiing ********/
	/******************************************/

	/**
	 *
	 *
	 * @param 
	 * @access public
	 * @return void 
	 */
	function s_buildFullQuery($url) {
		/* Declare */
		$br="\r\n";
	
		/* Begin */
		$this->s_addHeader('Host',$this->output['parseUrl']['host']);

		if (!isset($this->output['method']) || !trim($this->output['method'])) {
			//** Determine method/Content-Type
			$this->output['method'] = 'GET';

			if (!isset($this->output['headers']['Content-Type']) && (count($this->output['formData']) || count($this->output['files']))) {
				//* We have something to send
				$this->output['method'] = 'POST';

				//* If we have files to upload, then it's a "multipart/form-data"
				if (count($this->output['files'])) {
					$this->s_addHeader('Content-Type','multipart/form-data, boundary='.$this->output['boundary']);
				} else {
					$this->s_addHeader('Content-Type','application/x-www-form-urlencoded');
				}
			}
		}

		//*** Build "body"
		$this->s_buildQueryData();

		//*** Add content-length header
		if ($this->output['parts']['datasize']) {
			$this->s_addHeader('Content-length', $this->output['parts']['datasize']);
		}

		//*** Add cookies header
		if (count($this->output['cookies'])) {
			$this->s_addHeader('Cookie', $this->g_renderCookies($this->output['cookies']));
		}

		//*** Build HTTP header
		$this->output['parts']['head'] = $this->output['method'].' '.$this->output['parseUrl']['path'].' HTTP/1.1'.$br;
		//*** Concat headers
		$this->output['parts']['head'] .= $this->g_renderHeaders($this->output['headers']).$br;
	}

	/**
	 * Builds the data part of the query.
	 *
	 * @access public
	 * @return void 
	 */
	function s_buildQueryData() {
		/* Declare */
		$cType = reset(div::trimExplode(',', $this->output['headers']['Content-Type'], true));
		$data = array();
		$br = "\r\n";
		$ok = false;
		$boundary = 'boundary';
	
		/* Begin */
		switch ($cType) {
		case 'multipart/form-data':
			//** Building FORM vars
			foreach ($this->output['formData'] as $key=>$val) {
				$data[] = 'Content-Disposition: form-data; name="' . $key . '"' . $br . $br . $val . $br;
			}

			//** Adding files
			foreach (array_keys($this->output['files']) as $key) {
				$data[] = 'Content-disposition: form-data;name="' . $key . '";filename="' . $this->output['files'][$key]['filename'] . '"' . $br .
					'Content-Type: application/octet-stream' . $br .
					'Content-Length: ' . $this->output['files'][$key]['size'] . $br .
					$br .
					$this->output['files'][$key]['content'] . $br;
			}

			//*** Calculating boundary
			while (!$ok) {
				$ok = true;

				//** generate a random boundary
				$boundary = md5($boundary) . md5(microtime());

				//** Check boundary
				for ($i=0; $i<count($data); $i++) {
					//* If boundary is found in data, then we need another boundary
					if (strpos($data[$i], $boundary) !== false) {
						$ok = false;
						break;
					}
				}
			}

			//*** Register boundary
			$this->output['boundary'] = $boundary;

			//*** Building body
			$this->output['parts']['data'] = '--' . $boundary . $br .
				implode(
					'--' . $boundary . $br .
					'--' . $boundary . $br,
					$data
				) .
				'--' . $boundary . '--' . $br;
			$this->output['parts']['datasize'] = strlen($this->output['parts']['data']);
			break;
		case 'application/x-www-form-urlencoded':
			$this->output['parts']['data'] = substr(div::implodeArrayForUrl('', $this->output['formData']), 1);
			$this->output['parts']['datasize'] = strlen($this->output['parts']['data']);
			break;
		default:
			break;
			$this->output['parts']['datasize']=0;
			$this->output['parts']['data']='';
		}

		return true;
	}


	/******************************************/
	/******** Sending : Reset funcs ***********/
	/******************************************/

	/**
	 * Reset headers array
	 *
	 * @param bool $setDefaults = if true, will set default headers (@see s_addDefaultHeaders)
	 * @access public
	 * @return void 
	 */
	function s_resetHeaders($addDef = true) {
		$this->output['headers']=array();
		if ($addDef) $this->s_addDefaultHeaders();
	}

	/**
	 * Add default headers to headers array
	 *
	 * @access public
	 * @return void 
	 */
	function s_addDefaultHeaders() {
		$this->s_addHeader('Host', '');
		$this->s_addHeader('User-Agent', 'PHP/' . PHP_VERSION . ' (' . PHP_OS . ') extension pp_lib:tx_pplib_http for TYPO3 CMS');
		$this->s_addHeader('Accept', 'text/html;q=0.9,text/*;q=0.8,image/*:q=0.7,*/*;q=0.1');
		$this->s_addHeader('Accept-Language', 'en-us, en;q=0.50');
		$this->s_addHeader('Accept-Charset', 'UTF-8');
		$this->s_addHeader('Connection', 'keep-alive');
		$this->s_addHeader('Keep-Alive', '300');
		$this->s_addHeader('Pragma', 'no-cache');
		$this->s_addHeader('Cache-Control', 'no-cache');
		if (function_exists('gzinflate')) {
			if (false && function_exists('crc32')) {
				$this->s_addHeader('Accept-Encoding','gzip,deflate');
			} else {
				$this->s_addHeader('Accept-Encoding', 'deflate');
			}
		}
	}



	/**
	 * Reset _POST array
	 *
	 * @access public
	 * @return void 
	 */
	function s_resetPostParam() {
		$this->output['formData']=array();
	}

	function s_resetFiles() {
		$this->output['files']=array();
	}

	function s_resetCookies() {
		$this->output['cookies']=array();
	}

	function s_resetParts() {
		$this->output['parts']=array();
	}

	function s_resetUrl() {
		$this->output['parseUrl']=array();
	}

	/**
	 * Reset query config
	 *
	 * @param bool $setDefaults = if true, will set default headers (@see s_addDefaultHeaders)
	 * @access public
	 * @return void 
	 */
	function s_resetAll($setDefaults=TRUE) {
		$this->s_resetParts();
		$this->s_resetHeaders($setDefaults);
		$this->s_resetCookies();
		$this->s_resetFiles();
		$this->s_resetPostParam();
		$this->s_resetUrl();
	}

	/******************************************/
	/****** Receiving : data management *******/
	/******************************************/


	/**
	 * Read HEAD response part and parse headers
	 * 
	 * @access public
	 * @return void 
	 */
	function r_readHeaders() {
		/* Declare */
		$firstLine = '';
	
		/* Begin */
		//*** Get first response line (HTTP status message)
		$firstLine = $this->r_fgets();

		//*** Trim ending \r\n
		$firstLine = trim($firstLine);

		//*** Split HTTP status message
		list(
			$this->input['status']['protocole'],
			$this->input['status']['code'],
			$this->input['status']['msg']
			) = explode(' ', $firstLine, 3);

		//*** Fetch each response header (first blank line is the header part end)
		while (trim($buff = $this->r_fgets())) {
			$this->r_addHeader($buff);
		}
	}
	
	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function r_fgets($trimEndLine = true) {
		/* Declare */
		$result = '';
		$lastReadChar = false;
	
		/* Begin */
		while (!feof($this->handler) && $lastReadChar != "\n") {
			$lastReadChar = fgetc($this->handler);
			$result .= $lastReadChar;
		}

		if (is_bool($lastReadChar)) {
			$result = false;
		} else {
			if ($trimEndLine) {
				if (substr($result, -2) == "\r\n") {
					$result = substr($result, 0, -2);
				} elseif (substr($result, -1) == "\n") {
					$result = substr($result, 0, -1);
				}
			}
		}

		return $result;
	}

	/**
	 * Read body repsonse part
	 *  Handle transfer-encoding chunked & keep-alive connections
	 * 
	 * @access public
	 * @return void 
	 */
	function r_readBody() {
		/* Declare */
		$rest = 0;
	
		/* Begin */

		//*** Now reading body part
		if (isset($this->input['headers']['content-length'])) {
			//** content-length header found
			$rest = $contLength = intval($this->input['headers']['content-length']);

			//** Reading body content
			while (($rest > 0) && !feof($this->handler)) {
				$this->input['buffer'] .= fread($this->handler, $rest);
				$rest = $contLength - strlen($this->input['buffer']);
			}
		} elseif ($this->input['headers']['transfer-encoding'] == 'chunked') {
			$endingFound = false;

			//** Chunked suppord :
			while (!$endingFound && !feof($this->handler)) {
				if (strlen($this->input['buffer'])) {
					//* Read empty line
					fread($this->handler, 2);
				}

				//* Reading part size (wich is hexadecimal number)
				$hexStr = fgets($this->handler);
				$rest = $sizePart = hexdec(trim($hexStr));

				if ($sizePart == 0) {
					$endingFound = true;
					//* Read empty line
					fread($this->handler, 2);
				}
				
				$tempBuffer = '';

				//* Reading part content
				while (($rest > 0) && !feof($this->handler)) {
					$tempBuffer .= fread($this->handler, $rest);
					$rest = $sizePart - strlen($tempBuffer);
				}
				
				$this->input['buffer'] .= $tempBuffer;

			}

		} elseif ($this->input['headers']['connection'] != 'keep-alive') {
			while (!feof($this->handler)) {
				$this->input['buffer'] .= fread($this->handler,1024);
			}
		} else {
			//*** Should never come here :)
			$this->g_log('unknownContentLength');
			$char = 0;
			while (!feof($this->handler) && ord($char) !== false) {
				$char = fgetc($this->handler);
				$this->input['buffer'] .= $char;
			}
		}

		//*** Handling gzip compression
		if (trim($this->input['buffer'])) {
			switch ($this->input['headers']['content-encoding']){
			case 'gzip': 
				$this->input['buffer'] = $this->g_gzdecode($this->input['buffer']);
				break;
			case 'deflate': 
				$this->input['buffer'] = gzinflate($this->input['buffer']);
				break;
			default:
				$this->g_log('receiveUnknownEncoding');
				break;
			}
		}

	}

	/**
	 *
	 *
	 * @param 
	 * @access public
	 * @return void 
	 */
	function r_addHeader($line) {
		/* Declare */
		list($name,$value)=explode(':',$line,2);
		$name = strtolower(trim($name));
		$buff='';

		/* Begin */
		if (isset($this->input['headers'][$name])) {
			$this->input['headers'][$name].="\n";
		}
		$this->input['headers'][$name].=trim($value);
		return FALSE;
	}


	/**
	 *
	 *
	 * @param 
	 * @access public
	 * @return void 
	 */
	function r_getCookies() {
		if ($this->input['headers']['set-cookie']) {
			foreach (explode("\n",$this->input['headers']['set-cookie']) as $cookie) {
				list($name, $rest)=explode('=',$cookie,2);
				$name = rawurldecode($name);
				$value = rawurldecode($value);

				list($value,$rest)=explode(';',$rest,2);

				foreach (explode(';',$rest) as $parts) {
					list($pName,$pVal)=explode('=',$parts);
					$this->input['cookies'][$name][trim($pName)]=$pVal;
				}
				$this->input['cookies'][$name]['value']=rawurldecode($value);
			}

			$hostKey = trim($this->output['parseUrl']['host']) . ':' . intval($this->output['parseUrl']['port']);

			$this->cookies[$hostKey] = $this->input['cookies'];
		}
	}

	/******************************************/
	/****** Receiving : Reset funcs ***********/
	/******************************************/

	function r_resetBuffer() {
		$this->input['buffer']='';
	}

	function r_resetHeaders() {
		$this->input['headers']=array();
	}


	/******************************************/
	/**************   Div Funcs   *************/
	/******************************************/


	/**
	 * Parse an url : same usage than parse_url
	 *
	 * @param string $url = the url to parse
	 * @param bool $dontCheckHost = if false, then if the url has no protocole, teh first "dir" will be considered as host name
	 * @access public
	 * @return void 
	 */
	function g_parseUrl($url, $dontCheckHost = false) {
		/* Declare */
		$parse = parse_url($url);
		$result = array();
		$schemeArr = array('http'=>80,'https'=>443);
	
		/* Begin */
		//*** Check host
		if (!trim($parse['host']) && !$dontCheckHost) {
			list($parse['host'], $parse['path']) = explode('/',$parse['path'],2);
			$parse['path'] = '/'.$parse['path'];
		}
		$result['host'] = $parse['host'];

		//*** Ensure that path is set
		if (!isset($parse['path']) || !trim($parse['path'])) {
			$parse['path']='/';
		}

		//*** concat path and querystring
		if (isset($parse['query']) && trim($parse['query'])) {
			$parse['path'] .= '?' . $parse['query'];
		}

		$result['path'] = $parse['path'];

		//*** determine port number with scheme
		if (!intval($parse['port']) && isset($parse['scheme'])) {
			$parse['port']=$schemeArr[$parse['scheme']];
		}

		//*** Default port
		if (!intval($parse['port'])) {
			$parse['port'] = 80;
		}
		$result['port'] = intval($parse['port']);

		return $result;
	}

	/**
	 *
	 *
	 * @param 
	 * @access public
	 * @return void 
	 */
	function g_renderHeaders($headers) {
		/* Declare */
		$content='';
	
		/* Begin */
		foreach ($headers as $key=>$val) {
			$content.=$key.': '.$val."\r\n";
		}
		return $content;
	}

	/**
	 *
	 *
	 * @param 
	 * @access public
	 * @return void 
	 */
	function g_renderCookies($cookies) {
		/* Declare */
		$content = '';
	
		/* Begin */
		foreach ($this->output['cookies'] as $name => $value) {
			$content .= $name . '=' . rawurlencode($value) . ';';
		}
		return $content;
	}



	//*** Thx to Aaron G. (found at http://php.net/manual/en/function.gzencode.php)
	function g_gzdecode($data) {
		$len = strlen($data);
		if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
			return null;  // Not GZIP format (See RFC 1952)
		}
		$method = ord(substr($data,2,1));  // Compression method
		$flags  = ord(substr($data,3,1));  // Flags
		if ($flags & 31 != $flags) {
			// Reserved bits are set -- NOT ALLOWED by RFC 1952
			return null;
		}
		// NOTE: $mtime may be negative (PHP integer limitations)
		$mtime = unpack('V', substr($data,4,4));
		$mtime = $mtime[1];
		$xfl  = substr($data,8,1);
		$os    = substr($data,8,1);
		$headerlen = 10;
		$extralen  = 0;
		$extra    = '';
		if ($flags & 4) {
			// 2-byte length prefixed EXTRA data in header
			if ($len - $headerlen - 2 < 8) {
				return false;    // Invalid format
			}
			$extralen = unpack('v',substr($data,8,2));
			$extralen = $extralen[1];
			if ($len - $headerlen - 2 - $extralen < 8) {
				return false;    // Invalid format
			}
			$extra = substr($data,10,$extralen);
			$headerlen += 2 + $extralen;
		}

		$filenamelen = 0;
		$filename = '';
		if ($flags & 8) {
			// C-style string file NAME data in header
			if ($len - $headerlen - 1 < 8) {
				return false;    // Invalid format
			}
			$filenamelen = strpos(substr($data,8+$extralen),chr(0));
			if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
				return false;    // Invalid format
			}
			$filename = substr($data,$headerlen,$filenamelen);
			$headerlen += $filenamelen + 1;
		}

		$commentlen = 0;
		$comment = "";
		if ($flags & 16) {
			// C-style string COMMENT data in header
			if ($len - $headerlen - 1 < 8) {
				return false;    // Invalid format
			}
			$commentlen = strpos(substr($data,8+$extralen+$filenamelen),chr(0));
			if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
				return false;    // Invalid header format
			}
			$comment = substr($data,$headerlen,$commentlen);
			$headerlen += $commentlen + 1;
		}

		$headercrc = "";
		if ($flags & 1) {
			// 2-bytes (lowest order) of CRC32 on header present
			if ($len - $headerlen - 2 < 8) {
				return false;    // Invalid format
			}
			$calccrc = crc32(substr($data,0,$headerlen)) & 0xffff;
			$headercrc = unpack('v', substr($data,$headerlen,2));
			$headercrc = $headercrc[1];
			if ($headercrc != $calccrc) {
				return false;    // Bad header CRC
			}
			$headerlen += 2;
		}

		// GZIP FOOTER - These be negative due to PHP's limitations
		$datacrc = unpack('V',substr($data,-8,4));
		$datacrc = $datacrc[1];
		$isize = unpack('V',substr($data,-4));
		$isize = $isize[1];

		// Perform the decompression:
		$bodylen = $len-$headerlen-8;
		if ($bodylen < 1) {
			// This should never happen - IMPLEMENTATION BUG!
			return null;
		}
		$body = substr($data,$headerlen,$bodylen);
		$data = '';
		if ($bodylen > 0) {
			switch ($method) {
			case 8:
				// Currently the only supported compression method:
				$data = gzinflate($body);
				break;
				default:
				// Unknown compression method
				return false;
			}
		} else {
			// I'm not sure if zero-byte body content is allowed.
			// Allow it for now...  Do nothing...
		}

		// Verifiy decompressed size and CRC32:
		// NOTE: This may fail with large data sizes depending on how
		//      PHP's integer limitations affect strlen() since $isize
		//      may be negative for large sizes.
		if ($isize != strlen($data) || crc32($data) != $datacrc) {
			// Bad format!  Length or CRC doesn't match!
			return false;
		}
		return $data;
	}

}
?>