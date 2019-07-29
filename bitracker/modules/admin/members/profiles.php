<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker Profile Options
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

namespace IPS\bitracker\modules\admin\members;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Profile Options
 */
class _profiles extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'profiles_manage' );
		parent::execute();
	}
    
	/**
	 * Manage Profile Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'profiles_manage' );

		$form = $this->_manageProfile();

		if ( $values = $form->values( TRUE ) )
		{
			$this->saveSettingsForm( $form, $values );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__bitracker_settings' );
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('head_profile_options');;
		\IPS\Output::i()->output = $form;
}

	/**
	 * Build and return the settings form
	 *
	 * @note	Abstracted to allow third party devs to extend easier
	 * @return	\IPS\Helpers\Form
	 */
	protected function _manageProfile()
	{
		/* Build Form */
		$form = new \IPS\Helpers\Form;

        /* Form Settings */
        $form->addTab( 'general_settings' );
        $form->addHeader( 'general_profile_settings' );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_profile_enable', \IPS\Settings::i()->bit_profile_enable, FALSE, array( 'togglesOn' => array( 'bit_profile_name_enable' ) ), NULL, NULL, NULL, 'bit_profile_enable' ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_profile_name_enable', \IPS\Settings::i()->bit_profile_name_enable, FALSE, array( 'togglesOn' => array( 'bit_profile_name' ) ), NULL, NULL, NULL, 'bit_profile_name_enable' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'bit_profile_name', \IPS\Settings::i()->bit_profile_name, FALSE, array(), NULL, NULL, NULL, 'bit_profile_name' ) );

        $form->addTab( 'advanced_settings' );  
        $form->addHeader( 'advanced_profile_settings' );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_profile_adv_enable', \IPS\Settings::i()->bit_profile_adv_enable, FALSE, array( 'togglesOn' => array( 'bit_profile_private_enabled' ) ), NULL, NULL, NULL, 'bit_profile_adv_enable' ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_profile_private_enabled', \IPS\Settings::i()->bit_profile_private_enabled, FALSE, array(), NULL, NULL, NULL, 'bit_profile_private_enabled' ) );

		/* Save values */
		if ( $values = $form->values() )
		{
            
			$form->saveAsSettings( $values );

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=bitracker&module=members&controller=profiles' ), 'saved' );
		}

		return $form;
	}
}