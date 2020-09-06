<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker Overview Controller
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4.10
 * @subpackage	BitTracker
 * @version     2.2.0 Final
 * @source      https://github.com/GaalexxC/IPS-4.4-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     05 SEP 2020
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

namespace IPS\bitracker\modules\admin\dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Overview
 */
class _overview extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'overview_manage' );
		parent::execute();
	}

	protected function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'overview_manage' );

		$view = $this->_manageOverview();

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('head_tracker_overview');
		\IPS\Output::i()->output = $view;
}
	/**
	 * Overview
	 *
	 * @return	void
	 */
	protected function _manageOverview()
	{
		$chart = new \IPS\Helpers\Chart\Database( \IPS\Http\Url::internal( "app=bitracker&module=stats&controller=torrents" ), 'bitracker_downloads', 'dtime', '', array(
			'backgroundColor' 	=> '#ffffff',
			'colors'			=> array( '#10967e', '#ea7963', '#de6470', '#6b9dde', '#b09be4', '#eec766', '#9fc973', '#e291bf', '#55c1a6', '#5fb9da' ),
			'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
			'lineWidth'			=> 1,
			'areaOpacity'		=> 0.4,
		), 'AreaChart', 'monthly', array( 'start' => 0, 'end' => 0 ), array( 'dmid', 'dfid', 'dtime', 'dsize', 'dua', 'dip' ) );
		
		$chart->addSeries( \IPS\Member::loggedIn()->language()->addToStack('torrents'), 'number', 'COUNT(*)', FALSE );
		$chart->availableTypes = array( 'AreaChart', 'ColumnChart', 'BarChart' );
		
		$chart->tableParsers = array(
			'dmid'	=> function( $val )
			{
				$member = \IPS\Member::load( $val );

				if( $member->member_id )
				{
					return \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $member->url(), TRUE, $member->name );
				}
				else
				{
					return \IPS\Member::loggedIn()->language()->addToStack('deleted_member');
				}
			},
			'dfid'	=> function( $val )
			{
				try
				{
					$file = \IPS\bitracker\File::load( $val );
					return \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $file->url(), TRUE, $file->name );
				}
				catch ( \OutOfRangeException $e )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('deleted_file');
				}
			},
			'dtime'	=> function( $val )
			{
				return (string) \IPS\DateTime::ts( $val );
			},
			'dsize'	=> function( $val )
			{
				return \IPS\Output\Plugin\Filesize::humanReadableFilesize( $val );
			},
			'dua'	=> function( $val )
			{
				return (string) \IPS\Http\Useragent::parse( $val );
			},
			'dip'	=> function( $val )
			{
				return \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( \IPS\Http\Url::internal( "app=core&module=members&controller=ip&ip={$val}&tab=bitracker_BitrackerLog" ), FALSE, $val );
			}
		);
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('head_tracker_overview');
		\IPS\Output::i()->output = (string) $chart;

		$oneMonthAgo = \IPS\DateTime::create()->sub( new \DateInterval( 'P1M' ) )->getTimestamp();
		
		/* Basic stats */
		$data = array(
			'total_disk_transfer'		=> (int) \IPS\Db::i()->select( 'SUM(record_size)', 'bitracker_torrents_records' )->first(),
			'total_torrents'			=> (int) \IPS\Db::i()->select( 'COUNT(*)', 'bitracker_torrents' )->first(),
			'total_peers'				=> (int) \IPS\Db::i()->select( 'COUNT(*)', 'bitracker_torrent_peers' )->first(),
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
		return \IPS\Theme::i()->getTemplate( 'dashboard', 'bitracker', 'admin' )->overview( $data );
	}
	
}