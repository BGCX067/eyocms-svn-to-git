<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Touzet David <dtouzet@gmail.com>
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
 * <Class description>
 *
 * @author Touzet David <dtouzet@gmail.com>
 */

final class div {

	/**
	 * Tests if the input is an integer.
	 *
	 * @param	mixed		Any input variable to test.
	 * @return	boolean		Returns true if string is an integer
	 */
	public static function testInt($var)	{
		return !strcmp($var,intval($var));
	}


	/**
	 * Makes debug output
	 * Prints $var in bold between two vertical lines
	 * If not $var the word 'debug' is printed
	 * If $var is an array, the array is printed by t3lib_div::print_array()
	 *
	 * @param	mixed		Variable to print
	 * @param	mixed		If the parameter is a string it will be used as header. Otherwise number of break tags to apply after (positive integer) or before (negative integer) the output.
	 * @return	void
	 */
	public static function debug($var='',$brOrHeader=0)	{
			// buffer the output of debug if no buffering started before
		if (ob_get_level()==0) {
			ob_start();
		}

		if ($brOrHeader && !div::testInt($brOrHeader))	{
			echo '<table class="debug" border="0" cellpadding="0" cellspacing="0" bgcolor="white" style="border:0px; margin-top:3px; margin-bottom:3px;"><tr><td style="background-color:#bbbbbb; font-family: verdana,arial; font-weight: bold; font-size: 10px;">'.htmlspecialchars((string)$brOrHeader).'</td></tr><tr><td>';
		} elseif ($brOrHeader<0)	{
			for($a=0;$a<abs(intval($brOrHeader));$a++){echo '<br />';}
		}

		if (is_array($var))	{
			div::print_array($var);
		} elseif (is_object($var))	{
			echo '<b>|Object:<pre>';
			print_r($var);
			echo '</pre>|</b>';
		} elseif ((string)$var!='')	{
			echo '<b>|'.htmlspecialchars((string)$var).'|</b>';
		} else {
			echo '<b>| debug |</b>';
		}

		if ($brOrHeader && !div::testInt($brOrHeader))	{
			echo '</td></tr></table>';
		} elseif ($brOrHeader>0)	{
			for($a=0;$a<intval($brOrHeader);$a++){echo '<br />';}
		}
	}

	/**
	 * Prints an array
	 *
	 * @param	mixed		Array to print visually (in a table).
	 * @return	void
	 * @see view_array()
	 */
	public static function print_array($array_in)	{
		echo div::view_array($array_in);
	}


