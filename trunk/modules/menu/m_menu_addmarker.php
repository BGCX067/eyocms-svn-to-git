<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Eyolas <dtouzet@gmail.com>
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
 * <Class description>
 *
 * @author Touzet David <dtouzet@gmail.com>
 * @subpackage <EXTKEY>
 */


/** 
 * 
 *
 */
class m_menu_addmarker extends base{

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function init($ref) {
		$this->type = $ref[0];
		$this->module = $ref[1];
		parent::init();
	}
	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function main($ref) {
		$this->init($ref);

		if (is_null($GLOBALS['front']->user->code_adherent) || ($this->type == 'declic' && !$GLOBALS['front']->user->informations['autorise_lv']) || ($this->type == 'pixel' && !$GLOBALS['front']->user->informations['autorise_dz'])) {
			return '';
		}

		$content = $this->getContent();
		return $content;

		
	}

	function getContent() {
		$menu = array();
		if (isset($GLOBALS['MODULECONF']['menu'][$this->type])) {
			foreach ($GLOBALS['MODULECONF']['menu'][$this->type] as $k => $v) {
				$link = div::link(isset($v[0])? $v[0] : '', isset($v[2])? div::explodeStringForLink($v[2]) : array(), $v[1]? $v[1] : '');
				$menu[] = array(
					'NAME' => $k, 
					'LIEN' => $link,
					'notlast?' => true,
					'link_selected?' => ($v[0] == $this->module && $v[1] == $this->type && !isset($v[2]))? true : false,
				);
			}

			end($menu);
			$menu[key($menu)]['notlast?'] = false;
		}

		$marker = array('menu'=> $menu);

		return $this->template->nestedMarkerArray($marker, 'MENU_PART');
	}
}
?>