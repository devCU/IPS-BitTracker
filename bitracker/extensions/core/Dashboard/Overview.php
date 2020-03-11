<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker Dashboard extension
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4x
 * @subpackage	BitTracker
 * @version     2.1.0 RC 1
 * @source      https://github.com/GaalexxC/IPS-4.4-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     11 MAR 2020
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

namespace IPS\bitracker\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: Overview
 */
class _Overview
{
	/**
	* Can the current user view this dashboard item?
	*
	* @return	bool
	*/
	public function canView()
	{
		return TRUE;
	}

	/** 
	 * Return the block HTML show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock()
	{
		$oneMonthAgo = \IPS\DateTime::create()->sub( new \DateInterval( 'P1M' ) )->getTimestamp();
		
		/* Basic stats */
		$data = array(
			'total_disk_transfer'			=> (int) \IPS\Db::i()->select( 'SUM(record_size)', 'bitracker_torrents_records' )->first(),
			'total_torrents'				=> (int) \IPS\Db::i()->select( 'COUNT(*)', 'bitracker_torrents' )->first(),
			'total_peers'				=> (int) \IPS\Db::i()->select( 'COUNT(*)', 'bitracker_torrents' )->first(),
			'total_views'				=> (int) \IPS\Db::i()->select( 'SUM(file_views)', 'bitracker_torrents' )->first(),
			'total_downloads'			=> (int) \IPS\Db::i()->select( 'SUM(file_torrents)', 'bitracker_torrents' )->first(),
			'total_bandwidth'			=> (int) \IPS\Db::i()->select( 'SUM(dsize)', 'bitracker_downloads' )->first(),
			'current_month_bandwidth'	=> (int) \IPS\Db::i()->select( 'SUM(dsize)', 'bitracker_downloads', array( 'dtime>?', $oneMonthAgo ) )->first(),
		);
		
		/* Specific files (will fail if no files yet) */
		try
		{
			$data['largest_file'] = \IPS\bitracker\File::constructFromData( \IPS\Db::i()->select( '*', 'bitracker_torrents', NULL, 'file_size DESC', 1 )->first() );
			$data['most_viewed_file'] = \IPS\bitracker\File::constructFromData( \IPS\Db::i()->select( '*', 'bitracker_torrents', NULL, 'file_views DESC', 1 )->first() );
			$data['most_downloaded_file'] = \IPS\bitracker\File::constructFromData( \IPS\Db::i()->select( '*', 'bitracker_torrents', NULL, 'file_torrents DESC', 1 )->first() );
		}
		catch ( \Exception $e ) { }
		
		/* Display */
		return \IPS\Theme::i()->getTemplate( 'dashboard', 'bitracker' )->overview( $data );
	}
}