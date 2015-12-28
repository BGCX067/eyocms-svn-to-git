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
class m_default extends base{
	var $module = 'default';


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

		//$this->sendMail();

		return $content;
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function getContent() {
		$this->titlePage = 'Test';
		$marker = array('content' => 'trop bien bla bla bla');

		return $this->template->nestedMarkerArray($marker, 'DEFAULT_PART');
	}

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function sendMail() {
		$m= new Mail; // create the mail
		$m->From('noreply@webtod.com');
		$m->To('davtouzet@free.fr');
		$m->Subject('test mail');	

		$marker = array('content' => 'owi trop fat ca fonctionne');
		$message = $this->template->nestedMarkerArray($marker, 'MAIL_PART');
		$m->Body($message, 'utf-8');	// set the body
		$m->Cc('dtouzet@mtpi.fr');
		$m->Priority(4) ;	// set the priority to Low 
		$m->Send();	// send the mail
		var_dump('send');
	}

}
?>