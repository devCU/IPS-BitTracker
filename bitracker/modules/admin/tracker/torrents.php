<?php
/**
 * @brief       BitTracker Application Class
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	BitTracker
 * @version     2.5.0 Stable
 * @source      https://github.com/devCU/IPS-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     21 OCT 2020
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


namespace IPS\bitracker\modules\admin\tracker;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 *  Tracker :: Torrent
 */
class _torrents extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'torrents_manage' );
		parent::execute();
	}

	/**
	 * Manage Torrent Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'torrents_manage' );

		$form = $this->_manageTorrents();

		if ( $values = $form->values( TRUE ) )
		{
			$this->saveSettingsForm( $form, $values );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__bitracker_settings' );
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('head_tracker_torrents');;
		\IPS\Output::i()->output = $form;
}

	/**
	 * Build and return the settings form
	 *
	 * @note	Abstracted to allow third party devs to extend easier
	 * @return	\IPS\Helpers\Form
	 */
	protected function _manageTorrents()
	{
		/* Build Form */
		$form = new \IPS\Helpers\Form;

        /* Form Settings */
		if ( \IPS\Settings::i()->bit_announce_enable )
		{
			\IPS\Output::i()->error( 'acp_public_error', '2T01', 403, '' );
        } else {
        $form->addHeader( 'head_tracker_torrent_configure' );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_torrents_enable', \IPS\Settings::i()->bit_torrents_enable, FALSE, array( 'togglesOn' => array( 'bit_tracker_filter_option', 'bit_filter_black_action', 'bit_filter_any_action', 'bit_filter_white_action' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'bit_tracker_filter_option', \IPS\Settings::i()->bit_tracker_filter_option, FALSE, array(
			'options' => array(
				'none' => 'bit_none',
				'black' => 'bit_blacklist',
				'white' => "bit_whitelist" ),
			'toggles' => array(
				'black'	=> array( 'bit_tracker_blacklist', 'bit_filter_black_action' ),
				'white'	=> array( 'bit_tracker_whitelist', 'bit_filter_white_action' ),
				'none'		=> array( 'bit_filter_any_action' ),
			)
		), NULL, NULL, NULL, 'bit_tracker_filter_option' ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'bit_tracker_whitelist', \IPS\Settings::i()->bit_tracker_whitelist ? explode( ",", \IPS\Settings::i()->bit_tracker_whitelist ) : array(), FALSE, array(), NULL, NULL, NULL, 'bit_tracker_whitelist' ) );
 		$form->add( new \IPS\Helpers\Form\Stack( 'bit_tracker_blacklist', \IPS\Settings::i()->bit_tracker_blacklist ? explode( ",", \IPS\Settings::i()->bit_tracker_blacklist ) : array(), TRUE, array(), NULL, NULL, NULL, 'bit_tracker_blacklist' ) );
 		$form->add( new \IPS\Helpers\Form\Radio( 'bit_filter_black_action', \IPS\Settings::i()->bit_filter_black_action, FALSE, array(
	 		'options'		=> array(
		 		'block'			=> 'bit_filter_block',
		 		'moderate'		=> 'bit_filter_moderate'
	 		),
	 		'descriptions'	=> array(
		 		'block'			=> 'bit_filter_block_desc',
		 		'moderate'		=> 'bit_filter_moderate_desc'
	 		)
 		), NULL, NULL, NULL, 'bit_filter_black_action' ) );

 		$form->add( new \IPS\Helpers\Form\Radio( 'bit_filter_white_action', \IPS\Settings::i()->bit_filter_white_action, FALSE, array(
	 		'options'		=> array(
		 		'open'			=> 'bit_filter_white_open',
		 		'moderate'		=> 'bit_filter_white_moderate'
	 		),
	 		'description'	=> array(
		 		'open'			=> 'bit_filter_white_open_desc',
		 		'moderate'		=> 'bit_filter_white_moderate_desc'
	 		)
 		), NULL, NULL, NULL, 'bit_filter_white_action' ) );
 		
 		$form->add( new \IPS\Helpers\Form\Radio( 'bit_filter_any_action', \IPS\Settings::i()->bit_filter_any_action, FALSE, array(
	 		'options'		=> array(
		 		'open'			=> 'bit_filter_any_open',
		 		'moderate'		=> 'bit_filter_any_moderate'
	 		),
	 		'description'	=> array(
		 		'open'			=> 'bit_filter_any_open_desc',
		 		'moderate'		=> 'bit_filter_any_moderate_desc'
	 		)
 		), NULL, NULL, NULL, 'bit_filter_any_action' ) );
        }

		/* Save values */
		if ( $values = $form->values() )
		{
            $values['bit_tracker_whitelist'] = implode( ",", $values['bit_tracker_whitelist'] );
			$values['bit_tracker_blacklist'] = implode( ",", $values['bit_tracker_blacklist'] );
			$form->saveAsSettings( $values );


			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=bitracker&module=tracker&controller=torrents' ), 'saved' );
		}

		return $form;
	}
}