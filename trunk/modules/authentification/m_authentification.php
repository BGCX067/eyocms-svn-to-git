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
class m_authentification extends base{
	var $redirec;

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function main($redirect = array()) {
		$this->init();
		$this->redirect = $redirect;

		$mode = $this->getMode();

		$content = $this->getContent($mode);
		return $content;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getContent($mode) {
		$content = '';
		switch ($mode) {
		case 'choice':
			$content = $this->getContentChoice();
			break;
		case 'formlogout':
			$content = $this->getContentLogout();
			break;
		case 'processlogout':
			$content = $this->getContentprocesslogout();
			break;
		case 'processlogin':
			$content = $this->getContentprocesslogin();
			break;
		case 'formlogin':
		default:
			$content = $this->getContentLogin();
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
	function getMode() {
		$mode = 'choice';

		//if (isset($this->vars['formlogin'])) {
			$mode = 'formlogin';
			/*if (isset($this->vars['deconnexion']) && $this->vars['deconnexion'] && !is_null($GLOBALS['front']->user->code_adherent)) {
				$mode = 'processlogout';
			} elseif (!is_null($GLOBALS['front']->user->code_adherent)) {
				$mode = 'formlogout';
			} elseif (isset($this->vars['connexion']) && $this->vars['connexion']) {
				$mode = 'processlogin';
			}*/
		//} elseif (isset($this->vars['formadhesion'])) {
			//@todo
		/*} elseif (isset($this->vars['deconnexion']) && $this->vars['deconnexion'] && !is_null($GLOBALS['front']->user->code_adherent)) {
			$mode = 'processlogout';
		}*/
		

		return $mode;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getContentChoice() {
		$this->titlePage = 'Connexion';
		$marker = array(
			'login' => $this->link(array('formlogin' => true)),
			'adhesion' => $this->link(array('formadhesion' => true))
		);

		return $this->template->nestedMarkerArray($marker, 'CHOICE_PART');
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getContentprocesslogin() {
		
		$code_adherent = isset($this->vars['code_adherent'])? trim($this->vars['code_adherent']) : '';
		$no_adherent = isset($this->vars['no_adherent'])? trim($this->vars['no_adherent']) : '';


		if (!$code_adherent || !$no_adherent) {
			return $this->getContentLogin();
		}

		if ($GLOBALS['front']->user->authentification($code_adherent, $no_adherent)) {
			return $this->getContentLogout();
		} else {
			return $this->getContentLogin();
		}
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getContentprocesslogout() {
		$GLOBALS['front']->user->logout();

		return $this->getContentLogin();
	}


	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getContentLogin() {
		$this->titlePage = 'Connexion';

		$marker = array('url_login' => div::link($this->redirect['module'], array(), $this->redirect['type']));

		return $this->template->nestedMarkerArray($marker, 'LOGIN_PART');
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getContentLogout() {

		$this->titlePage = 'Déconnexion';

		$marker = array('url_logout' => $this->link());

		return $this->template->nestedMarkerArray($marker, 'LOGOUT_PART');

		
	}
}
?>