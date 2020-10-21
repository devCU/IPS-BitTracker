<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker Security Settings Controller
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
 *  Tracker :: Security
 */
class _security extends \IPS\Dispatcher\Controller
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
		\IPS\Dispatcher::i()->checkAcpPermission( 'security_manage' );
		parent::execute();
	}

	/**
	 * Manage Security Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'security_manage' );

		$form = $this->_manageSecurity();

		if ( $values = $form->values( TRUE ) )
		{
			$this->saveSettingsForm( $form, $values );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__bitracker_settings' );
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('head_tracker_security');;
		\IPS\Output::i()->output = $form;
}

	/**
	 * Build and return the settings form
	 *
	 * @note	Abstracted to allow third party devs to extend easier
	 * @return	\IPS\Helpers\Form
	 */
	protected function _manageSecurity()
	{
		/* Build Form */
		$form = new \IPS\Helpers\Form;

        /* Form Settings */
		if ( \IPS\Settings::i()->bit_torrents_enable )
		{
			\IPS\Output::i()->error( 'acp_private_error', '2S01', 403, '' );
        } else {
        $form->addHeader( 'head_tracker_security_configure' );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_security_forcessl', \IPS\Settings::i()->bit_security_forcessl, FALSE, array(), NULL, NULL, NULL, 'bit_security_forcessl' ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_security_permkey', \IPS\Settings::i()->bit_security_permkey, FALSE, array(), NULL, NULL, NULL, 'bit_security_permkey' ) );

        $form->addHeader( 'head_tracker_security_ipaccess' );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_security_ipenable', \IPS\Settings::i()->bit_security_ipenable, FALSE, array( 'togglesOn' => array( 'bit_security_ipcount', 'bit_security_bindip', 'bit_security_bindip_rule' ) ) ) );
 		$form->add( new \IPS\Helpers\Form\Radio( 'bit_security_ipcount', \IPS\Settings::i()->bit_filter_ipcount, FALSE, array(
			'options'	=> array(
				1	=> 'bit_security_singleip',
				2	=> 'bit_security_doubleip',
				3	=> 'bit_security_tripleip',
			)
		), NULL, NULL, NULL, 'bit_security_ipcount' ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_security_bindip', \IPS\Settings::i()->bit_security_bindip, FALSE, array( 'togglesOn' => array( 'bit_security_bindip_' ) ) ) );
 		$form->add( new \IPS\Helpers\Form\Radio( 'bit_security_bindip_rule', \IPS\Settings::i()->bit_security_bindip_rule, FALSE, array(
			'options'	=> array(
				1	=> 'bit_security_bindip_rule_force',
				2	=> 'bit_security_bindip_rule_optional',
			)
		), NULL, NULL, NULL, 'bit_security_bindip_rule' ) );

        $form->addHeader( 'head_tracker_security_seedbox' );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_security_seedboxenable', \IPS\Settings::i()->bit_security_seedboxenable, FALSE, array( 'togglesOn' => array( 'bit_security_seedboxip', 'bit_security_filter_option', 'bit_filter_sbblack_action', 'bit_filter_all_action', 'bit_filter_sbwhite_action' ) ) ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_security_seedboxip', \IPS\Settings::i()->bit_security_seedboxip, FALSE, array(), NULL, NULL, NULL, 'bit_security_seedboxip' ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'bit_security_filter_option', \IPS\Settings::i()->bit_security_filter_option, FALSE, array(
			'options' => array(
				'none' => 'bit_security_none',
				'black' => 'bit_security_blacklist',
				'white' => "bit_security_whitelist" ),
			'toggles' => array(
				'black'	=> array( 'bit_security_sbblacklist', 'bit_filter_sbblack_action' ),
				'white'	=> array( 'bit_security_sbwhitelist', 'bit_filter_sbwhite_action' ),
				'none'		=> array( 'bit_filter_all_action' ),
			)
		), NULL, NULL, NULL, 'bit_security_filter_option' ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'bit_security_sbwhitelist', \IPS\Settings::i()->bit_security_sbwhitelist ? explode( ",", \IPS\Settings::i()->bit_security_sbwhitelist ) : array(), FALSE, array(), NULL, NULL, NULL, 'bit_security_sbwhitelist' ) );
 		$form->add( new \IPS\Helpers\Form\Stack( 'bit_security_sbblacklist', \IPS\Settings::i()->bit_security_sbblacklist ? explode( ",", \IPS\Settings::i()->bit_security_sbblacklist ) : array(), TRUE, array(), NULL, NULL, NULL, 'bit_security_sbblacklist' ) );
 		$form->add( new \IPS\Helpers\Form\Radio( 'bit_filter_sbblack_action', \IPS\Settings::i()->bit_filter_sbblack_action, FALSE, array(
	 		'options'		=> array(
		 		'block'			=> 'bit_filter_sbblock',
		 		'moderate'		=> 'bit_filter_sbmoderate'
	 		),
	 		'descriptions'	=> array(
		 		'block'			=> 'bit_filter_sbblock_desc',
		 		'moderate'		=> 'bit_filter_sbmoderate_desc'
	 		)
 		), NULL, NULL, NULL, 'bit_filter_sbblack_action' ) );

 		$form->add( new \IPS\Helpers\Form\Radio( 'bit_filter_sbwhite_action', \IPS\Settings::i()->bit_filter_sbwhite_action, FALSE, array(
	 		'options'		=> array(
		 		'open'			=> 'bit_filter_sbwhite_open',
		 		'moderate'		=> 'bit_filter_sbwhite_moderate'
	 		),
	 		'description'	=> array(
		 		'open'			=> 'bit_filter_sbwhite_open_desc',
		 		'moderate'		=> 'bit_filter_sbwhite_moderate_desc'
	 		)
 		), NULL, NULL, NULL, 'bit_filter_sbwhite_action' ) );
 		
 		$form->add( new \IPS\Helpers\Form\Radio( 'bit_filter_all_action', \IPS\Settings::i()->bit_filter_all_action, FALSE, array(
	 		'options'		=> array(
		 		'open'			=> 'bit_filter_all_open',
		 		'moderate'		=> 'bit_filter_all_moderate'
	 		),
	 		'description'	=> array(
		 		'open'			=> 'bit_filter_all_open_desc',
		 		'moderate'		=> 'bit_filter_all_moderate_desc'
	 		)
 		), NULL, NULL, NULL, 'bit_filter_all_action' ) );
        }
		/* Save values */
		if ( $values = $form->values() )
		{

            $values['bit_security_sbwhitelist'] = implode( ",", $values['bit_security_sbwhitelist'] );
			$values['bit_security_sbblacklist'] = implode( ",", $values['bit_security_sbblacklist'] );
			$form->saveAsSettings( $values );

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=bitracker&module=tracker&controller=security' ), 'saved' );
		}

		return $form;
	}
}