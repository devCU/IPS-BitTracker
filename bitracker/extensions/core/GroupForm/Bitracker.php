<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker Admin CP Group Form
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

namespace IPS\bitracker\extensions\core\GroupForm;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Admin CP Group Form
 */
class _Bitracker
{
	/**
	 * Process Form
	 *
	 * @param	\IPS\Helpers\Form		$form	The form
	 * @param	\IPS\Member\Group		$group	Existing Group
	 * @return	void
	 */
	public function process( &$form, $group )
	{
		$restrictions = $group->bit_restrictions ? json_decode( $group->bit_restrictions, TRUE ) : array();

		$form->addHeader( 'torrent_management' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'bit_view_bitracker', $group->bit_view_bitracker ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'bit_view_approvers', $group->bit_view_approvers ) );
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'bit_bypass_revision', $group->bit_bypass_revision ) );
		}
				
		$form->addHeader( 'submission_permissions' );
		if ( \IPS\Application::appIsEnabled( 'nexus' ) and $group->g_id != \IPS\Settings::i()->guest_group )
		{
			if ( \IPS\Settings::i()->bit_nexus_on )
			{
				$form->add( new \IPS\Helpers\Form\YesNo( 'bit_add_paid', $group->bit_add_paid ) );
			}
			else
			{
				\IPS\Member::loggedIn()->language()->words['bit_add_paid_desc'] = \IPS\Member::loggedIn()->language()->addToStack( 'bit_add_paid_enable', FALSE );
				$form->add( new \IPS\Helpers\Form\YesNo( 'bit_add_paid', FALSE, FALSE, array( 'disabled' => TRUE ) ) );
			}
		}
		$form->add( new \IPS\Helpers\Form\YesNo( 'bit_bulk_submit', $group->bit_bulk_submit ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'bit_linked_torrents', $group->bit_linked_torrents ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'bit_import_torrents', $group->bit_import_torrents ) );
		
		$form->addHeader( 'access_restrictions' );
		if ( \IPS\Application::appIsEnabled( 'nexus' ) AND \IPS\Settings::i()->bit_nexus_on )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'bit_paid_restrictions', $group->bit_paid_restrictions ) );
		}
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
			$form->add( new \IPS\Helpers\Form\Number( 'min_posts', isset( $restrictions['min_posts'] ) ? $restrictions['min_posts'] : 0, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'bit_throttling_unlimited' ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('approved_posts_comments') ) );
		}
		$form->add( new \IPS\Helpers\Form\Number( 'bit_throttling', $group->bit_throttling, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'bit_throttling_unlimited' ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('bit_throttling_suffix') ) );
		$form->add( new \IPS\Helpers\Form\Interval( 'bit_wait_period', $group->bit_wait_period, FALSE, array( 'valueAs' => \IPS\Helpers\Form\Interval::SECONDS, 'unlimited' => 0, 'unlimitedLang' => 'bit_throttling_unlimited' ) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'limit_sim', isset( $restrictions['limit_sim'] ) ? $restrictions['limit_sim'] : 0, FALSE, array( 'unlimited' => 0 ) ) );
		if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->bit_nexus_on )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'bit_bypass_paid', $group->bit_bypass_paid ) );
		}
		
		$form->addHeader( 'bandwidth_limits' );
		$form->addMessage( 'bitracker_requires_log' );
		$form->add( new \IPS\Helpers\Form\Number( 'daily_bw', isset( $restrictions['daily_bw'] ) ? $restrictions['daily_bw'] : 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('kb_per_day') ) );
		$form->add( new \IPS\Helpers\Form\Number( 'weekly_bw', isset( $restrictions['weekly_bw'] ) ? $restrictions['weekly_bw'] : 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('kb_per_week') ) );
		$form->add( new \IPS\Helpers\Form\Number( 'monthly_bw', isset( $restrictions['monthly_bw'] ) ? $restrictions['monthly_bw'] : 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('kb_per_month') ) );
		$form->addHeader( 'torrent_limits' );
		$form->addMessage( 'bitracker_requires_log' );
		$form->add( new \IPS\Helpers\Form\Number( 'daily_dl', isset( $restrictions['daily_dl'] ) ? $restrictions['daily_dl'] : 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('bitracker_per_day') ) );
		$form->add( new \IPS\Helpers\Form\Number( 'weekly_dl', isset( $restrictions['weekly_dl'] ) ? $restrictions['weekly_dl'] : 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('bitracker_per_week') ) );
		$form->add( new \IPS\Helpers\Form\Number( 'monthly_dl', isset( $restrictions['monthly_dl'] ) ? $restrictions['monthly_dl'] : 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('bitracker_per_month') ) );

	}
	
	/**
	 * Save
	 *
	 * @param	array				$values	Values from form
	 * @param	\IPS\Member\Group	$group	The group
	 * @return	void
	 */
	public function save( $values, &$group )
	{
		$group->bit_view_approvers = $values['bit_view_approvers'];
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
			$group->bit_bypass_revision = $values['bit_bypass_revision'];
		}
		$group->bit_view_bitracker = $values['bit_view_bitracker'];
		$group->bit_throttling = $values['bit_throttling'];
		$group->bit_wait_period = $values['bit_wait_period'];
		$group->bit_linked_torrents = $values['bit_linked_torrents'];
		$group->bit_import_torrents = $values['bit_import_torrents'];
		$group->bit_bulk_submit = $values['bit_bulk_submit'];
		
		if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->bit_nexus_on )
		{
			if( $group->g_id != \IPS\Settings::i()->guest_group )
			{
				$group->bit_add_paid = $values['bit_add_paid'];
			}
			$group->bit_bypass_paid = $values['bit_bypass_paid'];
			$group->bit_paid_restrictions = $values['bit_paid_restrictions'];
		}
		
		$restrictions = array();
		foreach ( array( 'limit_sim', 'daily_bw', 'weekly_bw', 'monthly_bw', 'daily_dl', 'weekly_dl', 'monthly_dl' ) as $k )
		{
			$restrictions[ $k ] = $values[ $k ];
		}
		
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
			$restrictions['min_posts'] = $values['min_posts'];
		}
		
		$group->bit_restrictions = json_encode( $restrictions );
	}
}