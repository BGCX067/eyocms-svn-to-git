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
class m_reservation extends base{
	var $webtod;
	var $reponse = '';
	var $Treelignes;

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function init() {
		parent::init();
		$this->webtod = new webtodtunnel();
		$this->titlePage = 'Le Réseau';

		$param = array('avec_arretslv' => true, 'avec_arretsdz' => false, 'accesreservation' => true);
		$this->reponse = $this->webtod->getResponseParse('reseau', 'ServiceListerLignes', $param, 'reseau', 'ServiceListerLignes-answer.xml');
		$this->Treelignes = $this->getTreeLignes();
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	public function main() {
		$this->init();

		$content = $this->getContent($this->getMode());

		return $content;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	protected function getMode() {
		$mode = 'form';
		if (isset($this->vars['recherche']) && $this->checkForm()) {
			$mode = 'recherche';
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
	private function checkForm() {
		$no_error = true;

		if (!isset($this->vars['nombre']) || intval($this->vars['nombre']) > 4 || intval($this->vars['nombre']) <= 0) {
			div::debug(intval($this->vars['nombre']), 'erreur nombre');
			$no_error = false;
		}

		$issetLigne = isset($this->Treelignes[intval($this->vars['ligne'])]);


		if (!isset($this->vars['ligne']) || !$issetLigne) {
			div::debug($this->vars['ligne'], 'erreur ligne');
			$no_error = false;
		}

		if (!isset($this->vars['depart']) || !$this->issetArret($this->vars['depart'], intval($this->vars['ligne']))) {
			div::debug($this->vars['depart'], 'erreur depart');
			$no_error = false;
		}

		if (!isset($this->vars['arrive']) || !$this->issetArret($this->vars['arrive'], intval($this->vars['ligne']))) {
			div::debug($this->vars['arrive'], 'erreur arrive');
			$no_error = false;
		}

		if (!isset($this->vars['date']) || !div::valideDate($this->vars['date'])) {
			div::debug($this->vars['date'], 'erreur date');
			$no_error = false;
		}

		if (!isset($this->vars['heure']) || !div::testInt(intval($this->vars['heure'])) || intval($this->vars['heure']) < 0 || intval($this->vars['heure']) > 23) {
			div::debug(intval($this->vars['heure']), 'erreur heure');
			$no_error = false;
		}

		if (isset($this->vars['depart']) && isset($this->vars['arrive']) && $this->vars['depart'] == $this->vars['arrive']) {
			div::debug('erreur', 'erreur meme arrive et depart');
			$no_error = false;
		}

		if (!isset($this->vars['min']) || !div::testInt(intval($this->vars['min'])) || intval($this->vars['min']) < 0 || intval($this->vars['min']) > 59) {
			div::debug(intval($this->vars['min']), 'erreur min');
			$no_error = false;
		}

		return $no_error;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	private function getContent($mode) {
		switch ($mode) {
		case 'recherche':
			$marker = $this->getMarkerArrayForm();
			if ($reponse = $this->sendRequestSearch()) {
				$marker['resultat'][] = $reponse;
			}
			break;
		case 'form':
		default:
			$marker = $this->getMarkerArrayForm();
		}
		/*div::debug($marker, 'getContent');*/
		return $this->template->nestedMarkerArray($marker, 'MAIN_PART');
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access private
	 * @return void 
	 */
	private function sendRequestSearch() {
		$param = array(
			'code_adherent' => $GLOBALS['front']->user->code_adherent,
			'code_arret' => array(0 => intval($this->vars['depart']), 1 => intval($this->vars['arrive'])),
			'horaire' => div::ddmmyyToyyyymmddhhmmss($this->vars['date'], $this->vars['heure'], $this->vars['min']),
			'horaire_audepart' => true,
			'nb_personnes' => $this->vars['nombre'],
			'cod_motdep' => 1,
		);

		$reponse = $this->webtod->getResponseParse('reservation', 'ServiceCreerReservation', $param, 'reservation','ServiceCreerReservation-form.xml');
		/*div::debug($reponse, '$reponse');*/

		foreach ($reponse['header']['statut'] as $k => $v) {
			if ($v['code'] == '-10C01102') {
				return $this->markerPeriod($reponse['body']);
			}
		}

		return false;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access private
	 * @return void 
	 */
	private function markerPeriod($periode) {
		$marker = array();
		foreach ($periode as $plageHorraire) {
			$depart = '';
			$arrivee = '';
			if (isset($plageHorraire['periode'])) {
				$depart = isset($plageHorraire['periode']['debut_periode'])? $plageHorraire['periode']['debut_periode'] : (isset($plageHorraire['periode']['fin_periode'])? $plageHorraire['periode']['fin_periode'] : '');
			}

			if (isset($plageHorraire['periode[1]'])) {
				$arrivee = isset($plageHorraire['periode[1]']['fin_periode'])? $plageHorraire['periode[1]']['fin_periode'] : (isset($plageHorraire['periode[1]']['debut_periode'])? $plageHorraire['periode[1]']['debut_periode'] : '');
			}

			$marker[] = array(
				'depart' => $depart? div::makehoraire($depart, 'd/m/Y H:i:s') : $depart, 
				'arrivee' => $arrivee? div::makehoraire($arrivee, 'd/m/Y H:i:s') : $arrivee,
			);
		}
		//div::debug($marker, 'markerPeriod');
		return array('resultat_ligne' => $marker);
	}



	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	private function getMarkerArrayForm() {
		$marker = array(
			'lignes' => $this->getMarkersLignes(),
			'arrets_depart' => $this->getMarkersArrets('depart'),
			'arrets_arrive' => $this->getMarkersArrets('arrive'),
			'url' => $this->link(),
			'date' => isset($this->vars['date'])? htmlspecialchars($this->vars['date']) : '',
			'heure' => isset($this->vars['heure'])? htmlspecialchars($this->vars['heure']) : '',
			'min' => isset($this->vars['min'])? htmlspecialchars($this->vars['min']) : '',
			'nombre' => isset($this->vars['nombre'])? htmlspecialchars($this->vars['nombre']) : '',
			'resultat' => array(),
		);

		return $marker;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	private function getMarkersLignes() {
		$marker = array();
		$ligneSelected = isset($this->vars['ligne']) && intval($this->vars['ligne']) && isset($this->Treelignes[intval($this->vars['ligne'])])? intval($this->vars['ligne']) : 0;

		foreach ($this->Treelignes as $v) {
			$marker[] = array(
				'name' => $v['name'],
				'value' => $v['value'],
				'selected' => $ligneSelected == $v['value']? true : false,
			);
		}

		return $marker;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	private function getMarkersArrets($type) {
		if (!isset($this->vars['ligne']) || !intval($this->vars['ligne']) && !isset($this->Treelignes[intval($this->vars['ligne'])])) {
			return array();
		}



		$arretSelected = isset($this->vars[$type]) && intval($this->vars[$type])? intval($this->vars[$type]) : 0;

		$marker = array();

		$arrets = $this->Treelignes[intval($this->vars['ligne'])]['arrets'];

		foreach ($arrets as $v) {
			$marker[] = array(
				'name' => $v['name'],
				'value' => $v['value'],
				'selected' => $v['value'] == $arretSelected? true : false,
			);
		}

		return $marker;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	private function getTreeLignes() {
		$body = isset($this->reponse['body'])? $this->reponse['body'] : array();
		$lignes = array();

		foreach ($body as $k => $v) {
			$key = explode('[', $k);
			if ($key[0] == 'ligne') {
				if (!isset($lignes[$v['code_ligne']])) {
					$arrets = $this->getArretsTree($v);
					$lignes[$v['code_ligne']] = array(
						'name' => $v['lib_ligne'],
						'value' => $v['code_ligne'],
						'arrets' => $arrets,
					);
				}
			}
		}

		return $lignes;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	private function getArretsTree($arrayLigne) {
		$arrets = array();
		$listeArrets = array();
		foreach ($arrayLigne as $k => $v) {
			$keyItinéraire = explode('[', $k);
			if ($keyItinéraire[0] == 'itineraire') {
				foreach ($v as $k2 => $v2) {
					$keyArret = explode('[', $k2);
					if ($keyArret[0] == 'arret' && !isset($listeArrets[$v2['code_arret']])) {
						$arrets[] = array(
							'name' => $v2['lib_arret'],
							'value' => $v2['code_arret'],
						);

						$listeArrets[$v2['code_arret']] = true;
					}
				}
			}
		}
		unset($listeArrets);
		return $arrets;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access private
	 * @return void 
	 */
	private function issetArret($code, $ligne) {
		if (!isset($this->Treelignes[$ligne])) {
			return false;
		}

		$isset = false;
		foreach ($this->Treelignes[$ligne]['arrets'] as $v) {
			if ($v['value'] == $code) {
				$isset = true;
				break;
			}
		}

		return $isset;
	}
}
?>