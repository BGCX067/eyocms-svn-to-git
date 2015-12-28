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


/** 
 * 
 *
 */
class base {
	public $template;
	public $config;
	public $titlePage;
	public $module;
	public $vars;
	public $type;


	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function __construct($type = '', $module = '') {
		$this->type = $type;
		$this->module = $module;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function init() {
		$this->loadConfig();

		$this->vars = array_merge_recursive($_POST, $_GET);

		$this->template = &template::getInstance($this);

		$this->template->initByFile($this->config['template']);
	}


	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	protected function loadConfig() {
		$pathTemplate =  PATH_MODULE;
		$pathAltTemplate = PATH_TEMPLATE;
		$nameClass = get_class($this);
		$ex = explode('_', $nameClass);

		if (!isset($ex[2])) {
			if ($this->type) {
				$pathTemplate .= $this->type . '/';
				$pathAltTemplate .= $this->type . '.';
			}

			$pathAltTemplate .= $this->module . '.tmpl';

			//si il y a une surcharge existante du template alors on la prends
			if (file_exists($pathAltTemplate)) {
				$pathTemplate = $pathAltTemplate;
			} else {
				$pathTemplate .= $this->module . '/template.tmpl';
			}
		} else {
			$namefile = substr(get_class($this), 2) . '.tmpl';
			$pathAltTemplate .= $namefile;

			if (file_exists($pathAltTemplate)) {
				$pathTemplate = $pathAltTemplate;
			} else {
				$pathTemplate .= $ex[1] . '/' . $namefile;
			}
		}

		$this->config = array(
			'template' => $pathTemplate,
		);
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function link($addParams = array(), $urlRewrite = null, $module = null, $type = null) {
		if (is_null($module)) {
			$module = $this->module;
		}

		if (is_null($type)) {
			$type = $this->type;
		}
		return div::link($module, $addParams, $type, $urlRewrite);
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function link_vars($addParams = array(), $urlRewrite = null, $module = null, $type = null) {
		$addParams += $this->vars;
		return $this->link($addParams, $urlRewrite, $module, $type);
	}
}
?>