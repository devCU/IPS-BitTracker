<?php
/**
 * @brief       BitTracker Application Class
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.2x
 * @subpackage	BitTracker
 * @version     1.0.0 Beta 1
 * @source      https://github.com/GaalexxC/IPS-4.2-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/ips4bt/
 * @Created     11 FEB 2018
 * @Updated     19 FEB 2018
 *
 *                    GNU General Public License v3.0
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

namespace IPS\bitracker\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Top Downloaders
 */
class _downloaders extends \IPS\Dispatcher\Controller
{
	const PER_PAGE = 25;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'downloaders_manage' );
		parent::execute();
	}

	/**
	 * Top Downloaders
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$values = NULL;
		$where = array( array( 'dmid>0' ) );
		
		if ( isset( \IPS\Request::i()->form ) )
		{
			$form = new \IPS\Helpers\Form( 'form', 'go' );
			$form->add( new \IPS\Helpers\Form\DateRange( 'stats_date_range' ), NULL, FALSE, array( 'start' => array( 'max' => new \IPS\DateTime() ), 'end' => array( 'max' => new \IPS\DateTime() ) ));
			
			if ( $values = $form->values() )
			{
				if ( $values['stats_date_range']['start'] )
				{
					$where[] = array( 'dtime>?', $values['stats_date_range']['start']->getTimestamp() );
				}
				if ( $values['stats_date_range']['end'] )
				{
					$where[] = array( 'dtime<?', $values['stats_date_range']['end']->getTimestamp() );
				}
			}
			else
			{
				\IPS\Output::i()->output = $form;
				return;
			}
		}
		
		$page = isset( \IPS\Request::i()->page ) ? intval( \IPS\Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		$select = \IPS\Db::i()->select( 'dmid, COUNT(*) as bitracker', 'bitracker_torrents', $where, 'bitracker DESC', array( ( $page - 1 ) * static::PER_PAGE, static::PER_PAGE ), 'dmid', NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS );
		$mids = array();
		
		foreach( $select as $row )
		{
			$mids[] = $row['dmid'];
		}
		
		$members = array();
		
		if ( count( $mids ) )
		{
			$members = iterator_to_array( \IPS\Db::i()->select( '*', 'core_members', array( \IPS\Db::i()->in( 'member_id', $mids ) ) )->setKeyField('member_id') );
		}
		
		$pagination = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
			\IPS\Http\Url::internal( 'app=bitracker&module=stats&controller=downloaders' )->setQueryString( $values ),
			ceil( $select->count( TRUE ) / static::PER_PAGE ),
			$page,
			static::PER_PAGE,
			FALSE
		);
		
		\IPS\Output::i()->sidebar['actions'] = array(
			'settings'	=> array(
				'title'		=> 'stats_date_range',
				'icon'		=> 'calendar',
				'link'		=> \IPS\Http\Url::internal( 'app=bitracker&module=stats&controller=downloaders&form=1' )->setQueryString( $values ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('stats_date_range') )
			)
		);
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('stats')->bitrackerTable( $select, $pagination, $members );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__bitracker_stats_downloaders');
	}
}