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
 * @Updated     16 MAR 2018
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
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * sites
 */
class _sites extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\bitracker\Sites';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'sites_manage' );
		parent::execute();
	}
    
	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */    
	public function _getRootButtons()
	{
	    /* Get original buttons */
		$buttons = parent::_getRootButtons();

        /* Add rebuild button */
		$buttons['rebuild']	= array(
			'icon'	=> 'cog',
			'title'	=> 'rebuild_bitracker',
			'link'	=> \IPS\Http\Url::internal( "app=bitracker&module=settings&controller=sites&do=rebuild" ),
			'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('rebuild_bitracker') )
		);        
            
		return $buttons;
	}  
    
	/**
	 * Rebuild bitracker
	 * 
	 */
	public function rebuild()
	{
		\IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
			\IPS\Http\Url::internal( 'app=bitracker&module=settings&controller=sites&do=rebuild' ),
			function( $data )
			{
				/* First import */
				if ( ! is_array( $data ) )
				{
					/* Start import */
					$data = array(
							'lastId'     => 0,
							'processing' => true,
							'total'	     => \IPS\Db::i()->select( 'COUNT(*)', 'bitracker_torrents_records', array( array( 'record_type=?', 'media_url' ) ) )->first(),
							'done'       => 0
					);
						
					return array( $data, \IPS\Member::loggedIn()->language()->addToStack('torrent_rebuild_processing') );
				}
	
				/* Rebuild and then finish */
				if ( $data['processing'] )
				{
					try
					{
						$episodeID = \IPS\Db::i()->select( 'record_id', 'bitracker_torrents_records', array( 'record_id > ? AND record_type=?', intval( $data['lastId'] ), 'media_url' ), 'record_id ASC', array( 0, 1 ) )->first();
						
						if ( ! empty( $torrentID ) )
						{
							$torrent = \IPS\bitracker\File::load( $torrentID );
							$torrent->rebuildTorrent();
							
							$data['lastId'] = $torrentID;
							$data['done']++;
						}
						else
						{
							$data['lastId']     = 0;
							$data['processing'] = false;
						}
						
						return array( $data, \IPS\Member::loggedIn()->language()->addToStack('episode_rebuild_processing'), 100 / $data['total'] * $data['done'] );
					}
					catch( \UnderflowException $ex )
					{
						return null;
					}
				}
				else
				{
					/* Done? */
					return null;
				}
			},
			function()
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=bitracker&module=settings&controller=sites' ), 'completed' );
			}
		);
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('rebuild_bitracker');
		
		/* Output */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( \IPS\Output::i()->output ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
		}
		else
		{
			\IPS\Output::i()->buildMetaTags();
			\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( \IPS\Output::i()->title, \IPS\Output::i()->output, array( 'app' => \IPS\Dispatcher::i()->application->directory, 'module' => \IPS\Dispatcher::i()->module->key, 'controller' => \IPS\Dispatcher::i()->controller ) ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
		}
		
		return;
	}      
}