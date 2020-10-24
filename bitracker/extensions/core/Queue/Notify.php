<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker Notify
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	BitTracker
 * @version     2.5.0 Stable
 * @source      https://github.com/devCU/IPS-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     24 OCT 2020
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

namespace IPS\bitracker\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class _Notify
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array
	 */
	public function preQueueData( $data )
	{
		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( $data, $offset )
	{
		try
		{
			$file = \IPS\bitracker\File::load( $data['file'] );
		}
		catch( \OutOfRangeException $e )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		$notifyIds = array();

		$recipients = iterator_to_array( \IPS\Db::i()->select( 'bitracker_torrents_notify.*', 'bitracker_torrents_notify', array( 'notify_file_id=?', $data['file'] ), 'notify_id ASC', array( $offset, \IPS\Bitracker\File::NOTIFICATIONS_PER_BATCH ) ) );

		if( !\count( $recipients ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		$notification = new \IPS\Notification( \IPS\Application::load( 'bitracker' ), 'new_torrent_version', $file, array( $file ) );

		foreach( $recipients AS $recipient )
		{
			$recipientMember = \IPS\Member::load( $recipient['notify_member_id'] );
			if ( $file->container()->can( 'view', $recipientMember ) )
			{
				$notifyIds[] = $recipient['notify_id'];
				$notification->recipients->attach( $recipientMember );
			}
		}

		\IPS\Db::i()->update( 'bitracker_torrents_notify', array( 'notify_sent' => time() ), \IPS\Db::i()->in( 'notify_id', $notifyIds ) );
		$notification->send();

		return $offset + \IPS\bitracker\File::NOTIFICATIONS_PER_BATCH;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( $data, $offset )
	{
		try
		{
			$file = \IPS\bitracker\File::load( $data['file'] );
		}
		catch( \OutOfRangeException $e )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		$complete			= $data['notifyCount'] ? round( 100 / $data['notifyCount'] * $offset, 2 ) : 100;

		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('backgroundQueue_new_version', FALSE, array( 'htmlsprintf' => array( \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $file->url(), TRUE, $file->name, FALSE ) ) ) ), 'complete' => $complete );
	}
}