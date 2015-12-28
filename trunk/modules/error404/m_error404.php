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
class m_error404 extends base{
	var $module = 'error404';
	var $titlePage = '404';

	/**
	 * 
	 * 
	 * @param 
	 * @access public
	 * @return void 
	 */
	function main() {
		$this->init();

		$marker = array();

		header("HTTP/1.1 404 Not Found");

		return $this->template->nestedMarkerArray($marker, 'MAIN_PART');
	}

}
?>