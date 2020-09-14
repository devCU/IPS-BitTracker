<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker Categories Application Class
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4.10
 * @subpackage	BitTracker
 * @version     2.2.0 Final
 * @source      https://github.com/GaalexxC/IPS-4.4-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     05 SEP 2020
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

namespace IPS\bitracker\modules\admin\configure;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Categories
 */
class _categories extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\bitracker\Category';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'categories_manage' );
		parent::execute();
	}
	
	/**
	 * Recalculate Torrent Downloads
	 *
	 * @return	void
	 */
	protected function recountTorrents()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'categories_recount_bitracker' );		
	
		try
		{
			$category = \IPS\bitracker\Category::load( \IPS\Request::i()->id );
			
			\IPS\Db::i()->update( 'bitracker_torrents', array( 'file_torrents' => \IPS\Db::i()->select( 'COUNT(*)', 'bitracker_downloads', array( 'dfid=file_id' ) ) ), array( 'file_cat=?', $category->id ) );
			\IPS\Session::i()->log( 'acplogs__bitracker_recount_torrents', array( $category->_title => FALSE ) );
		
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=bitracker&module=configure&controller=categories&do=form&id=" . \IPS\Request::i()->id ), 'clog_recount_done' );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2D180/1', 404, '' );
		}
	}

	/**
	 * Show the add/edit form
	 *
	 * @return void
	 */
	protected function form()
	{
		parent::form();

		if ( \IPS\Request::i()->id )
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('edit_category')  . ': ' . \IPS\Output::i()->title;
		}
		else
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('add_category');
		}
	}

	/**
	 * Rebuild the Downloads Files Topics
	 *
	 * @return void
	 */
	public function rebuildTopicContent()
	{
		$class = $this->nodeClass;
		\IPS\Task::queue( 'core', 'ResyncTopicContent', array( 'class' => $class, 'categoryId' => \IPS\Request::i()->id ), 3, array( 'categoryId' ) );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=bitracker&module=configure&controller=categories&do=form&id=' . \IPS\Request::i()->id ), \IPS\Member::loggedIn()->language()->addToStack('rebuilding_stuff', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( 'category_forums_integration' ) ) ) ) );
	}
}