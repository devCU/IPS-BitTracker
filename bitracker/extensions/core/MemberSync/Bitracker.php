<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker Member Sync
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

namespace IPS\bitracker\extensions\core\MemberSync;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Sync
 */
class _Bitracker
{
	/**
	 * Member is merged with another member
	 *
	 * @param	\IPS\Member	$member		Member being kept
	 * @param	\IPS\Member	$member2	Member being removed
	 * @return	void
	 */
	public function onMerge( $member, $member2 )
	{
		\IPS\Db::i()->update( 'bitracker_downloads', array( 'dmid' => $member->member_id ), array( 'dmid=?', $member2->member_id ) );
		\IPS\Db::i()->update( 'bitracker_files', array( 'file_approver' => $member->member_id ), array( 'file_approver=?', $member2->member_id ) );
		\IPS\Db::i()->update( 'bitracker_files_notify', array( 'notify_member_id' => $member->member_id ), array( 'notify_member_id=?', $member2->member_id ) );
		\IPS\Db::i()->delete( 'bitracker_sessions', array( 'dsess_mid=?', $member2->member_id ) );

		/* Clean up duplicate notify rows */
		$unique		= array();
		$duplicate	= array();

		foreach( \IPS\Db::i()->select( 'bitracker_torrents_notify.*, bitracker_torrents.file_submitter', 'bitracker_torrents_notify', array( 'notify_member_id=?', $member->member_id ) )->join( 'bitracker_torrents', "bitracker_torrents_notify.notify_file_id=bitracker_torrents.file_id" ) as $notify )
		{
			if( !\in_array( $notify['notify_file_id'], $unique ) and $notify['notify_member_id'] !== $notify['file_submitter'] )
			{
				$unique[]	= $notify['notify_file_id'];
			}
			else
			{
				$duplicate[]	= $notify['notify_id'];
			}
		}

		if( \count( $duplicate ) )
		{
			\IPS\Db::i()->delete( 'bitracker_torrents_notify', array( "notify_id IN('" . implode( "','", $duplicate ) . "')" ) );
		}
	}
	
	/**
	 * Member is deleted
	 *
	 * @param	$member	\IPS\Member	The member
	 * @return	void
	 */
	public function onDelete( $member )
	{
		\IPS\Db::i()->delete( 'bitracker_downloads', array( 'dmid=?', $member->member_id ) );
		\IPS\Db::i()->delete( 'bitracker_sessions', array( 'dsess_mid=?', $member->member_id ) );
		\IPS\Db::i()->update( 'bitracker_torrents', array( 'file_approver' => 0 ), array( 'file_approver=?', $member->member_id ) );
		\IPS\Db::i()->delete( 'bitracker_torrents_notify', array( 'notify_member_id=?', $member->member_id ) );
	}
}