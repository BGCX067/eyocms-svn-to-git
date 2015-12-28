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
class m_voyagesreguliers extends base{
	var $webtod;
	var $voyage;
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
		$this->titlePage = 'Activer mes voyages réguliers';

		//création du tableau des voyages réguliers
		$param = array('code_adherent' => $GLOBALS['front']->user->code_adherent);
		$reponse = $this->webtod->getResponseParse('voyageregulier', ' ListerVoyagesReguliersParAdherent', $param, 'reservation', 'ServiceListerVoyagesReguliersParAdherent_Response.xml');

		$this->makeArrayVoyages($reponse);

		//création du tableau des arrets
		$param = array('avec_arretslv' => false, 'avec_arretsdz' => true, 'accesreservation' => true);
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

		$content = $this->getContent();

		return $content;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function makeArrayVoyages($reponse) {
		$this->voyage = array();

		if (!isset($reponse['body'])) {
			return;
		}

		foreach ($reponse['body'] as $k => $v) {
			$key = explode('[', $k);
			if ($key[0] != 'voyageregulier') {
				continue;
			}

			$this->voyage[] = $v;	
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
	function getContent() {

		$marker = array(
			'reservation' => $this->getMarkersVoyage(),
			'url_activer' => $this->link(array('activer' => 1)),
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
	function getMarkersVoyage() {
		$markers = array();

		foreach ($this->voyage as $k => $v) {
			$depart = isset($v['code_arret'][0])? $v['code_arret'][0] : 0;
			$arrivee = isset($v['code_arret'][1])? $v['code_arret'][1] : 0;
			$horaire = div::makehoraire($v['horaire'], 'd/m/Y H:i:s');

			$markers[] = array(
				'depart' => isset($this->arrets[$depart])? $this->arrets[$depart]['lib_arret'] : '',
				'arrivee' => isset($this->arrets[$arrivee])? $this->arrets[$arrivee]['lib_arret'] : '',
				'debut_periode' => isset($v['periode']['debut_periode'])? div::makehoraire($v['periode']['debut_periode'], 'd/m/Y') : '',
				'fin_periode' => isset($v['periode']['fin_periode'])? div::makehoraire($v['periode']['fin_periode'], 'd/m/Y') : '',
				'nb_personnes' => isset($v['nb_personnes'])? $v['nb_personnes'] : 0,
				'horaire_arrivee' => !isset($v['horaire_audepart']) || !$v['horaire_audepart']? $horaire : '',
				'horaire_depart' => isset($v['horaire_audepart']) && $v['horaire_audepart']? $horaire : '',
				'horaire' => $horaire,
				'activation' => $this->getActivationVoyage($v),
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
	function getActivationVoyage($voyage) {
		if (!isset($voyage['semaine_activation']) || !preg_match('/^[NO]{7,7}$/', $voyage['semaine_activation'])) {
			$filtre = 'NNNNNNN';
		} else {
			$filtre = $voyage['semaine_activation'];
		}

		$activation = array();

		for ($i=0; $i<7; $i++) {
			$activation[$i] = array('active?' => $filtre[$i] == 'O'? true : false);
		}

		return $activation;
	}
}
?>