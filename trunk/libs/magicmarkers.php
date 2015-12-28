<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Touzet David <dtouzet@gmail.com>
*  All rights reserved
*
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
 * @author Touzet David <dtouzet@gmail.com>
 */
class magicmarkers {

	/**
	 * 
	 * @access protected
	 * @var object
	 */
	protected $caller = null;

	/**
	 * 
	 * @access protected
	 * @var array
	 */
	protected static $userFuncs = array();


	/**
	 * 
	 * 
	 * @param object $caller = 
	 * @access public
	 * @return void 
	 */
	public static function &getInstance(&$caller) {
		/* Declare */
		$res = new magicmarkers();

		$res->caller = &$caller;

		if (empty(self::$userFuncs)) {
			self::buildCallableUserFuncs();
		}

		return $res;
	}

	/**
	 * Static initialisation : load & build the magick markers list
	 * 
	 * @access protected static
	 * @return void 
	 */
	protected static function buildCallableUserFuncs() {
		/* Declare */
		self::$userFuncs = array();
	
		/* Begin */
		foreach (self::getMagicMarkerList() as $k => $v) {
			$k = strtolower($k);
			
			self::$userFuncs[$k] = div::getCallableFromStringUserFunc($v);
		}
	}

	/**
	 * Apply a magickmarker, determinated by the $subpartKey (basically, the marker/subpart key)
	 * Result and behaviour may vary a lot, depending on wich magickmarker is called, and if
	 *   it was called by a marker or a subpart (so if $subpartContent is null or not)
	 * Typically, this function is called by the tx_pplib_template class
	 * Marker key syntax : <magickMarkerKey>:<parameters>
	 * 
	 * @param string $subpartKey = subpart/marker key
	 * @param mixed $currentData = current markerArray/object
	 * @param string $subpartContent = current subpart content (null if called from marker)
	 * @access public
	 * @return string 
	 */
	public function call($subpartKey, &$currentData, $subpartContent = null) {
		/* Declare */
		list($key, $params) = explode(':', $subpartKey, 2);
		$key = strtolower($key);

		/* Begin */
		if (isset(self::$userFuncs[$key])) {
			$subpartContent = call_user_func(self::$userFuncs[$key], $this->caller, $params, $currentData, $subpartContent);
		}

		return $subpartContent;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function getMagicMarkerList() {
		$markers = array();
		foreach ($GLOBALS['LIBCONF']['template']['magicMarkers'] as $k => $v) {
			$markers[$k] = $v;
		}

		return $markers;
	}

	/**
	 * Check if a subpart key is a valid magic marker
	 * 
	 * @param string $subpartKey = subpart/marker key
	 * @access public
	 * @return bool 
	 */
	public function isCallable($subpartKey) {
		/* Declare */
		list($key, $params) = explode(':', $subpartKey, 2);
		$key = strtolower($key);

		/* Begin */
		return isset(self::$userFuncs[$key]);
	}

	/**
	 * 
	 * 
	 * @param string $content
	 * @access public
	 * @return void 
	 */
	function formatSizeMarkers(&$content) {
		magicmarkers::replaceKeySubpartBy($content, 'FORMAT_SIZE', array(&$this, 'formatSize_callback'));
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function formatSize_callback($match) {
		return div::formatSize($match[2], $match[1]);
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function processDateSubpart(&$content) {
		magicmarkers::replaceKeySubpartBy($content, 'DATE', array(&$this, 'processDateSubpart_callback'));
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function link($caller, $params, $currentData) {
		$params = explode(',', $params);
		$paramLink = isset($params[2])? div::explodeStringForLink($params[2]) : array();

		return div::link(isset($params[0]) ? $params[0] : '', $paramLink, isset($params[1]) ? $params[1] : '');
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function addJS($caller, $params, $currentData) {
		$params = explode(',', $params);
		if (!isset($params[0]) && !file_exists(PATH_SITE . $params[0])) {
			return '';
		}

		
		headmgr::addJsFile($params[0]);
		return '';
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function addJSFile($caller, $params, $currentData) {
		$params = explode(',', $params);
		if (!isset($params[0]) && !file_exists(PATH_SITE . $params[0])) {
			return '';
		}

		
		return '<script type="text/javascript" src="'.$GLOBALS['SITECONF']['CONFIG']['baseUrl'] . $params[0].'"></script>';
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function addCSS($caller, $params, $currentData) {
		$params = explode(',', $params);
		$filename = '';
		if (!isset($params[0]) && !file_exists(PATH_SITE . $params[0])) {
			return '';
		}
		headmgr::addCssFile($params[0]);
		return '';
	}


	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function addCSSFile($caller, $params, $currentData) {
		$params = explode(',', $params);
		$filename = '';
		if (!isset($params[0]) && !file_exists(PATH_SITE . $params[0])) {
			return '';
		}

		return '<link href="'.$GLOBALS['SITECONF']['CONFIG']['baseUrl'] . $params[0].'" rel="stylesheet" type="text/css" />';
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function oddEvenTableRow() {
		$bascule = isset($GLOBALS['SITE']['bascule'])? $GLOBALS['SITE']['bascule'] : false;
		if($bascule){
			$content = 'class="x-odd"';
		} else{
			$content = 'class="x-even"';
		}

		$bascule = !$bascule;

		$GLOBALS['SITE']['bascule'] = $bascule;

		return $content;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function addCalendar($caller, $params, $currentData) {
		$params = explode(',', $params);
		if (!isset($params[0]) || !isset($params[1])) {
			return '';
		}

		$lang = isset($GLOBALS['SITECONF']['CONFIG']['lang']) && isset($GLOBALS['LIBCONF']['template']['includeCalendar']['js_lang'][$GLOBALS['SITECONF']['CONFIG']['lang']])? $GLOBALS['SITECONF']['CONFIG']['lang'] : 'fr';

		foreach ($GLOBALS['LIBCONF']['template']['includeCalendar']['css'] as $k => $v) {
			headmgr::addCssFile($v);
		}

		foreach ($GLOBALS['LIBCONF']['template']['includeCalendar']['js'] as $k => $v) {
			headmgr::addJsFile($v);
		}

		headmgr::addJsFile($GLOBALS['LIBCONF']['template']['includeCalendar']['js_lang'][$lang]);

		$content = '<a id="'.$params[1].'" href="#"><img src="'.$GLOBALS['SITECONF']['CONFIG']['baseUrl'].'public/images/cal.png"/></a><script type="text/javascript">
			Calendar.setup({
				inputField : "'.$params[0].'",
				trigger    : "'.$params[1].'",
				onSelect   : function() { this.hide()},
				dateFormat : "%d/%m/%Y"
			});
		</script>';

		return $content;
	}


	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function path($caller, $params, $currentData) {
		$params = explode(',', $params);
		$addPath = isset($params[0])? trim($params[0]) : '';
		
		
		return $GLOBALS['SITECONF']['CONFIG']['baseUrl'] . $addPath;
	}



	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function processDateSubpart_callback($match) {
		/* Declare */
		$dateFormat = $match[1];
		$format = false;
		$date = trim($match[2]);
		$res = '';

		/* Begin */
		if (div::testInt($date)) {
			$date = intval($date);
		} else {
			$date = strtotime($date);
		}

		if (strpos($dateFormat, ':')) {
			list($dateFormat, $format) = explode(':', $dateFormat, 2);
		}

		switch ($dateFormat) {
		case 'strftime':
			$res = strftime($format, $date);
			break;
		case 'date':
			$res = date($format, $date);
			break;
		}

		return $res;
	}

	/**
	 * Magic marker helper func
	 * 
	 * 
	 * @param string $content = the content to browse
	 * @param string $key = the marker starting key (markers have to be ###<key>:<parameter>###
	 * @param mixed $callback = the preg_replace_callback's callback
	 * @access public
	 * @return void 
	 */
	function replaceKeyMarkerBy(&$content, $key, $callback) {
		$mask = '/\#\#\#' . preg_quote($key, '/') . ':(.*?)\#\#\#/i';
		$content = preg_replace_callback($mask, $callback, $content);
	}

	/**
	 * Magic marker helper func
	 * 
	 * 
	 * @param string $content = the content to browse
	 * @param string $key = the subpart starting key (markers have to be ###<key>:<parameter>###
	 * @param mixed $callback = the preg_replace_callback's callback
	 * @access public
	 * @return void 
	 */
	function replaceKeySubpartBy(&$content, $key, $callback) {
		// (<!--xxx)###<key>:<parameter>###(xxx-->)<text>(<!--xxx)###<key>###(xxx-->)
		$mask = '/(?:<!--[^>]*)?\#\#\#' . preg_quote($key, '/') . ':(.*?)\#\#\#(?:[^>]*-->)?((?s).*?)(?:<!--[^>]*)?\#\#\#' . preg_quote($key, '/') . '\#\#\#(?:[^>]*-->)?/i';

		$content = preg_replace_callback($mask, $callback, $content);
	}

}
?>