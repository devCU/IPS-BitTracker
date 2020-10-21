<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker Announce Settings Controller
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

namespace IPS\bitracker\modules\admin\tracker;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 *  Tracker :: Announce
 */
class _announce extends \IPS\Dispatcher\Controller
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
		\IPS\Dispatcher::i()->checkAcpPermission( 'announce_manage' );
		parent::execute();
	}

	/**
	 * Manage Announce Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'announce_manage' );

		$form = $this->_manageAnnounce();

		if ( $values = $form->values( TRUE ) )
		{
			$this->saveSettingsForm( $form, $values );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__bitracker_settings' );
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('head_tracker_announce');;
		\IPS\Output::i()->output = $form;
}

	/**
	 * Build and return the settings form
	 *
	 * @note	Abstracted to allow third party devs to extend easier
	 * @return	\IPS\Helpers\Form
	 */
	protected function _manageAnnounce()
	{
        $annParam = "announce";
		$announceURL = 	\IPS\Settings::i()->base_url . $annParam ;

		/* Build Form */
		$form = new \IPS\Helpers\Form;

        /* Form Settings */
		if ( \IPS\Settings::i()->bit_torrents_enable )
		{
			\IPS\Output::i()->error( 'acp_private_error', '2A01', 403, '' );
        } else {
        $form->addHeader( 'head_tracker_announce_configure' );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_announce_enable', \IPS\Settings::i()->bit_announce_enable, FALSE, array( 'togglesOn' => array( 'bit_announce_url', 'bit_announce_scrape_interval' ) ) ) );
        $form->add( new \IPS\Helpers\Form\Text( 'bit_announce_url', $announceURL, FALSE, array( 'disabled' => TRUE ), NULL, NULL, NULL, 'bit_announce_url' ) );
        $form->add( new \IPS\Helpers\Form\Number( 'bit_announce_scrape_interval', \IPS\Settings::i()->bit_announce_scrape_interval, FALSE, array(), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('minutes'), 'bit_announce_scrape_interval' ) );
        }
		/* Save values */
		if ( $values = $form->values() )
		{

			$form->saveAsSettings( $values );

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=bitracker&module=tracker&controller=announce' ), 'saved' );
		}

		return $form;
	}
}