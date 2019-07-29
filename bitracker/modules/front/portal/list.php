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
 * @Updated     26 MAR 2018
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

namespace IPS\bitracker\modules\front\bitracker;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Browse Files
 */
class _browse extends \IPS\Dispatcher\Controller
{

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{

		if( \IPS\Settings::i()->bit_breadcrumb_name_enable )
		{
		\IPS\Output::i()->breadcrumb	= array();
		\IPS\Output::i()->breadcrumb['module'] = array( \IPS\Http\Url::internal( 'app=bitracker&module=submit&controller=main', 'front', 'bitracker' ), \IPS\Settings::i()->bit_breadcrumb_name );
        }
       else
        {
		\IPS\Output::i()->breadcrumb	= array();
		\IPS\Output::i()->breadcrumb['module'] = array( \IPS\Http\Url::internal( 'app=bitracker&module=submit&controller=main', 'front', 'bitracker' ), \IPS\Settings::i()->bit_application_name );
        }

		parent::execute();
}

	/**
	 * Route
	 *
	 * @return	void
	 */
	protected function manage()
	{
		if ( isset( \IPS\Request::i()->currency ) and in_array( \IPS\Request::i()->currency, \IPS\nexus\Money::currencies() ) and isset( \IPS\Request::i()->csrfKey ) and \IPS\Request::i()->csrfKey === \IPS\Session\Front::i()->csrfKey )
		{
			$_SESSION['currency'] = \IPS\Request::i()->currency;
		}
		
		if ( isset( \IPS\Request::i()->id ) )
		{
			try
			{
				$this->_category( \IPS\bitracker\Category::loadAndCheckPerms( \IPS\Request::i()->id, 'read' ) );
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'node_error', '2D175/1', 404, '' );
			}
		}
		else
		{
			$this->_index();
		}
	}
	
	/**
	 * Show Index
	 *
	 * @return	void
	 */
	protected function _index()
	{
		/* Add RSS feed */
		if ( \IPS\Settings::i()->bit_rss )
		{
			\IPS\Output::i()->rssFeeds['bit_rss_title'] = \IPS\Http\Url::internal( 'app=bitracker&module=submit&controller=main&do=rss', 'front', 'bitracker_rss' );
		}
		
		/* Get stuff */
		$featured = (\IPS\Settings::i()->bit_show_featured) ? iterator_to_array(\IPS\bitracker\File::featured(\IPS\Settings::i()->bit_featured_count, '_rand')) : array();

		if ( \IPS\Settings::i()->bit_newest_categories )
		{
			$newestWhere = array( array( 'bitracker_categories.copen=1 and ' . \IPS\Db::i()->in('file_cat', explode( ',', \IPS\Settings::i()->bit_newest_categories ) ) ) );
		}
		else
		{
			$newestWhere = array( array( 'bitracker_categories.copen=1' ) );
		}

        $new = ( \IPS\Settings::i()->bit_show_newest) ? \IPS\bitracker\File::getItemsWithPermission( $newestWhere, NULL, 14, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, TRUE ) : array();

		if (\IPS\Settings::i()->bit_highest_rated_categories )
		{
			$highestWhere = array( array( 'bitracker_categories.copen=1 and ' . \IPS\Db::i()->in('file_cat', explode( ',', \IPS\Settings::i()->bit_highest_rated_categories ) ) ) );
		}
		else
		{
			$highestWhere = array( array( 'bitracker_categories.copen=1' ) );
		}
		$highestWhere[] = array( 'file_rating > ?', 0 );
		$highestRated = ( \IPS\Settings::i()->bit_show_highest_rated ) ? \IPS\bitracker\File::getItemsWithPermission( $highestWhere, 'file_rating DESC, file_reviews DESC', 14, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, TRUE ) : array();

		if (\IPS\Settings::i()->bit_show_most_downloaded_categories )
		{
			$mostDownloadedWhere = array( array( 'bitracker_categories.copen=1 and ' . \IPS\Db::i()->in('file_cat', explode( ',', \IPS\Settings::i()->bit_show_most_downloaded_categories ) ) ) );
		}
		else
		{
			$mostDownloadedWhere = array( array( 'bitracker_categories.copen=1' ) );
		}
		$mostDownloadedWhere[] = array( 'bitracker_categories.copen=1 and file_torrents > ?', 0 );
		$mostDownloaded = ( \IPS\Settings::i()->bit_show_most_downloaded ) ? \IPS\bitracker\File::getItemsWithPermission( $mostDownloadedWhere, 'file_torrents DESC', 14, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, TRUE ) : array();
		
		/* Online User Location */
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=bitracker', 'front', 'bitracker' ), array(), 'loc_bitracker_browsing' );
		
		/* Display */
		\IPS\Output::i()->sidebar['contextual'] = \IPS\Theme::i()->getTemplate( 'browse' )->indexSidebar( \IPS\bitracker\Category::canOnAny('add') );
		\IPS\Output::i()->title		= \IPS\Settings::i()->bit_application_name;
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->index( $featured, $new, $highestRated, $mostDownloaded );
	}
	
	/**
	 * Show Category
	 *
	 * @param	\IPS\bitracker\Category	$category	The category to show
	 * @return	void
	 */
	protected function _category( $category )
	{
		\IPS\Output::i()->sidebar['contextual'] = '';
		
		$_count = \IPS\bitracker\File::getItemsWithPermission( array( array( \IPS\bitracker\File::$databasePrefix . \IPS\bitracker\File::$databaseColumnMap['container'] . '=?', $category->_id ) ), NULL, 1, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, FALSE, FALSE, FALSE, TRUE );

		if( !$_count )
		{
			/* Set breadcrumb */
			if ( $club = $category->club() )
			{
				\IPS\core\FrontNavigation::$clubTabActive = TRUE;
				\IPS\Output::i()->breadcrumb = array();
				\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), \IPS\Member::loggedIn()->language()->addToStack('module__core_clubs') );
				\IPS\Output::i()->breadcrumb[] = array( $club->url(), $club->name );
				\IPS\Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
				
				if ( \IPS\Settings::i()->clubs_header == 'sidebar' )
				{
					\IPS\Output::i()->sidebar['contextual'] = \IPS\Theme::i()->getTemplate( 'clubs', 'core' )->header( $club, $category, 'sidebar' );
				}
			}
			else
			{
				foreach ( $category->parents() as $parent )
				{
					\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
				\IPS\Output::i()->breadcrumb[] = array( NULL, $category->_title );
			}

			/* Show a 'no files' template if there's nothing to display */
			$table = \IPS\Theme::i()->getTemplate( 'browse' )->noFiles( $category );
		}
		else
		{
			/* Build table */
			$table = new \IPS\Helpers\Table\Content( 'IPS\bitracker\File', $category->url(), NULL, $category );
			$table->classes = array( 'ipsDataList_large' );
			$table->sortOptions = array_merge( $table->sortOptions, array( 'file_torrents' => 'file_torrents' ) );

			if ( !$category->bitoptions['reviews_bitrack'] )
			{
				unset( $table->sortOptions['num_reviews'] );
			}

			if ( !$category->bitoptions['comments'] )
			{
				unset( $table->sortOptions['last_comment'] );
				unset( $table->sortOptions['num_comments'] );
			}

			if ( $table->sortBy === 'bitracker_torrents.file_title' )
			{
				$table->sortDirection = 'asc';
			}
			
			if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->bit_nexus_on )
			{
				$table->filters = array(
					'file_free'	=> "( ( file_cost='' OR file_cost IS NULL ) AND ( file_nexus='' OR file_nexus IS NULL ) )",
					'file_paid'	=> "( file_cost<>'' OR file_nexus>0 )",
				);
			}
			$table->title = \IPS\Member::loggedIn()->language()->pluralize(  \IPS\Member::loggedIn()->language()->get('bitrack_file_count'), array( $_count ) );
		}

		/* Online User Location */
		$permissions = $category->permissions();
		\IPS\Session::i()->setLocation( $category->url(), explode( ",", $permissions['perm_view'] ), 'loc_bitracker_viewing_category', array( "bitracker_category_{$category->id}" => TRUE ) );
				
		/* Output */
		\IPS\Output::i()->title		= $category->_title;

		\IPS\Output::i()->contextualSearchOptions[ \IPS\Member::loggedIn()->language()->addToStack( 'search_contextual_item_bitracker_categories' ) ] = array( 'type' => 'bitracker_torrent', 'nodes' => $category->_id );
		\IPS\Output::i()->sidebar['contextual'] .= \IPS\Theme::i()->getTemplate( 'browse' )->indexSidebar( \IPS\bitracker\Category::canOnAny('add'), $category );
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->category( $category, (string) $table );
	}

	/**
	 * Show a category listing
	 *
	 * @return	void
	 */
	protected function categories()
	{
		/* Online User Location */
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=bitracker&module=submit&controller=main&do=categories', 'front', 'bitracker_categories' ), array(), 'loc_bitracker_browsing_categories' );
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('bitracker_categories_pagetitle');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->categories();
	}
	
	/**
	 * Latest Files RSS
	 *
	 * @return	void
	 */
	protected function rss()
	{
		if( !\IPS\Settings::i()->bit_rss )
		{
			\IPS\Output::i()->error( 'rss_offline', '2D175/2', 403, 'rss_offline_admin' );
		}

		$document = \IPS\Xml\Rss::newDocument( \IPS\Http\Url::internal( 'app=bitracker&module=submit&controller=main', 'front', 'bitracker' ), \IPS\Member::loggedIn()->language()->get('bit_rss_title'), \IPS\Member::loggedIn()->language()->get('bit_rss_title') );
		
		foreach ( \IPS\bitracker\File::getItemsWithPermission() as $file )
		{
			$document->addItem( $file->name, $file->url(), $file->desc, \IPS\DateTime::ts( $file->updated ), $file->id );
		}
		
		/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
		\IPS\Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
	}
}