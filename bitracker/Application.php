<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
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
 * @Updated     20 OCT 2020
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

namespace IPS\bitracker;

/**
 * BitTracker Application Class
 */
class _Application extends \IPS\Application
{
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init()
	{
		/* Handle RSS requests */
		if ( \IPS\Request::i()->module == 'bitracker' and \IPS\Request::i()->controller == 'main' and \IPS\Request::i()->do == 'rss' )
		{
			$member = NULL;
			if( \IPS\Request::i()->member AND \IPS\Request::i()->key )
			{
				$member = \IPS\Member::load( \IPS\Request::i()->member );
				if( !\IPS\Login::compareHashes( md5( ( $member->members_pass_hash ?: $member->email ) . $member->members_pass_salt ), (string) \IPS\Request::i()->key ) )
				{
					$member = NULL;
				}
			}

			$this->sendDownloadsRss( $member ?? new \IPS\Member );

			if( !\IPS\Member::loggedIn()->group['g_view_board'] )
			{
				\IPS\Output::i()->error( 'node_error', '2D220/1', 404, '' );
			}
		}
		
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'bitracker.css' ) );

		if ( \IPS\Theme::i()->settings['responsive'] )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'bitracker_responsive.css', 'bitracker', 'front' ) );
		}
	}

	/**
	 * Send the latest file RSS feed for the indicated member
	 *
	 * @param	\IPS\Member		$member		Member
	 * @return	void
	 */
	protected function sendBitrackerRss( $member )
	{
		if( !\IPS\Settings::i()->bit_rss )
		{
			\IPS\Output::i()->error( 'rss_offline', '2D175/2', 403, 'rss_offline_admin' );
		}

		$document = \IPS\Xml\Rss::newDocument( \IPS\Http\Url::internal( 'app=bitracker&module=portal&controller=main', 'front', 'bitracker' ), $member->language()->get(bit_rss_title'), $member->language()->get('bit_rss_title') );
		
		foreach ( \IPS\bitracker\File::getItemsWithPermission( array(), NULL, 10, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, $member ) as $file )
		{
			$document->addItem( $file->name, $file->url(), $file->desc, \IPS\DateTime::ts( $file->updated ), $file->id );
		}
		
		/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
		\IPS\Output::i()->sendOutput( $document->asXML(), 200, 'text/xml', array(), TRUE );
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return 'cloud';
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation()
	{
		return array(
			'rootTabs'	=> array(),
			'browseTabs'	=> array( array( 'key' => 'Bitracker' ) ),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}
	
	/**
	 * Perform some legacy URL parameter conversions
	 *
	 * @return	void
	 */
	public function convertLegacyParameters()
	{
		if ( isset( \IPS\Request::i()->showfile ) AND \is_numeric( \IPS\Request::i()->showfile ) )
		{
			try
			{
				$file = \IPS\bitracker\File::loadAndCheckPerms( \IPS\Request::i()->showfile );
				
				\IPS\Output::i()->redirect( $file->url() );
			}
			catch( \OutOfRangeException $e ) {}
		}

		if ( isset( \IPS\Request::i()->module ) AND \IPS\Request::i()->module == 'post' AND isset( \IPS\Request::i()->controller ) AND \IPS\Request::i()->controller == 'submit' )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=bitracker&module=submit&controller=submit", "front", "torrents_submit" ) );
		}
	}
	
	/**
	 * Get any settings that are uploads
	 *
	 * @return	array
	 */
	public function uploadSettings()
	{
		/* Apps can overload this */
		return array( 'bit_watermarkpath' );
	}

	/**
	 * Return an array of specific links to add to the sitemap
	 *
	 * @return	array
	 */
	public function sitemapLinks()
	{
		return array(
			\IPS\Http\Url::internal( 'app=bitracker&module=portal&controller=main&do=categories', 'front', 'bitracker_categories' ),
			\IPS\Http\Url::internal( 'app=bitracker&module=portal&controller=main', 'front', 'bitracker' ),
		);
	}
}