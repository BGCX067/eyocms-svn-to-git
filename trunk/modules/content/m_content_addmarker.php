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
class m_content_addmarker extends base{

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

	function getBonjourContent($ref) {
		$this->init($ref);

		if (is_null($GLOBALS['front']->user->code_adherent) || 	$ref[1] == 'error404' || ($this->type == 'declic' && !$GLOBALS['front']->user->informations['autorise_lv']) || ($this->type == 'pixel' && !$GLOBALS['front']->user->informations['autorise_dz'])) {
			return '';
		}

		$bonjour = array(
			'firstname' => ucfirst(strtolower($GLOBALS['front']->user->informations['nom_adherent'])),
			'name' => ucfirst(strtolower($GLOBALS['front']->user->informations['prenom_adherent'])),
		);


		return $this->template->nestedMarkerArray($bonjour, 'BONJOUR_PART');
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getTypeContent($ref) {
		return $ref[2]=='declic'? 'déclic' : $ref[2];
	}
}
?>