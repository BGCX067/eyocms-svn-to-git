<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Eyolas <dtouzet@gmail.com>
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


/** 
 * 
 *
 */
class headmgr {
	private static $afterInclude = array();
	
	private static $beforeInclude = array();
	
	public static function addHeaderAfterInclude($add) {
		if (is_array($add)) {
			self::$afterInclude = array_merge(self::$afterInclude ,$add);
		} else {
			self::$afterInclude[] = $add;
		}
	}
	
	public static function addHeaderBeforeInclude($add) {
		if (is_array($add)) {
			self::$beforeInclude = array_merge(self::$beforeInclude ,$add);
		} else {
			self::$beforeInclude[] = $add;
		}
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function addJsFile($path) {
		if (!file_exists(PATH_SITE . $path)) {
			return;
		}

		preg_match('/.*\/([^\/.]*).([^?]*)/', $path, $matches);
		if ($matches[2] != 'js') {
			return;
		}

		$name = $matches[1];

		if (!isset($GLOBALS['SITECONF']['HEADER']['js'][$name])) {
			$GLOBALS['SITECONF']['HEADER']['js'][$name] = $path;
		}
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function addCssFile($path) {
		if (!file_exists(PATH_SITE . $path)) {
			return;
		}
		preg_match('/.*\/([^\/.]*).([^?]*)/', $path, $matches);

		if ($matches[2] != 'css') {
			return;
		}

		$name = $matches[1];

		if (!isset($GLOBALS['SITECONF']['HEADER']['css'][$name])) {
			$GLOBALS['SITECONF']['HEADER']['css'][$name] = $path;
		}
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public static function getHeader(&$page) {
		$header = '';		

		$contentType = isset($page['contentType'])? $page['contentType'] : $GLOBALS['SITECONF']['CONFIG']['contentType'];
		$meta = array(0 => '<meta http-equiv="Content-Type" content="'.$contentType.'" />');
		
		if (!is_null($page)) {
			$header = '<title>'. $page->titlePage.'</title>'. chr(10);
			$meta = array_merge($meta, $page->getMetaData);
		}
		
		
		
		$meta[] = '<meta name="robots" content="'.$GLOBALS['SITECONF']['CONFIG']['robots'].'" />';
		$meta[] = '<meta name="Copyright" content="'.$GLOBALS['SITECONF']['CONFIG']['Copyright'].'" />';
		
		foreach  ($meta as $v) {
			$header .= $v . chr(10);
		}
					
		
		foreach (self::$beforeInclude as $v){
			$header .= $v .  chr(10);
		}

		foreach ($GLOBALS['SITECONF']['HEADER']['css'] as $v) {
			$header .= '<link href="'.$GLOBALS['SITECONF']['CONFIG']['baseUrl'] . $v.'" rel="stylesheet" type="text/css" />'. chr(10);
		}

		foreach ($GLOBALS['SITECONF']['HEADER']['js'] as $v) {
			$header .= '<script type="text/javascript" src="'.$GLOBALS['SITECONF']['CONFIG']['baseUrl'] . $v.'"></script>'. chr(10);
		}
		
		foreach (self::$afterInclude as $v){
			$header .= $v .  chr(10);
		}

		return $header;
	}
}
?>