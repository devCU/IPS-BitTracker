<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker General Settings
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

namespace IPS\bitracker\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Settings
 */
class _general extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static $csrfProtected = TRUE;
	
	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'settings_manage' );

		$form = $this->getForm();

		if ( $values = $form->values( TRUE ) )
		{
			$this->saveSettingsForm( $form, $values );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__bitracker_settings' );
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('general_settings');
		\IPS\Output::i()->output = $form;
	}

	/**
	 * Build and return the settings form
	 *
	 * @note	Abstracted to allow third party devs to extend easier
	 * @return	\IPS\Helpers\Form
	 */
	protected function getForm()
	{
		$form = new \IPS\Helpers\Form;

		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_settings.js', 'bitracker', 'admin' ) );
		$form->attributes['data-controller'] = 'bitracker.admin.settings.settings';
		$form->hiddenValues['rebuildWatermarkScreenshots'] = \IPS\Request::i()->rebuildWatermarkScreenshots ?: 0;

        $form->addTab( 'bit_portal_general' );
		$form->addHeader( 'head_portal_layout' );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_view_portal_switch', \IPS\Settings::i()->bit_view_portal_switch, FALSE, array('togglesOn' => array( 'bit_view_table_categories', 'bit_view_table_torrents') ) ) );
        $form->add( new \IPS\Helpers\Form\Number( 'bit_view_table_categories', \IPS\Settings::i()->bit_view_table_categories, FALSE, array(), NULL, NULL, NULL, 'bit_view_table_categories' ) );
        $form->add( new \IPS\Helpers\Form\Number( 'bit_view_table_torrents', \IPS\Settings::i()->bit_view_table_torrents, FALSE, array(), NULL, NULL, NULL, 'bit_view_table_torrents' ) );

        $form->addTab( 'bit_customize_general' );
		$form->addHeader( 'customized_bitracker' );
		$form->add( new \IPS\Helpers\Form\Text( 'bit_application_name', \IPS\Settings::i()->bit_application_name, FALSE, array(), NULL, NULL, NULL, 'bit_application_name' ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_breadcrumb_name_enable', \IPS\Settings::i()->bit_breadcrumb_name_enable, FALSE, array( 'togglesOn' => array( 'bit_breadcrumb_name' ) ), NULL, NULL, NULL, 'bit_breadcrumb_name_enable' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'bit_breadcrumb_name', \IPS\Settings::i()->bit_breadcrumb_name, FALSE, array(), NULL, NULL, NULL, 'bit_breadcrumb_name' ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_cusmenu_name_enable', \IPS\Settings::i()->bit_cusmenu_name_enable, FALSE, array( 'togglesOn' => array( 'bit_cusmenu_name' ) ), NULL, NULL, NULL, 'bit_cusmenu_name_enable' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'bit_cusmenu_name', \IPS\Settings::i()->bit_cusmenu_name, FALSE, array(), NULL, NULL, NULL, 'bit_cusmenu_name' ) );

		$form->addTab( 'bit_feeds_general' );
		$form->addHeader( 'rss_bitracker' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'bit_rss', \IPS\Settings::i()->bit_rss ) );
		$form->addHeader( 'featured_bitracker' );
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_show_featured', \IPS\Settings::i()->bit_show_featured, FALSE, array( 'togglesOn' => array( 'bit_featured_count' ) ) ) );
        $form->add( new \IPS\Helpers\Form\Number( 'bit_featured_count', \IPS\Settings::i()->bit_featured_count, FALSE, array(), NULL, NULL, NULL, 'bit_featured_count' ) );

		$form->addHeader('browse_whats_new');
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_show_newest', \IPS\Settings::i()->bit_show_newest, FALSE, array('togglesOn' => array( 'bit_newest_categories') ) ) );
        $form->add( new \IPS\Helpers\Form\Node( 'bit_newest_categories', ( \IPS\Settings::i()->bit_newest_categories AND \IPS\Settings::i()->bit_newest_categories != 0 ) ? explode( ',', \IPS\Settings::i()->bit_newest_categories ) : 0, FALSE, array(
            'class' => 'IPS\bitracker\Category',
            'zeroVal' => 'any',
            'multiple' => TRUE ), NULL, NULL, NULL, 'bit_newest_categories') );

		$form->addHeader('browse_highest_rated');
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_show_highest_rated', \IPS\Settings::i()->bit_show_highest_rated, FALSE, array( 'togglesOn' => array( 'bit_highest_rated_categories' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Node( 'bit_highest_rated_categories', ( \IPS\Settings::i()->bit_highest_rated_categories AND \IPS\Settings::i()->bit_highest_rated_categories != 0 ) ? explode( ',', \IPS\Settings::i()->bit_highest_rated_categories ) : 0, FALSE, array(
			'class' => 'IPS\bitracker\Category',
			'zeroVal' => 'any',
			'multiple' => TRUE ), NULL, NULL, NULL, 'bit_highest_rated_categories') );

		$form->addHeader('browse_most_downloaded');
        $form->add( new \IPS\Helpers\Form\YesNo( 'bit_show_most_downloaded', \IPS\Settings::i()->bit_show_most_downloaded, FALSE, array( 'togglesOn' => array( 'bit_show_most_downloaded_categories' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Node( 'bit_show_most_downloaded_categories', ( \IPS\Settings::i()->bit_show_most_downloaded_categories AND \IPS\Settings::i()->bit_show_most_downloaded_categories != 0 ) ? explode( ',', \IPS\Settings::i()->bit_show_most_downloaded_categories ) : 0, FALSE, array(
			'class' => 'IPS\bitracker\Category',
			'zeroVal' => 'any',
			'multiple' => TRUE ), NULL, NULL, NULL, 'bit_show_most_downloaded_categories') );


        $form->addTab( 'basic_settings' );
		$form->addHeader('basic_settings');
		$form->add( new \IPS\Helpers\Form\Upload( 'bit_watermarkpath', \IPS\Settings::i()->bit_watermarkpath ? \IPS\File::get( 'core_Theme', \IPS\Settings::i()->bit_watermarkpath ) : NULL, FALSE, array( 'image' => TRUE, 'storageExtension' => 'core_Theme' ) ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'bit_link_blacklist', explode( ',', \IPS\Settings::i()->bit_link_blacklist ), FALSE, array( 'placeholder' => 'example.com' ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'bit_antileech', \IPS\Settings::i()->bit_antileech ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'bit_rss', \IPS\Settings::i()->bit_rss ) );

		if ( \IPS\Application::appIsEnabled( 'nexus' ) )
		{
			$form->addTab( 'paid_file_settings' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'bit_nexus_on', \IPS\Settings::i()->bit_nexus_on, FALSE, array( 'togglesOn' => array( 'bit_nexus_tax', 'bit_nexus_percent', 'bit_nexus_transfee', 'bit_nexus_mincost', 'bit_nexus_gateways', 'bit_nexus_display' ) ) ) );
			$form->add( new \IPS\Helpers\Form\Node( 'bit_nexus_tax', \IPS\Settings::i()->bit_nexus_tax ?:0, FALSE, array( 'class' => '\IPS\nexus\Tax', 'zeroVal' => 'do_not_tax' ), NULL, NULL, NULL, 'bit_nexus_tax' ) );
			$form->add( new \IPS\Helpers\Form\Number( 'bit_nexus_percent', \IPS\Settings::i()->bit_nexus_percent, FALSE, array( 'min' => 0, 'max' => 100 ), NULL, NULL, '%', 'bit_nexus_percent' ) );
			$form->add( new \IPS\nexus\Form\Money( 'bit_nexus_transfee', json_decode( \IPS\Settings::i()->bit_nexus_transfee, TRUE ), FALSE, array(), NULL, NULL, NULL, 'bit_nexus_transfee' ) );
			$form->add( new \IPS\nexus\Form\Money( 'bit_nexus_mincost', json_decode( \IPS\Settings::i()->bit_nexus_mincost, TRUE ), FALSE, array(), NULL, NULL, NULL, 'bit_nexus_mincost' ) );
			$form->add( new \IPS\Helpers\Form\Node( 'bit_nexus_gateways', ( \IPS\Settings::i()->bit_nexus_gateways ) ? explode( ',', \IPS\Settings::i()->bit_nexus_gateways ) : 0, FALSE, array( 'class' => '\IPS\nexus\Gateway', 'zeroVal' => 'no_restriction', 'multiple' => TRUE ), NULL, NULL, NULL, 'bit_nexus_gateways' ) );
			$form->add( new \IPS\Helpers\Form\CheckboxSet( 'bit_nexus_display', explode( ',', \IPS\Settings::i()->bit_nexus_display ), FALSE, array( 'options' => array( 'purchases' => 'bit_purchases', 'bitracker' => 'bitracker' ) ), NULL, NULL, NULL, 'bit_nexus_display' ) );
		}

		return $form;
	}

	/**
	 * Save the settings form
	 *
	 * @param \IPS\Helpers\Form 	$form		The Form Object
	 * @param array 				$values		Values
	 */
	protected function _saveSettingsForm( \IPS\Helpers\Form $form, array $values )
	{
		/* We can't store '' for bit_nexus_display as it will fall back to the default */
		if ( \IPS\Application::appIsEnabled( 'nexus' ) and !$values['bit_nexus_display'] )
		{
			$values['bit_nexus_display'] = 'none';
		}

		$rebuildScreenshots = $values['rebuildWatermarkScreenshots'];

		unset( $values['rebuildWatermarkScreenshots'] );

		$form->saveAsSettings( $values );

		/* Save the form first, then queue the rebuild */
		if( $rebuildScreenshots )
		{
			\IPS\Db::i()->delete( 'core_queue', array( '`app`=? OR `key`=?', 'bitracker', 'RebuildScreenshotWatermarks' ) );

			\IPS\Task::queue( 'bitracker', 'RebuildScreenshotWatermarks', array( ), 5 );
			\IPS\Output::i()->inlineMessage	= \IPS\Member::loggedIn()->language()->addToStack('bitrack_settings_saved_rebuilding');
		}
		else
		{
			\IPS\Output::i()->inlineMessage	= \IPS\Member::loggedIn()->language()->addToStack('saved');
		}
	}
}