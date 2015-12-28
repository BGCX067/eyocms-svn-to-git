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

if (!defined('PATH_THISSCRIPT')) 	
	define('PATH_THISSCRIPT',str_replace('//','/', str_replace('\\','/', (php_sapi_name()=='cgi'||php_sapi_name()=='isapi' ||php_sapi_name()=='cgi-fcgi')&&((isset($_SERVER['ORIG_PATH_TRANSLATED']) && $_SERVER['ORIG_PATH_TRANSLATED'])?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED'])? ((isset($_SERVER['ORIG_PATH_TRANSLATED']) && $_SERVER['ORIG_PATH_TRANSLATED'])?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED']):((isset($_SERVER['ORIG_SCRIPT_FILENAME']) && $_SERVER['ORIG_SCRIPT_FILENAME'])?$_SERVER['ORIG_SCRIPT_FILENAME']:$_SERVER['SCRIPT_FILENAME']))));

if (!defined('PATH_SITE')) 			define('PATH_SITE', dirname(PATH_THISSCRIPT).'/');

define('PATH_MODULE', PATH_SITE.'modules/');
define('PATH_TEMPLATE', PATH_MODULE.'templates/');
define('PATH_PUBLIC', PATH_SITE.'public/');
define('PATH_LIBS', PATH_SITE.'libs/');
define('PATH_DEFAULT_MODULE', PATH_MODULE.'default/');
define('PATH_CACHE', PATH_SITE.'cache/');


//if (version_compare(phpversion(), '5.2', '<'))	die ('TYPO3 requires PHP 5.2.0 or higher.');

//inclusion des conf des modules
if (file_exists(PATH_MODULE.'config.php')) {
	include_once(PATH_MODULE.'config.php');
}

//création de la config de base du site
if (file_exists(PATH_SITE.'config.php')) {
	include_once(PATH_SITE.'config.php');
} else {
	file_put_contents(PATH_SITE.'config.php', file_get_contents(PATH_LIBS.'config_base.php'));
	include_once(PATH_SITE.'config.php');
}

include_once(PATH_LIBS.'div.php');
include_once(PATH_LIBS.'front.php');

$front = new front();
$front->initUser();

echo $front->getContent();
?>