	/**
	 * Returns HTML-code, which is a visual representation of a multidimensional array
	 * use div::print_array() in order to print an array
	 * Returns false if $array_in is not an array
	 *
	 * @param	mixed		Array to view
	 * @return	string		HTML output
	 */
	public static function view_array($array_in)	{
		if (is_array($array_in))	{
			$result='
			<table border="1" cellpadding="1" cellspacing="0" bgcolor="white">';
			if (count($array_in) == 0)	{
				$result.= '<tr><td><font face="Verdana,Arial" size="1"><b>EMPTY!</b></font></td></tr>';
			} else	{
				foreach ($array_in as $key => $val)	{
					$result.= '<tr>
						<td valign="top"><font face="Verdana,Arial" size="1">'.htmlspecialchars((string)$key).'</font></td>
						<td>';
					if (is_array($val))	{
						$result.=div::view_array($val);
					} elseif (is_object($val))	{
						$string = get_class($val);
						if (method_exists($val, '__toString'))	{
							$string .= ': '.(string)$val;
						}
						$result .= '<font face="Verdana,Arial" size="1" color="red">'.nl2br(htmlspecialchars($string)).'<br /></font>';
					} else	{
						if (gettype($val) == 'object')	{
							$string = 'Unknown object';
						} else	{
							$string = (string)$val;
						}
						$result.= '<font face="Verdana,Arial" size="1" color="red">'.nl2br(htmlspecialchars($string)).'<br /></font>';
					}
					$result.= '</td>
					</tr>';
				}
			}
			$result.= '</table>';
		} else	{
			$result  = '<table border="1" cellpadding="1" cellspacing="0" bgcolor="white">
				<tr>
					<td><font face="Verdana,Arial" size="1" color="red">'.nl2br(htmlspecialchars((string)$array_in)).'<br /></font></td>
				</tr>
			</table>';	// Output it as a string.
		}
		return $result;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function dirname($path) {
		return preg_replace('/(^|\/)[^\/]*$/', '\1', $path);
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function resolves($path) {
		return preg_replace('/(^|\/)[^\/]+\/..\//', '\1', $path);;
	}

	/**
	 * Returns true if the first part of $str matches the string $partStr
	 *
	 * @param	string		Full string to check
	 * @param	string		Reference string which must be found as the "first part" of the full string
	 * @return	boolean		True if $partStr was found to be equal to the first part of $str
	 */
	public static function isFirstPartOfStr($str,$partStr)	{
		// Returns true, if the first part of a $str equals $partStr and $partStr is not ''
		$psLen = strlen($partStr);
		if ($psLen)	{
			return substr($str,0,$psLen)==(string)$partStr;
		} else return false;
	}

	/**
	 * Explodes a string and trims all values for whitespace in the ends.
	 * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
	 *
	 * @param	string		Delimiter string to explode with
	 * @param	string		The string to explode
	 * @param	boolean		If set, all empty values (='') will NOT be set in output
	 * @return	array		Exploded values
	 */
	public static function trimExplode($delim, $string, $onlyNonEmptyValues=0)	{
		$array = explode($delim, $string);
			// for two perfomance reasons the loop is duplicated
			//  a) avoid check for $onlyNonEmptyValues in foreach loop
			//  b) avoid unnecessary code when $onlyNonEmptyValues is not set
		if ($onlyNonEmptyValues) {
			$new_array = array();
			foreach($array as $value) {
				$value = trim($value);
				if ($value != '') {
					$new_array[] = $value;
				}
			}
				// direct return for perfomance reasons
			return $new_array;
		}

		foreach($array as &$value) {
			$value = trim($value);
		}

		return $array;
	}

	/**
	 * Implodes a multidim-array into GET-parameters (eg. &param[key][key2]=value2&param[key][key3]=value3)
	 *
	 * @param	string		Name prefix for entries. Set to blank if you wish none.
	 * @param	array		The (multidim) array to implode
	 * @param	string		(keep blank)
	 * @param	boolean		If set, parameters which were blank strings would be removed.
	 * @param	boolean		If set, the param name itself (for example "param[key][key2]") would be rawurlencoded as well.
	 * @return	string		Imploded result, fx. &param[key][key2]=value2&param[key][key3]=value3
	 * @see explodeUrl2Array()
	 */
	public static function implodeArrayForUrl($name,array $theArray,$str='',$skipBlank=0,$rawurlencodeParamName=0)	{
		foreach($theArray as $Akey => $AVal)	{
			$thisKeyName = $name ? $name.'['.$Akey.']' : $Akey;
			if (is_array($AVal))	{
				$str = div::implodeArrayForUrl($thisKeyName,$AVal,$str,$skipBlank,$rawurlencodeParamName);
			} else {
				if (!$skipBlank || strcmp($AVal,''))	{
					$str.='&'.($rawurlencodeParamName ? rawurlencode($thisKeyName) : $thisKeyName).
						'='.rawurlencode($AVal);
				}
			}
		}
		return $str;
	}

	/**
	 * Test if the module exist
	 * 
	 * @param	string		Name module
	 * @param	array		Type of module
	 * @return bool 
	 */
	public static function moduleExist($module, $type = '') {
		$path = PATH_MODULE;

		if ($type) {
			$path .= $type . '/';
		}

		$path .= $module.'/';
		$className = 'm_'.$module;
		return $module && is_dir($path) && file_exists($path.$className.'.php');
	}

	/**
	 * make a link
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function link($module, $addParams = array(), $type = '', $urlRewrite = null) {

		if (is_null($urlRewrite) || !is_bool($urlRewrite)) {
			$urlRewrite = $GLOBALS['SITECONF']['CONFIG']['urlRewrite'];
		}

		if (!$urlRewrite) {
			$addParams['module'] = $module;
			if ($type) {
				$addParams['type'] = $type;
			}
		}

		if (is_array($addParams)) {
			$addParams = div::implodeArrayForUrl('', $addParams, '', true);
			if ($addParams) {
				$addParams = '?' . substr($addParams, 1);
			}
		}

		$url = $GLOBALS['SITECONF']['CONFIG']['baseUrl'] . 'index.php';

		if ($urlRewrite) {
			if ($type) {
				$url .= '/'.$type;
			}
			if ($module) {
				$url .= '/'.$module.'/';	
			}
		}

		$url .= $addParams;

		return $url;
	}

	/**
	 * Explode string parameter (ex :&param[key][key2]=value2&param[key][key3]=value3)) for use div::link
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function explodeStringForLink($str, $value_separator = '=', $field_separator = '&') {
		$arr = array();
		foreach (explode($field_separator, $str) as $f) {
			list($k,$v) = explode($value_separator, $f);
			$arr[$k] = $v;
		}
		return $arr;
	}

	/**
	 * return boolval of string
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function boolval($val) {
		return $val == 'false' ? false : ($val == 'true'? true : $val);
	}

	/**
	 * return string
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function boolstr($val) {
		return is_bool($val)? ($val? 'true' : 'false') : $val;
	}


	/**
	 * return json var
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function to_json($data) {
		if (is_string($data)) {
			return '"' . addslashes($data) . '"';
		} elseif (is_bool($data)) {
			return $data ? 'true' : 'false';
		} elseif (is_array($data)) {
			$keys = array_keys($data);
			$is_hash = array_keys($data) !== range(0,count($data)-1);
			$result_strings = array();
			foreach ($data as $key => $value) {
					$result_strings[] = $is_hash
							? self::to_json($key) . ': ' . self::to_json($value)
							: self::to_json($value);
			}
			return $is_hash
					? '{' . implode(', ', $result_strings) . '}'
					: '[' . implode(', ', $result_strings) . ']';
		} elseif (is_object($data)) {
			return self::to_json(get_object_vars($data));
		} elseif (is_numeric($data)) {
			return $data;
		} else {
			return self::to_json("$data");
		}
	}


	/**
	 * Transform a "String user func" into it's callable form (means, usable in call_user_func)
	 * 
	 * @param string $userFunc = String user func
	 * @param object $ref = 
	 * @access public
	 * @return array (callable user func) 
	 */
	public static function getCallableFromStringUserFunc($userFunc, &$ref = null) {
		/* Declare */
		$callable = array();
	
		/* Begin */
		preg_match('/^(&?)(.*?)((?:::)|(?:->))(.*)$/', $userFunc, $infos);
		$singleton = $infos[1];
		$class     = $infos[2];
		$mode      = $infos[3];
		$method    = $infos[4];
	
		if ($mode == '::') { // Static call
			$callable[] = $class;
		} else {
			if ($class == 'this' && is_object($ref)) {
				$callable[] = &$ref;
			} else {
				$callable[] = new $class;
			}
		}

		$callable[] = $method;
		
		return $callable;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function callUserFunc($userFunc, $params, &$ref = null) {
		return call_user_func(div::getCallableFromStringUserFunc($userFunc, $ref), $params, $ref);
	}


	/**
	 * Formats the input integer $sizeInBytes as bytes/kilobytes/megabytes (-/K/M)
	 *
	 * @param	integer		Number of bytes to format.
	 * @param	string		Labels for bytes, kilo, mega and giga separated by vertical bar (|) and possibly encapsulated in "". Eg: " | K| M| G" (which is the default value)
	 * @return	string		Formatted representation of the byte number, for output.
	 */
	public static function formatSize($sizeInBytes,$labels='')	{

			// Set labels:
		if (strlen($labels) == 0) {
			$labels = ' | K| M| G';
		} else {
			$labels = str_replace('"','',$labels);
		}
		$labelArr = explode('|',$labels);

			// Find size:
		if ($sizeInBytes>900)	{
			if ($sizeInBytes>900000000)	{	// GB
				$val = $sizeInBytes/(1024*1024*1024);
				return number_format($val, (($val<20)?1:0), '.', '').$labelArr[3];
			}
			elseif ($sizeInBytes>900000)	{	// MB
				$val = $sizeInBytes/(1024*1024);
				return number_format($val, (($val<20)?1:0), '.', '').$labelArr[2];
			} else {	// KB
				$val = $sizeInBytes/(1024);
				return number_format($val, (($val<20)?1:0), '.', '').$labelArr[1];
			}
		} else {	// Bytes
			return $sizeInBytes.$labelArr[0];
		}
	}

	/**
	 * Dynamic class load
	 * 
	 * @param string $className = the class to load
	 * @access static
	 * @return bool = true if class exists after processing
	 */
	public static function dynClassLoad($className) {
		/* Declare */
		$lowerdClass = strtolower($className);
		$fileName = null;

		/* Begin */
		if (class_exists($className, false)) {
			// Nothing to do
			return true;
		} elseif (substr($className, 0, 2) == 'm_') {
			$ex = explode('_', $className);
			$fileName = PATH_MODULE . $ex[1] . '/' . $className . '.php';
			if (!file_exists(fileName)) {
				$fileName = PATH_LIBS . 'modules/' . $ex[1] . '/' . $className . '.php';
			}
		} elseif (is_dir(PATH_LIBS .$className)) {
			$fileName = PATH_LIBS . $className . '/' . $className . '.php';
		} else {
			$fileName = PATH_LIBS . $className .'.php';
		}

		if (!is_null($fileName) && file_exists($fileName)) {
			require_once($fileName);
		}

		return class_exists($className, false);
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function redirectAuth($module, $type) {
		if ($module == 'authentification') {
			$autentification = true;
		} else {
			$autentification = isset($GLOBALS['MODULECONF']['auth'][$type][$module])? $GLOBALS['MODULECONF']['auth'][$type][$module] : false;	
		}

		if ($autentification) {
			$redirectAuth = self::redirectAuthWebTod($module, $type);
		} else {
			$redirectAuth = false;
		}

		return $redirectAuth;
	}

	public static function redirectAuthWebTod($module, $type){
		return $module == 'authentification' || is_null($GLOBALS['front']->user->code_adherent) || ($type == 'declic' && !$GLOBALS['front']->user->informations['autorise_lv']) || ($type == 'pixel' && !$GLOBALS['front']->user->informations['autorise_dz']);
	}

}
?>