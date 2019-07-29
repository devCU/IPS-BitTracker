<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker bitrackerStats Widget
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
 

namespace IPS\bitracker\widgets;

 /* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * bitrackerStats Widget
 */
class _bitrackerStats extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'bitrackerStats';
	
	/**
	 * @brief	App
	 */
	public $app = 'bitracker';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

 	/**
	 * Init the widget
	 *
	 * @return	void
	 */
 	public function init()
 	{
 		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'widgets.css', 'bitracker', 'front' ) );

 		parent::init();
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		$stats = \IPS\Db::i()->select( 'COUNT(*) AS totalFiles, SUM(file_comments) AS totalComments, SUM(file_reviews) AS totalReviews', 'bitracker_torrents', array( "file_open=?", 1 ) )->first();
		$stats['totalAuthors'] = \IPS\Db::i()->select( 'COUNT(DISTINCT file_submitter)', 'bitracker_torrents' )->first();
		
		$latestFile = NULL;
		foreach ( \IPS\bitracker\File::getItemsWithPermission( array(), NULL, 1 ) as $latestFile )
		{
			break;
		}
		
		return $this->output( $stats, $latestFile );
	}
}