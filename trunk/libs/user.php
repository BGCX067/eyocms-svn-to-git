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
class user {
	/**
	 * 
	 * @access public
	 * @var string
	 */
	public $code_adherent;

	/**
	 * 
	 * @access public
	 * @var string
	 */
	public $webtod;

	/**
	 * 
	 * @access public
	 * @var string
	 */
	public $informations;

	/**
	 * Constructor
	 * @access public
	 **/
	public function __construct() {
		$this->webtod = new webtodtunnel();
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function init() {
		if (isset($_SESSION['user'])) {
			$this->initUser($_SESSION['user']);
		}
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function initUser($user) {
		$this->code_adherent = $user['informations']['code_adherent'];
		$this->informations = $user['informations'];
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function initUserByCodeAdherent($code) {
		$param = array('code_adherent' => $code);
		$reponse = $this->webtod->getResponseParse('adherent', 'ServiceLireAdherent', $param, 'LireAdherent', 'ServiceLireAdherent_Response.xml');
		if (!$this->userExist($reponse)) {
			$this->logout();
			return false;
		}

		$this->code_adherent = $code;
		$this->informations = $reponse['body']['adherent'];

		$this->informations['autorise_dz'] = div::boolval($this->informations['autorise_dz']);
		$this->informations['autorise_lv'] = div::boolval($this->informations['autorise_lv']);

		$_SESSION['user']['code_adherent'] = $this->code_adherent;
		$_SESSION['user']['informations'] = $this->informations;

		return true;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function authentification($codeAdherent, $no_adherent) {
		//si l'utilisateur est déjà authentifié on stop
		if (!is_null($this->code_adherent)) {
			return true;
		}

		$param = array(
			'code_adherent' => $codeAdherent,
			'no_adherent' => $no_adherent,
		);

		$reponse = $this->webtod->getResponseParse('adherent', 'ServiceAuthentifierAdherent', $param, 'AuthentifierAdherent', 'ServiceAuthentifierAdherent_Response.xml');

		if ($this->userExist($reponse)) {
			return $this->initUserByCodeAdherent($codeAdherent);
		} else {
			return false;
		}
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function userExist($reponse) {
		$userExist = false;

		if (!isset($reponse['header']['statut'])) {
			return false;
		}

		foreach ($reponse['header']['statut'] as $k => $v) {
			if ($v['code'] == '+02F01001') {
				$userExist = true;
				break;
			}
		}

		return $userExist;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function logout() {
		if (isset($_SESSION['user'])) {
			unset($_SESSION['user']);
		}
		$this->code_adherent = null;
		$this->informations = null;
	}
}
?>