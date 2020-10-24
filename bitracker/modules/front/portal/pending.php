<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker 24 OCT 2020
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	BitTracker
 * @version     2.5.0 Stable
 * @source      https://github.com/devCU/IPS-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     24 OCT 2020
 * @Updated     24 OCT 2020
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

namespace IPS\bitracker\modules\front\portal;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Pending Version Controller
 */
class _pending extends \IPS\Content\Controller
{
	/**
	 * [Content\Controller]    Class
	 */
	protected static $contentModel = 'IPS\bitracker\File\PendingVersion';

	/**
	 * @brief	Storage for loaded file
	 */
	protected $file = NULL;

	/**
	 * @brief	Storage for loaded version
	 */
	protected $version = NULL;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		try
		{
			$this->file = \IPS\bitracker\File::load( \IPS\Request::i()->file_id );
			$this->version = \IPS\bitracker\File\PendingVersion::load( \IPS\Request::i()->id );

			if ( !$this->version->canUnhide() )
			{
				\IPS\Output::i()->error( 'node_error', '2D417/1', 404, '' );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			/* The version does not exist, but the file does. Redirect there instead. */
			if( isset( $this->file ) )
			{
				\IPS\Output::i()->redirect( $this->file->url() );
			}

			\IPS\Output::i()->error( 'node_error', '2D417/2', 404, '' );
		}

		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_view.js', 'bitracker', 'front' ) );

		parent::execute();
	}

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function manage()
	{
		/* Display */
		\IPS\Output::i()->title = $this->file->name;

		$container = $this->file->container();
		foreach ( $container->parents() as $parent )
		{
			\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
		}
		\IPS\Output::i()->breadcrumb[] = array( $container->url(), $container->_title );

		\IPS\Output::i()->breadcrumb[] = array( $this->file->url(), $this->file->name );
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack('pending_version') );

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'view' )->pendingView( $this->file, $this->version );
	}

	/**
	 * Download a torrent
	 *
	 * @return	void
	 */
	public function download()
	{
		try
		{
			$record = \IPS\Db::i()->select( '*', 'bitracker_torrents_records', array( 'record_id=? AND record_file_id=?', \IPS\Request::i()->fileId, $this->file->id ) )->first();
		}
		catch( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2D417/3', 404, '' );
		}

		/* Download */
		if ( $record['record_type'] === 'link' )
		{
			\IPS\Output::i()->redirect( $record['record_location'] );
		}
		else
		{
			$file = \IPS\File::get( 'bitracker_Torrents', $record['record_location'] );
			$file->originalFilename = $record['record_realname'] ?: $file->originalFilename;
		}

		/* If it's an AWS file just redirect to it */
		if ( $signedUrl = $file->generateTemporaryDownloadUrl() )
		{
			\IPS\Output::i()->redirect( $signedUrl );
		}

		/* Send headers and print file */
		\IPS\Output::i()->sendStatusCodeHeader( 200 );
		\IPS\Output::i()->sendHeader( "Content-type: " . \IPS\File::getMimeType( $file->originalFilename ) . ";charset=UTF-8" );
		\IPS\Output::i()->sendHeader( "Content-Security-Policy: default-src 'none'; sandbox" );
		\IPS\Output::i()->sendHeader( "X-Content-Security-Policy:  default-src 'none'; sandbox" );
		\IPS\Output::i()->sendHeader( 'Content-Disposition: ' . \IPS\Output::getContentDisposition( 'attachment', $file->originalFilename ) );
		\IPS\Output::i()->sendHeader( "Content-Length: " . $file->filesize() );

		$file->printFile();
		exit;
	}
}