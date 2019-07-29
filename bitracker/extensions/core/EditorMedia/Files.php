<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker Editor Media
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

namespace IPS\bitracker\extensions\core\EditorMedia;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Media: Files
 */
class _Files
{
	/**
	 * Get Counts
	 *
	 * @param	\IPS\Member	$member		The member
	 * @param	string		$postKey	The post key
	 * @param	string|null	$search		The search term (or NULL for all)
	 * @return	array		array( 'Title' => 0 )
	 */
	public function count( $member, $postKey, $search=NULL )
	{
		$where = array(
			array( "record_file_id IN(?) AND record_type=? AND record_backup=0", \IPS\Db::i()->select( 'file_id', 'bitracker_torrents', array( 'file_submitter=?', $member->member_id ) ), 'upload' ),
		);
		
		if ( $search )
		{
			$where[] = array( "record_realname LIKE ( CONCAT( '%', ?, '%' ) )", $search );
		}
						
		return \IPS\Db::i()->select( 'COUNT(*)', 'bitracker_torrents_records', $where )->first();
	}
	
	/**
	 * Get Files
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string|null	$search	The search term (or NULL for all)
	 * @param	string		$postKey	The post key
	 * @param	int			$page	Page
	 * @param	int			$limit	Number to get
	 * @return	array		array( 'Title' => array( (IPS\File, \IPS\File, ... ), ... )
	 */
	public function get( $member, $search, $postKey, $page, $limit )
	{
		$where = array(
			array( "record_file_id IN(?) AND record_type=? AND record_backup=0", \IPS\Db::i()->select( 'file_id', 'bitracker_torrents', array( 'file_submitter=?', $member->member_id ) ), 'upload' ),
		);
		
		if ( $search )
		{
			$where[] = array( "record_realname LIKE ( CONCAT( '%', ?, '%' ) )", $search );
		}
		
		$return = array();
		foreach ( \IPS\Db::i()->select( '*', 'bitracker_torrents_records', $where, 'record_time DESC', array( ( $page - 1 ) * $limit, $limit ) ) as $row )
		{
			$file = \IPS\bitracker\File::load( $row['record_file_id'] );
			$obj = \IPS\File::get( 'bitracker_Torrents', $row['record_location'] );
			$obj->contextInfo = $file->name;
			$obj->screenshot = $file->primary_screenshot;
			$obj->originalFilename = $row['record_realname'];
			$return[ (string) $file->url()->setQueryString( array( 'do' => 'download', 'r' => $row['record_id'] ) ) ] = $obj;
		}
				
		return $return;
	}
}