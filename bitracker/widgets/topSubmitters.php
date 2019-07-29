<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker topSubmitters Widget
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
 * topSubmitters Widget
 */
class _topSubmitters extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'topSubmitters';
	
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
	 * Specify widget configuration
	 *
	 * @param	null|\IPS\Helpers\Form	$form	Form object
	 * @return	\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
 	{
		$form = parent::configuration( $form );
 		
		$form->add( new \IPS\Helpers\Form\Number( 'number_to_show', isset( $this->configuration['number_to_show'] ) ? $this->configuration['number_to_show'] : 5, TRUE ) );
		return $form;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		foreach ( array( 'week' => 'P1W', 'month' => 'P1M', 'year' => 'P1Y', 'all' => NULL ) as $time => $interval )
		{
			/* What's the time period we care about? */
			$intervalWhere = NULL;
			if ( $interval )
			{
				$intervalWhere = array( 'file_submitted>?', \IPS\DateTime::create()->sub( new \DateInterval( $interval ) )->getTimestamp() );
			}
			
			/* Get the submitters ordered by their rating */
			$where = array( array( 'file_submitter != ? and file_rating > ? AND file_open = 1', 0, 0 ) );
			if ( $interval )
			{
				$where[] = $intervalWhere;
			}
			$ratings = iterator_to_array( \IPS\Db::i()->select(
				'bitracker_torrents.file_submitter, AVG(file_rating) as rating, count(file_id) AS files',
				'bitracker_torrents',
				$where,
				'files DESC, rating DESC',
				isset( $this->configuration['number_to_show'] ) ? $this->configuration['number_to_show'] : 5, 'file_submitter'
			)->setKeyField('file_submitter')->setValueField('rating') );
			
			${$time} = array();

			if( \count( $ratings ) )
			{
				/* Get their file counts */
				$where = array( array( \IPS\Db::i()->in( 'file_submitter', array_keys( $ratings ) ) ) );
				if ( $interval )
				{
					$where[] = $intervalWhere;
				}
				
				$fileCounts = iterator_to_array( \IPS\Db::i()->select( 'file_submitter, count(*) AS count', 'bitracker_torrents', $where, NULL, NULL, 'file_submitter' )->setKeyField('file_submitter')->setValueField('count') );
							
				/* Get member data and put it together */
				foreach( \IPS\Db::i()->select( '*', 'core_members', \IPS\Db::i()->in( 'member_id', array_keys( $ratings ) ) ) as $key => $memberData )
				{
					${$time}[$key]['member'] = \IPS\Member::constructFromData( $memberData );
					${$time}[$key]['files']  = isset( $fileCounts[ $memberData['member_id'] ] ) ? $fileCounts[ $memberData['member_id'] ] : 0;
					${$time}[$key]['rating'] = $ratings[ $memberData['member_id'] ];
				}
			}
		}

		return $this->output( $week, $month, $year, $all );
	}
}