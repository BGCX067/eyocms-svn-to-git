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
class m_reseau extends base{
	var $webtod;
	var $Treelignes;

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

		$param = array('avec_arretslv' => true, 'avec_arretsdz' => false, 'accesreservation' => true);
		$reponse = $this->webtod->getResponseParse('reseau', 'ServiceListerLignes', $param, 'reseau', 'ServiceListerLignes-answer.xml');
		
		$this->Treelignes = $this->getTreeLignes($reponse);
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

		$this->redirect();

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
	function redirect() {
		$ligneSelected = isset($this->vars['ligne']) && intval($this->vars['ligne']) && isset($this->Treelignes[intval($this->vars['ligne'])])? intval($this->vars['ligne']) : 0;
		if (isset($this->vars['reserver']) && $ligneSelected) {
			header('Location: '.$this->link(array('ligne' => intval($this->vars['ligne'])), null, 'reservation'));
			exit();
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
			'lignes' => $this->getMarkersLignes(),
			'json' => div::to_json($this->Treelignes),
			'resultat' => $this->getResultats(),
			'url' => $this->link(),
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
	function getMarkersLignes() {
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
	function getResultats() {
		if (!isset($this->vars['ligne']) || !intval($this->vars['ligne']) && !isset($this->Treelignes[intval($this->vars['ligne'])])) {
			return array();
		}

		$marker = array();

		$arrets = $this->Treelignes[intval($this->vars['ligne'])]['arrets'];

		foreach ($arrets as $v) {
			$marker[] = array('name' => $v['name']);
		}

		return array(0 => array('ligne_name' => $this->Treelignes[intval($this->vars['ligne'])]['name'],'arrets' => $marker));
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getTreeLignes($reponse) {
		$body = isset($reponse['body'])? $reponse['body'] : array();
		$lignes = array();

		foreach ($body as $k => $v) {
			$key = explode('[', $k);
			if ($key[0] == 'ligne') {
				if (!isset($lignes[$v['code_ligne']])) {
					$arrets = $this->getArrets($v);
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
	function getArrets($arrayLigne) {
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
}
?>