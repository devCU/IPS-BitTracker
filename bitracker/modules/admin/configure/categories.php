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
 * @Updated     08 MAR 2018
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
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
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
	 * RecalculateTorrent Downloads
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
}