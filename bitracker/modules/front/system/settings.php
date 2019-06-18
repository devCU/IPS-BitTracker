<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker User CP Controller
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4x
 * @subpackage	BitTracker
 * @version     2.0.0 RC 2
 * @source      https://github.com/GaalexxC/IPS-4.4-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     17 JUN 2019
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
 
namespace IPS\bitracker\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * User CP Controller
 */
class _settings extends \IPS\Dispatcher\Controller
{

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		/* Only logged in members */
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_module_permission_guest', '2C122/1', 403, '' );
		}

		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		parent::execute();
	}

	/**
	 * Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Work out output */
		$area = \IPS\Request::i()->area ?: 'overview';
		if ( method_exists( $this, "_{$area}" ) )
		{
			$output = \call_user_func( array( $this, "_{$area}" ) );
		}
		
		/* Display */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('system_bitracker_settings');
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack('system_bitracker_settings') );
		if ( !\IPS\Request::i()->isAjax() )
		{
			if ( \IPS\Request::i()->service )
			{
				$area = "{$area}_" . \IPS\Request::i()->service;
			}
            
            \IPS\Output::i()->cssFiles	= array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/settings.css' ) );
            
            if ( \IPS\Theme::i()->settings['responsive'] )
            {
                \IPS\Output::i()->cssFiles	= array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/settings_responsive.css' ) );
            }
            
            if ( $output )
            {
				\IPS\Output::i()->output .= $this->_wrapOutputInTemplate( $area, $output );
			}
		}
		elseif ( $output )
		{
			\IPS\Output::i()->output .= $output;
		}
	}

	/**
	 * Wrap output in template
	 *
	 * @param	string	$area	Active area
	 * @param	string	$output	Output
	 * @return	string
	 */
	protected function _wrapOutputInTemplate( $area, $output )
	{
		/* What can we do? */

				
		/* Return */
		return \IPS\Theme::i()->getTemplate( 'system' )->settings( $area, $output );
	}

	/**
	 * Build and return the settings form: Overview
	 *
	 * @note	Abstracted to allow third party devs to extend easier
	 * @return	\IPS\Helpers\Form
	 */
	protected function _overview()
	{

			$announceURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] ; 

	if ( !empty($_SERVER['HTTP_CLIENT_IP'] ))   
	   {
    $live_ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	   }
	elseif ( !empty($_SERVER['HTTP_X_FORWARDED_FOR'] ))
	   {
	   $live_ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	   }
	else
	   {
	   $live_ipaddress = $_SERVER['REMOTE_ADDR'];

  }

		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_settings.js', 'bitracker', 'front' ) );
		return \IPS\Theme::i()->getTemplate( 'system' )->settingsOverview( $live_ipaddress, $announceURL );
	}

	/**
	 * Build and return the settings form: UpdatetIP
	 *
	 * @note	Abstracted to allow third party devs to extend easier
	 * @return	\IPS\Helpers\Form
	 */
	protected function _updateip()
	{

	if ( !empty($_SERVER['HTTP_CLIENT_IP'] ))   
	   {
    $live_ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	   }
	elseif ( !empty($_SERVER['HTTP_X_FORWARDED_FOR'] ))  
	   {
	   $live_ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	   }
	else
	   {
    $live_ipaddress = $_SERVER['REMOTE_ADDR'];
  }

            $where = array( array( 'member_id=?', \IPS\Member::loggedIn()->member_id ) );
	    	\IPS\Db::i()->update( 'core_members', array( 'ip_address' => $live_ipaddress ), $where);

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=bitracker&module=system&controller=settings&area=overview', 'front', 'settings' ), 'system_ipaddress_updated' );
	}

	/**
	 * Build and return the settings form: Configure
	 *
	 * @note	Abstracted to allow third party devs to extend easier
	 * @return	\IPS\Helpers\Form
	 */
	protected function _configure()
	{
		/* Build Form */
		$form = new \IPS\Helpers\Form;
		$form->class = 'ipsForm_collapseTablet';
			if ( \IPS\Settings::i()->bit_security_ipenable AND \IPS\Settings::i()->bit_security_ipcount >= 2 )
			{
		$form->add( new \IPS\Helpers\Form\Text( 'bit_ip_address2', \IPS\Member::loggedIn()->bit_ip_address2, FALSE) );
			}
			if ( \IPS\Settings::i()->bit_security_ipenable AND \IPS\Settings::i()->bit_security_ipcount == 3 )
			{
		$form->add( new \IPS\Helpers\Form\Text( 'bit_ip_address3', \IPS\Member::loggedIn()->bit_ip_address3, FALSE) );
			}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			
			\IPS\Member::loggedIn()->bit_ip_address2 = $values['bit_ip_address2'];
			\IPS\Member::loggedIn()->bit_ip_address3 = $values['bit_ip_address3'];
			
			\IPS\Member::loggedIn()->save();
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=bitracker&module=system&controller=settings&area=configure', 'front', 'settings' ), 'system_ipaddress_updated' );
	   }

		return \IPS\Theme::i()->getTemplate( 'system' )->settingsConfigure( $form );
   }

	/**
	 * Build and return the settings form: Security Passkey
	 *
	 * @note	Abstracted to allow third party devs to extend easier
	 * @return	\IPS\Helpers\Form
	 */

	protected function _getPasskey()
	{
        $length = 32;
        $chars = str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
		$stringToHash = \IPS\Member::loggedIn()->ip_address . \IPS\Member::loggedIn()->joined . \IPS\Member::loggedIn()->member_pass_hash . $chars;
		$hash = md5( $stringToHash, $length);
        $permkey = bin2hex($hash);

            $where = array( array( 'member_id=?', \IPS\Member::loggedIn()->member_id ) );
            \IPS\Db::i()->update( 'core_members', array( 'bit_perm_key' => $permkey ), $where);


			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=bitracker&module=system&controller=settings&area=security', 'front', 'settings' ), 'system_passkey_updated' );
	}

	/**
	 * Build and return the settings form: Security
	 *
	 * @note	Abstracted to allow third party devs to extend easier
	 * @return	\IPS\Helpers\Form
	 */
	protected function _security()
	{
		/* Build Form */
		$form = new \IPS\Helpers\Form;
		$form->class = 'ipsForm_collapseTablet';
			if ( \IPS\Settings::i()->bit_security_bindip AND \IPS\Settings::i()->bit_security_bindip_rule == 2 )
			{
		$form->add( new \IPS\Helpers\Form\YesNo( 'bit_member_ipbind', \IPS\Member::loggedIn()->bit_member_ipbind, FALSE) );
			}
			else
			{
		$form->add( new \IPS\Helpers\Form\YesNo( 'bit_member_ipbind', FALSE, FALSE, array( 'disabled' => TRUE ) ) );
			}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			
			\IPS\Member::loggedIn()->bit_member_ipbind = $values['bit_member_ipbind'];

			
			\IPS\Member::loggedIn()->save();
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=bitracker&module=system&controller=settings&area=security', 'front', 'settings' ), 'bit_security_updated' );
	   }

		return \IPS\Theme::i()->getTemplate( 'system' )->settingsSecurity( $form );
	}

	/**
	 * Build and return the settings form: Privacy
	 *
	 * @note	Abstracted to allow third party devs to extend easier
	 * @return	\IPS\Helpers\Form
	 */
	protected function _privacy()
	{

		/* Build Form */
		$form = new \IPS\Helpers\Form;
		$form->class = 'ipsForm_collapseTablet';
			if ( \IPS\Settings::i()->bit_profile_private_enabled )
			{
		$form->add( new \IPS\Helpers\Form\YesNo( 'bit_profile_private', \IPS\Member::loggedIn()->bit_profile_private, FALSE, array('togglesOn' => array( 'bit_profile_private_followers' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'bit_profile_private_followers', \IPS\Member::loggedIn()->bit_profile_private_followers, FALSE, array(), NULL, NULL, NULL, 'bit_profile_private_followers' ) );

			}
			else
			{
		$form->add( new \IPS\Helpers\Form\YesNo( 'bit_profile_private', FALSE, FALSE, array( 'disabled' => TRUE ) ) );
			}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			
			\IPS\Member::loggedIn()->bit_profile_private = $values['bit_profile_private'];
			\IPS\Member::loggedIn()->bit_profile_private_followers = $values['bit_profile_private_followers'];
			
			\IPS\Member::loggedIn()->save();
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=bitracker&module=system&controller=settings&area=privacy', 'front', 'settings' ), 'bit_profile_private_changed' );
	   }

		return \IPS\Theme::i()->getTemplate( 'system' )->settingsPrivacy( $form );
	}
}