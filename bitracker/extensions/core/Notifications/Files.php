<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker Notification Options
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

namespace IPS\bitracker\extensions\core\Notifications;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notification Options
 */
class _Files
{
	/**
	 * Get fields for configuration
	 *
	 * @param	\IPS\Member|null	$member		The member (to take out any notification types a given member will never see) or NULL if this is for the ACP
	 * @return	array
	 */
	public static function configurationOptions( \IPS\Member $member = NULL )
	{
		return array(
			'new_torrent_version'	=> array(
				'type'				=> 'standard',
				'notificationTypes'	=> array( 'new_torrent_version' ),
				'title'				=> 'notifications__bitracker_Files',
				'showTitle'			=> FALSE,
				'description'		=> 'notifications__bitracker_Files_desc',
				'default'			=> array( 'inline', 'push', 'email' ),
				'disabled'			=> array()
			)
		);
	}
	
	/**
	 * Parse notification: new_file_version
	 *
	 * @param	\IPS\Notification\Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	 	return array(
	 		'title'		=> "Mark has replied to A Topic",			// The notification title
	 		'url'		=> \IPS\Http\Url\Friendly::internal( ... ),	// The URL the notification should link to
	 		'content'	=> "Lorem ipsum dolar sit",					// [Optional] Any appropriate content. Do not format this like an email where the text
	 																// explains what the notification is about - just include any appropriate content.
	 																// For example, if the notification is about a post, set this as the body of the post.
	 		'author'	=>  \IPS\Member::load( 1 ),					// [Optional] The user whose photo should be displayed for this notification
	 	);
	 * @endcode
	 */
	public function parse_new_file_version( $notification, $htmlEscape=TRUE )
	{
		$item = $notification->item;
		if ( !$item )
		{
			throw new \OutOfRangeException;
		}
                
		return array(
			'title'		=> ( $item->container()->version_numbers ) ? \IPS\Member::loggedIn()->language()->addToStack( 'notification__new_torrent_version_with', FALSE, array( 'sprintf' => array( $item->author()->name, $item->version, $item->mapped('title') ) ) ) : \IPS\Member::loggedIn()->language()->addToStack( 'notification__new_torrent_version', FALSE, array( 'sprintf' => array( $item->author()->name, $item->mapped('title') ) ) ),
			'url'		=> $notification->item->url(),
			'content'	=> $notification->item->content(),
			'author'	=> $notification->extra ?: $notification->item->author(),
			'unread'	=> (bool) ( $item->unread() )
		);
	}
	
	/**
	 * Parse notification for mobile: new_torrent_version
	 *
	 * @param	\IPS\Lang			$language	The language that the notification should be in
	 * @param	\IPS\bitracker\File	$file		The file
	 * @return	array
	 */
	public static function parse_mobile_new_torrent_version( \IPS\Lang $language, \IPS\bitracker\File $file )
	{
		return array(
			'body'		=> $language->addToStack( 'notification__new_torrent_version', FALSE, array( 'htmlsprintf' => array(
				$file->author()->name,
				$file->mapped('title')
			) ) ),
			'data'		=> array(
				'url'		=> (string) $file->url(),
				'author'	=> $file->author()
			),
			'channelId'	=> 'files',
		);
	}
}