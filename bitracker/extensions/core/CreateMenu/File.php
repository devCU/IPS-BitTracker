<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker Create Menu Extension
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4x
 * @subpackage	BitTracker
 * @version     2.0.1 Beta Build
 * @source      https://github.com/GaalexxC/IPS-4.4-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     28 JUL 2019
 *
 *                       GNU General Public License v3.0
 *    This program is free software: you can redistribute it and/or modify       
 *    it under the terms of the GNU General Public License as published by       
 *    the Free Software Foundation, either version 3 of the License, or          
 *    (at your option) any later version.                                        
 *                                                                               
 *    This program is distributed in the hope that it will be useful,            
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of             
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *                                                                               
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see http://www.gnu.org/licenses/
 */

namespace IPS\bitracker\extensions\core\CreateMenu;

 /* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Create Menu Extension
 */
class _File
{
	/**
	 * Get Items
	 *
	 * @return	array
	 */
	public function getItems()
	{		
		if ( \IPS\bitracker\Category::canOnAny( 'add', NULL, \IPS\Settings::i()->club_nodes_in_apps ? array() : array( array( 'cclub_id IS NULL' ) ) ) )
		{
			if ( !\IPS\Settings::i()->club_nodes_in_apps and $theOnlyNode = \IPS\bitracker\Category::theOnlyNode() AND !\IPS\Member::loggedIn()->group['bit_bulk_submit'] )
			{
				return array(
					'file_bitrack' => array(
						'link' 	=> \IPS\Http\Url::internal( "app=bitracker&module=submit&controller=submit&do=submit&_new=1&category=" . $theOnlyNode->_id, 'front', 'torrent_submit' ),
					)
				);
			}
			else
			{
				return array(
					'file_bitrack' => array(
						'link' 		=> \IPS\Http\Url::internal( "app=bitracker&module=submit&controller=submit&_new=1", 'front', 'torrent_submit' ),
						'title' 	=> 'select_category',
						'extraData'	=> array( 'data-ipsDialog' => true, 'data-ipsDialog-size' => "narrow" )
					)
				);
			}
		}
		
		
		return array();
	}
}