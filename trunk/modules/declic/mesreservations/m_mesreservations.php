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
class m_mesreservations extends base{
	var $webtod;
	var $reponse = '';
	var $reservation;
	var $arrets;


	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function init() {
		parent::init();
		$this->webtod = new webtodtunnel();
		$this->titlePage = 'Le Réseau';

		//création du tableau des réservation
		$param = array('code_adherent' => $GLOBALS['front']->user->code_adherent);
		$reponse = $this->webtod->getResponseParse('reservation', 'ServiceListerReservationsParAdherent', $param, 'reservation', 'ServiceListerReservationsParAdherent_Response.xml');
		$this->makeArrayReservation($reponse);

		//création du tableau des arrets
		$param = array('avec_arretslv' => true, 'avec_arretsdz' => false, 'accesreservation' => true);
		$reponse = $this->webtod->getResponseParse('reseau', 'ServiceListerArrets', $param, 'reseau', 'ServiceListerArrets_Response_LV.xml');
		$this->makeArrayArrets($reponse);	
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function main() {
		$this->init();
		$mode =  $this->getMode();

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
	function makeArrayReservation($reponse) {
		$this->reservation = array();

		if (!isset($reponse['body'])) {
			return;
		}

		foreach ($reponse['body'] as $k => $v) {
			$key = explode('[', $k);
			if ($key[0] != 'reservation') {
				continue;
			}

			if (!isset($v['plage_horaire'])) {
				$this->reservation[] = $v;	
			}
		}
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function makeArrayArrets($reponse) {
		$this->arrets = array();

		if (!isset($reponse['body'])) {
			return;
		}

		foreach ($reponse['body'] as $k => $v) {
			$key = explode('[', $k);
			if ($key[0] != 'arret') {
				continue;
			}

			$this->arrets[$v['code_arret']] = $v;
		}
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getMode() {
		$mode = 'list';
		$issetParam = isset($this->vars['depart']) && isset($this->vars['arrivee']) && isset($this->vars['horaire']);

		if (isset($this->vars['confirmer_annulation']) && $issetParam) {
			$mode = 'annuler';
		} elseif (isset($this->vars['confirmer_modification']) && $issetParam) {
			$mode = 'modifier';
		} elseif (isset($this->vars['action']) && $this->vars['action'] == 'modifier' && $issetParam) {
			$mode = 'formmodifier';

		} elseif (isset($this->vars['action']) && $this->vars['action'] == 'annuler' && $issetParam) {
			$mode = 'formannuler';
		} 

		return $mode;
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
		case 'list':
			$content = $this->getContentReservation();
			break;
		case 'formannuler':
			$content = $this->getContentformannuler();
			break;
		case 'formmodifier':
			$content = $this->getContentformmodifier();
			break;
		case 'annuler':
			$message = $this->annulerReservation();
			$content = $this->getContentReservation($message);
			break;
		case 'modifier':
			$message = $this->checkForm();
			if ($message) {
				$content = $this->getContentformmodifier($message);
			} else {
				$message = $this->modifierReservation();
				$content = $this->getContentReservation($message);
			}
			break;
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
	function annulerReservation() {

		$param = array(
			'code_adherent' => $GLOBALS['front']->user->code_adherent, 
			'code_arret' => array(0 => $this->vars['depart'], 1 => $this->vars['arrivee']), 
			'horaire' => $this->vars['horaire']
		);


		$reponse = $this->webtod->getResponseParse('reservation', 'ServiceAnnulerReservation', $param, 'reservation', 'ServiceAnnulerReservation_Response.xml');

		$message = 'Echec de l\'annulation de votre réservation';

		foreach ($reponse['header']['statut'] as $k => $v) {

			if ($v['code'] == '+10D01000' || $v['code'] == '-10D01000' || $v['code'] == '-10D01020' || $v['code'] == '-10D01103') {
				$message = htmlspecialchars($v['value']);
				break;
			}
		}		

		return $message;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function modifierReservation() {
		$param = array(
			'code_adherent' => $GLOBALS['front']->user->code_adherent, 
			'code_arret' => array(0 => $this->vars['depart'], 1 => $this->vars['arrivee']), 
			'horaire' => $this->vars['horaire'],
			'nb_personnes' => $this->vars['nb_personnes'],
		);


		$reponse = $this->webtod->getResponseParse('reservation', 'ServiceModifierReservation', $param, 'reservation', 'ServiceModifierReservation_Response.xml');

		$message = 'Echec de la modification de votre réservation';

		foreach ($reponse['header']['statut'] as $k => $v) {

			if ($v['code'] == '+10U01000' || $v['code'] == '-00X00000' || $v['code'] == '-10F01001' || $v['code'] == '-10U01000' || $v['code'] == '-10C01030' || $v['code'] == '-10C01031' || $v['code'] == '-10C01032' || $v['code'] == '-10C01033' || $v['code'] == '-10U01020' || $v['code'] == '-10U01103'){
				$message = htmlspecialchars($v['value']);
				break;
			}
		}

		return $message;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getContentformannuler() {
		$reservation = $this->getReservation($this->vars['depart'], $this->vars['arrivee'], $this->vars['horaire']);

		if (!count($reservation)) {
			return $this->getContentReservation('La réservation que vous avez sélectionné n\'éxiste plus');
		}

		$param = array(
			'depart' => $this->vars['depart'],
			'arrivee' => $this->vars['arrivee'], 
			'horaire' => $this->vars['horaire'],
			'confirmer_annulation' => true,
		);

		$marker = array(
			'confirmer' => $this->link($param),
			'retour_list' => $this->link(),
		);

		$marker += $reservation;

		return $this->template->nestedMarkerArray($marker, 'FORMANNULER_PART');
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function checkForm() {
		$reservation = $this->getReservation($this->vars['depart'], $this->vars['arrivee'], $this->vars['horaire']);
		if (!count($reservation)) {
			return $this->getContentReservation('La réservation que vous avez sélectionné n\'éxiste plus');
		}

		$message = '';

		if (!isset($this->vars['nb_personnes']) || !$this->vars['nb_personnes'] || !div::testInt($this->vars['nb_personnes'])) {
			$message = 'Vous devez rentrer un nombre de personnes';
		} elseif ($this->vars['nb_personnes'] == $reservation['nb_personnes']) {
			$message = 'Le nombre de personnes doit être différent de l\'ancienne valeur';
		}

		return $message;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getContentformmodifier($message = '') {
		$reservation = $this->getReservation($this->vars['depart'], $this->vars['arrivee'], $this->vars['horaire']);
		
		if (!count($reservation)) {
			return $this->getContentReservation('La réservation que vous avez sélectionné n\'éxiste plus');
		}

		$marker = array(
			'url' => $this->link(),
			'retour_list' => $this->link(),
			'message' => $message? array('0' => array('message' => $message)) : array(),
		);

		$marker += $reservation;

		return $this->template->nestedMarkerArray($marker, 'FORMMODIFIER_PART');
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getContentReservation($message = '') {

		$marker = array(
			'reservation' => $this->getMarkersReservation(),
			'message' => $message? array('0' => array('message' => $message)) : array(),
		);

		return $this->template->nestedMarkerArray($marker, 'MAIN_PART');
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getMarkersReservation() {
		$markers = array();

		foreach ($this->reservation as $k => $v) {
			$depart = isset($v['code_arret'][0])? $v['code_arret'][0] : 0;
			$arrivee = isset($v['code_arret'][1])? $v['code_arret'][1] : 0;
			$horaire = div::makehoraire($v['horaire'], 'd/m/Y H:i:s');
			$modifier = $this->link(array('depart' => $depart, 'arrivee' => $arrivee, 'horaire' => $v['horaire'], 'action' => 'modifier'));
			$annuler = $this->link(array('depart' => $depart, 'arrivee' => $arrivee, 'horaire' => $v['horaire'], 'action' => 'annuler'));

			$markers[] = array(
				'depart' => isset($this->arrets[$depart])? $this->arrets[$depart]['lib_arret'] : '',
				'arrivee' => isset($this->arrets[$arrivee])? $this->arrets[$arrivee]['lib_arret'] : '',
				'horaire_arrivee' => !isset($v['horaire_audepart']) || !$v['horaire_audepart']? $horaire : '',
				'horaire_depart' => isset($v['horaire_audepart']) && $v['horaire_audepart']? $horaire : '',
				'horaire' => $horaire,
				'nb_personnes' => isset($v['nb_personnes'])? $v['nb_personnes'] : 0,
				'url_modifier' => $modifier,
				'url_annuler' => $annuler,
			);
		}

		return $markers;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getReservation($depart, $arrivee, $horaire) {
		$reservation = array();
		foreach ($this->reservation as $k => $v) {
			if (isset($v['code_arret'][0]) && isset($v['code_arret'][1]) && $v['horaire'] && $v['code_arret'][0] == $depart && $v['code_arret'][1] == $arrivee && $v['horaire'] == $horaire) {
				$reservation = $v;
				$h = div::makehoraire($v['horaire'], 'd/m/Y H:i:s');
				$reservation['code_depart'] = $v['code_arret'][0];
				$reservation['code_arrivee'] = $v['code_arret'][1];
				$reservation['arrivee'] = isset($this->arrets[$v['code_arret'][1]])? $this->arrets[$v['code_arret'][1]]['lib_arret'] : '';
				$reservation['depart'] = isset($this->arrets[$v['code_arret'][0]])? $this->arrets[$v['code_arret'][0]]['lib_arret'] : '';
				$reservation['arrivee'] = isset($this->arrets[$v['code_arret'][1]])? $this->arrets[$v['code_arret'][1]]['lib_arret'] : '';
				$reservation['horaire_arrivee'] = !isset($v['horaire_audepart']) || !$v['horaire_audepart']? $h : '';
				$reservation['horaire_depart'] = isset($v['horaire_audepart']) && $v['horaire_audepart']? $h : '';
			}
		}

		return $reservation;
	}

}
?>