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
class front {

	/**
	 * 
	 * @access public
	 * @var string
	 */
	protected $template;

	/**
	 * 
	 * @access public
	 * @var string
	 */
	public $user;

	/**
	 * 
	 * @access public
	 * @var string
	 */
	public $type;

	/**
	 * 
	 * @access public
	 * @var string
	 */
	public $module;
	
	
	/**
	 * 
	 * @access public
	 * @var string
	 */
	public $db;

	/**
	 * 
	 * @access public
	 * @var string
	 */
	protected $markerArray;

	public function __construct() {
		session_start();
		$this->load();
		$this->validConfigBase();
		
		headmgr::addHeaderBeforeInclude('<!--' . chr(10) . chr(9) .'This website is powered by EYOCMS - inspiring people to share!'. chr(10) . chr(9) .'EYOCMS is a free open source Content Management Framework initially created by Touzet David and licensed under GNU/GPL.'. chr(10) . chr(9) .'EYOCMS is copyright 2010-2011 of Touzet David.'. chr(10) . chr(9) .'Extensions are copyright of their respective owners.'. chr(10) . '-->');
		
		$this->db = new db;
		$this->db->connect($GLOBALS['SITECONF']['DBCONF']['SERNAME'], $GLOBALS['SITECONF']['DBCONF']['username'], $GLOBALS['SITECONF']['DBCONF']['password'], $GLOBALS['SITECONF']['DBCONF']['db']);

		$this->template =  &template::getInstance($this);//new template();

		$this->vars = array_merge_recursive($_POST, $_GET);

		if (file_exists(PATH_TEMPLATE . 'index.html')) {
			$templatePath = PATH_TEMPLATE . 'index.html';
		} else {
			$templatePath = PATH_MODULE.'index.html';
		}


		$this->template->initByFile($templatePath);
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	protected function load() {
		include_once(PATH_LIBS.'config.php');
		foreach ($GLOBALS['LIB_REGISTER'] as $k => $v) {
			include_once($v);
		}
	}
	
	
	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	protected function validConfigBase() {
		if (!isset($GLOBALS['SITECONF']['DBCONF']['SERNAME']) || !isset($GLOBALS['SITECONF']['DBCONF']['username']) || !isset($GLOBALS['SITECONF']['DBCONF']['password']) || !isset($GLOBALS['SITECONF']['DBCONF']['db'])) {
			//@todo redirect install
		}
	}



	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function getContent() {
		$markerArray = $this->getmarkerArray();

		return $this->template->nestedMarkerArray($markerArray, 'DEFAULT_PART');
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	protected function getmarkerArray() {
		$content = '';
		$title = '';

		//pour le cas d'une réécriture d'url
		if (isset($_SERVER['PATH_INFO']) && trim($_SERVER['PATH_INFO'])) {
			$info = explode('/', trim($_SERVER['PATH_INFO']));
			if (trim($_SERVER['PATH_INFO']) != '/') {
				if (isset($info[1]) && trim($info[1]) && ($info[1] == 'declic' || $info[1] == 'pixel')) {
					$type = trim($info[1]);
					$module = isset($info[2]) && trim($info[2])? trim($info[2]) : '';
				} else {
					$type = '';
					$module = isset($info[1]) && trim($info[1])? trim($info[1]) : '';
				}
			} else {
				$module = $GLOBALS['SITECONF']['CONFIG']['defaultModule'];
				$type = $GLOBALS['SITECONF']['CONFIG']['defaultType'];
			}	

		} elseif (isset($_GET['module']) && trim($_GET['module'])) {
			$module = trim($_GET['module']);
			$type = isset($_GET['type']) && trim($_GET['type'])? trim($_GET['type']) : '';
		} else {
			$module = $GLOBALS['SITECONF']['CONFIG']['defaultModule'];
			$type = $GLOBALS['SITECONF']['CONFIG']['defaultType'];
		}

		if (!div::moduleExist($module, $type)) {
			$module = 'error404';
			$type = '';
		}

		if (div::redirectAuth($module, $type)) {
			$redirect = array('type' => $type, 'module' => $module == 'authentification' ? $GLOBALS['SITECONF']['CONFIG']['defaultModule'] : $module);
			$module = 'authentification';

			$path = PATH_MODULE;
			$path .= $module.'/';

			$className = 'm_'.$module;

			require_once($path.$className.'.php');
			$class = new $className('', $module);
			$content = $class->main($redirect);
			$title = $class->titlePage;
			
		} else {
			$path = PATH_MODULE;

			if ($type) {
				$path .= $type.'/';
			}

			$path .= $module.'/';

			$className = 'm_'.$module;


			require_once($path.$className.'.php');
			$class = new $className($type, $module);
			$content = $class->main();
			$title = $class->titlePage;
		}

		$currentType = $type? $type : (isset($class->type)? $class->type : '');
		

		$markerArray = array(
			'title' => $title,
			'content' => $content,
			'header' => headmgr::getHeader(),
		);


		if (isset($GLOBALS['CONF']['addMarker'])) {
			foreach ($GLOBALS['CONF']['addMarker'] as $k => $v) {
				$markerArray[$k] = div::callUserFunc($v, array($type, $module, $currentType), $this);
			}
		}

		return $markerArray;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function initUser() {
		$this->user = new user();
		$this->user->init();

		if (isset($this->vars['deconnexion']) && $this->vars['deconnexion'] && !is_null($this->user->code_adherent)) {
				$this->user->logout();
		} elseif (isset($this->vars['connexion']) && $this->vars['connexion']) {
			$code_adherent = isset($this->vars['code_adherent'])? trim($this->vars['code_adherent']) : '';
			$no_adherent = isset($this->vars['no_adherent'])? trim($this->vars['no_adherent']) : '';
			$lol = $this->user->authentification($code_adherent, $no_adherent);
		}
	}
}
?>