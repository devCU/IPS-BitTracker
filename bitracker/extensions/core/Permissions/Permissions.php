<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker Permissions
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	BitTracker
 * @version     2.5.0 Stable
 * @source      https://github.com/devCU/IPS-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     24 OCT 2020
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

namespace IPS\bitracker\extensions\core\Permissions;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Permissions
 */
class _Permissions
{
	/**
	 * Get node classes
	 *
	 * @return	array
	 */
	public function getNodeClasses()
	{		
		return array(
			'IPS\bitracker\Category' => function( $current, $group )
			{
				$rows = array();
				
				foreach( \IPS\bitracker\Category::roots( NULL ) AS $root )
				{
					try
					{
						\IPS\bitracker\Category::populatePermissionMatrix( $rows, $root, $group, $current );
					}
					catch( \BadMethodCallException $e ) {}
				}
				
				return $rows;
			}
		);
	}
}