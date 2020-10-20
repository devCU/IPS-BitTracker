<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker Torrents API
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

namespace IPS\bitracker\api;

 /* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	BitTracker Torrents API
 */
class _torrents extends \IPS\Content\Api\ItemController
{
	/**
	 * Class
	 */
	protected $class = 'IPS\bitracker\File';
	
	/**
	 * GET /bitracker/torrents
	 * Get list of torrents
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only files the authorized user can view will be included
	 * @apiparam	string	categories		Comma-delimited list of category IDs
	 * @apiparam	string	authors			Comma-delimited list of member IDs - if provided, only files started by those members are returned
	 * @apiparam	int		locked			If 1, only files which are locked are returned, if 0 only unlocked
	 * @apiparam	int		hidden			If 1, only files which are hidden are returned, if 0 only not hidden
	 * @apiparam	int		pinned			If 1, only files which are pinned are returned, if 0 only not pinned
	 * @apiparam	int		featured		If 1, only files which are featured are returned, if 0 only not featured
	 * @apiparam	string	sortBy			What to sort by. Can be 'date' for creation date, 'title', 'updated', 'popular' or leave unspecified for ID
	 * @apiparam	string	sortDir			Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @return		\IPS\Api\PaginatedResponse<IPS\bitracker\File>
	 */
	public function GETindex()
	{
		/* Where clause */
		$where = array();
		$sortBy = NULL;

		/* Sort by popular files */
		if( \IPS\Request::i()->sortBy == 'popular' )
		{
			\IPS\Request::i()->sortDir = \IPS\Request::i()->sortDir ?: 'ASC';
			$sortBy = 'file_rating ' . \IPS\Request::i()->sortDir . ', file_reviews';
			$where = array( array( 'file_rating>?', 0 ) );
		}

		/* Return */
		return $this->_list( $where, 'categories', FALSE, $sortBy );
	}
	
	/**
	 * GET /bitracker/torrents/{id}
	 * View information about a specific torrent
	 *
	 * @param		int		$id				ID Number
	 * @apiparam	int		version			If specified, will show a previous version of a file (see GET /bitracker/files/{id}/versions)
	 * @throws		2S303/1	INVALID_ID		The file ID does not exist or the authorized user does not have permission to view it
	 * @throws		2S303/6	INVALID_VERSION	The version ID does not exist
	 * @return		\IPS\bitracker\File
	 */
	public function GETitem( $id )
	{
		try
		{
			$file = \IPS\bitracker\File::load( $id );
			if ( $this->member and !$file->can( 'read', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			
			if ( isset( \IPS\Request::i()->version ) )
			{
				try
				{
					$backup = \IPS\Db::i()->select( '*', 'bitracker_filebackup', array( 'b_id=? AND b_fileid=?', \IPS\Request::i()->version, $file->id ) )->first();
					return new \IPS\Api\Response( 200, $file->apiOutput( $this->member, $backup ) );
				}
				catch ( \UnderflowException $e )
				{
					throw new \IPS\Api\Exception( 'INVALID_VERSION', '2S303/6', 404 );
				}
			}
			else
			{
				return new \IPS\Api\Response( 200, $file->apiOutput( $this->member ) );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2S303/1', 404 );
		}
	}
	
	/**
	 * POST /bitracker/torrents
	 * Upload a torrent
	 *
	 * @note	For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, locked will only be honoured if the authenticated user has permission to lock files).
	 * @reqapiparam	int					category		The ID number of the calendar the file should be created in
	 * @reqapiparam	int					author			The ID number of the member creating the file (0 for guest). Required for requests made using an API Key or the Client Credentials Grant Type. For requests using an OAuth Access Token for a particular member, that member will always be the author
	 * @reqapiparam	string				title			The file name
	 * @reqapiparam	string				description		The description as HTML (e.g. "<p>This is an file.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type.
	 * @apiparam	string				version			The version number
	 * @reqapiparam	object				files			Files. Keys should be filename (e.g. 'file.txt') and values should be file content
	 * @apiparam	object				screenshots		Screenshots. Keys should be filename (e.g. 'screenshot1.png') and values should be file content.
	 * @apiparam	string				prefix			Prefix tag
	 * @apiparam	string				tags			Comma-separated list of tags (do not include prefix)
	 * @apiparam	datetime			date			The date/time that should be used for the file post date. If not provided, will use the current date/time. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	string				ip_address		The IP address that should be stored for the file. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	int					locked			1/0 indicating if the file should be locked
	 * @apiparam	int					hidden			0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	int					pinned			1/0 indicating if the file should be featured
	 * @apiparam	int					featured		1/0 indicating if the file should be featured
	 * @throws		1S303/7				NO_CATEGEORY	The category ID does not exist
	 * @throws		1S303/8				NO_AUTHOR		The author ID does not exist
	 * @throws		1S303/9				NO_TITLE		No title was supplied
	 * @throws		1S303/A				NO_DESC			No description was supplied
	 * @throws		1S303/B				NO_TORRENTS		No torrent files were supplied
	 * @throws		2S303/H				NO_PERMISSION	The authorized user does not have permission to create a file in that category
	 * @throws		1S303/I				BAD_FILE_EXT	One of the files has a file type that is not allowed
	 * @throws		1S303/J				BAD_FILE_SIZE	One of the files is too big
	 * @throws		1S303/K				BAD_SS			One of the screenshots is not a valid image
	 * @throws		1S303/L				BAD_SS_SIZE		One of the screenshots is too big by filesize
	 * @throws		1S303/M				BAD_SS_DIMS		One of the screenshots is too big by dimensions
	 * @throws		1S303/N				NO_SS			No screenshots are provided, but screenshots are required for the category
	 * @return		\IPS\bitracker\File
	 */
	public function POSTindex()
	{
		/* Get category */
		try
		{
			$category = \IPS\bitracker\Category::load( \IPS\Request::i()->category );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'NO_CATEGEORY', '1S303/7', 400 );
		}
		
		/* Get author */
		if ( $this->member )
		{
			if ( !$category->can( 'add', $this->member ) )
			{
				throw new \IPS\Api\Exception( 'NO_PERMISSION', '2S303/H', 403 );
			}
			$author = $this->member;
		}
		else
		{
			if ( \IPS\Request::i()->author )
			{
				$author = \IPS\Member::load( \IPS\Request::i()->author );
				if ( !$author->member_id )
				{
					throw new \IPS\Api\Exception( 'NO_AUTHOR', '1S303/8', 400 );
				}
			}
			else
			{
				$author = new \IPS\Member;
			}
		}
		
		/* Check we have a title and a description */
		if ( !\IPS\Request::i()->title )
		{
			throw new \IPS\Api\Exception( 'NO_TITLE', '1S303/9', 400 );
		}
		if ( !\IPS\Request::i()->description )
		{
			throw new \IPS\Api\Exception( 'NO_DESC', '1S303/A', 400 );
		}
		
		/* Validate torrents */
		if ( !isset( \IPS\Request::i()->torrents ) or !\is_array( \IPS\Request::i()->torrents ) or empty( \IPS\Request::i()->torrents ) )
		{
			throw new \IPS\Api\Exception( 'NO_TORRENTS', '1L296/B', 400 );
		}
		if ( $this->member )
		{
			$this->_validateTorrentsForMember( $category );
		}
		
		/* Create torrent record */
		$file = $this->_create( $category, $author );
				
		/* Save records */
		foreach ( array_keys( \IPS\Request::i()->torrents ) as $name )
		{
			$fileObject = \IPS\File::create( 'bitracker_Torrents', $name, $_POST['torrents'][ $name ] );
			
			\IPS\Db::i()->insert( 'bitracker_torrents_records', array(
				'record_file_id'	=> $file->id,
				'record_type'		=> 'upload',
				'record_location'	=> (string) $fileObject,
				'record_realname'	=> $fileObject->originalFilename,
				'record_size'		=> $fileObject->filesize(),
				'record_time'		=> time(),
			) );
		}
		if ( $category->bitoptions['allowss'] and isset( \IPS\Request::i()->screenshots ) )
		{
			$primary = 1;
			foreach ( array_keys( \IPS\Request::i()->screenshots ) as $name )
			{
				$fileObject = \IPS\File::create( 'bitracker_Screenshots', $name, $_POST['screenshots'][ $name ] );
				
				\IPS\Db::i()->insert( 'bitracker_torrents_records', array(
					'record_file_id'		=> $file->id,
					'record_type'			=> 'ssupload',
					'record_location'		=> (string) $fileObject,
					'record_thumb'			=> (string) $fileObject->thumbnail( 'bitracker_Screenshots' ),
					'record_realname'		=> $fileObject->originalFilename,
					'record_size'			=> \strlen( $fileObject->contents() ),
					'record_time'			=> time(),
					'record_no_watermark'	=> NULL,
					'record_default'		=> $primary
				) );
				
				$primary = 0;
			}
		}
		
		/* Recaluclate properties */
		$file = $this->_recalculate( $file );
		
		/* Return */
		$file->save();
		return new \IPS\Api\Response( 201, $file->apiOutput( $this->member ) );
	}
	
	/**
	 * Validate that the authorized member can upload the files provided
	 * 
	 * @param	\IPS\bitracker\Category	$category	The category
	 * @return	void
	 */
	protected function _validateTorrentsForMember( $category )
	{
		foreach ( \IPS\Request::i()->files as $name => $content )
		{
			if ( $category->types )
			{
				$ext = mb_substr( $name, mb_strrpos( $name, '.' ) + 1 );
				if( !\in_array( mb_strtolower( $ext ), array_map( 'mb_strtolower', $category->types ) ) )
				{
					throw new \IPS\Api\Exception( 'BAD_FILE_EXT', '1S303/I', 400 );
				}
			}
			
			if ( $category->maxfile and \strlen( $content ) > ( $category->maxfile * 1024 ) )
			{
				throw new \IPS\Api\Exception( 'BAD_FILE_SIZE', '1S303/J', 400 );
			}
		}
		
		if ( $category->bitoptions['allowss'] )
		{
			if ( isset( \IPS\Request::i()->screenshots ) and \IPS\Request::i()->screenshots )
			{
				foreach ( \IPS\Request::i()->screenshots as $name => $content )
				{
					if ( $category->maxss and \strlen( $content ) > ( $category->maxss * 1024 ) )
					{
						throw new \IPS\Api\Exception( 'BAD_SS_SIZE', '1S303/L', 400 );
					}
					
					try
					{
						$image = \IPS\Image::create( $content );
						if ( $category->maxdims )
						{
							$maxDims = explode( 'x', $category->maxdims );
							if ( $image->width > $maxDims[0] or $image->height > $maxDims[1] )
							{
								throw new \IPS\Api\Exception( 'BAD_SS_DIMS', '1S303/M', 400 );
							}
						}
					}
					catch ( \InvalidArgumentException $e )
					{
						throw new \IPS\Api\Exception( 'BAD_SS', '1S303/K', 400 );
					}
				}
			}
			elseif ( $category->bitoptions['reqss'] )
			{
				throw new \IPS\Api\Exception( 'NO_SS', '1S303/N', 400 );
			}
		}
	}
	
	/**
	 * POST /bitracker/torrents/{id}
	 * Edit a file
	 *
	 * @note	For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, locked will only be honoured if the authenticated user has permission to lock records).
	 * @apiparam	int					category		The ID number of the calendar the file should be created in
	 * @apiparam	int					author			The ID number of the member creating the file (0 for guest). Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	string				title			The file name
	 * @apiparam	string				description		The description as HTML (e.g. "<p>This is an file.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type.
	 * @apiparam	string				prefix			Prefix tag
	 * @apiparam	string				tags			Comma-separated list of tags (do not include prefix)
	 * @apiparam	string				ip_address		The IP address that should be stored for the file. If not provided, will use the IP address from the API request.  Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	int					locked			1/0 indicating if the file should be locked
	 * @apiparam	int					hidden			0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	int					featured		1/0 indicating if the file should be featured
	 * @param		int		$id			ID Number
	 * @throws		2S303/C				INVALID_ID		The file ID is invalid or the authorized user does not have permission to view it
	 * @throws		1S303/D				NO_CATEGORY		The category ID does not exist or the authorized user does not have permission to post in it
	 * @throws		1S303/E				NO_AUTHOR		The author ID does not exist
	 * @throws		2S303/O				NO_PERMISSION	The authorized user does not have permission to edit the file
	 * @return		\IPS\bitracker\File
	 */
	public function POSTitem( $id )
	{
		try
		{
			$file = \IPS\bitracker\File::load( $id );
			if ( $this->member and !$file->can( 'read', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			if ( $this->member and !$file->canEdit( $this->member ) )
			{
				throw new \IPS\Api\Exception( 'NO_PERMISSION', '2S303/O', 403 );
			}
			
			/* New category */
			if ( isset( \IPS\Request::i()->category ) and \IPS\Request::i()->category != $file->category_id and ( !$this->member or $file->canMove( $this->member ) ) )
			{
				try
				{
					$newCategory = \IPS\bitracker\Category::load( \IPS\Request::i()->category );
					if ( $this->member and !$newCategory->can( 'add', $this->member ) )
					{
						throw new \OutOfRangeException;
					}
					
					$file->move( $newCategory );
				}
				catch ( \OutOfRangeException $e )
				{
					throw new \IPS\Api\Exception( 'NO_CATEGORY', '1S303/D', 400 );
				}
			}
			
			/* New author */
			if ( !$this->member and isset( \IPS\Request::i()->author ) )
			{				
				try
				{
					$member = \IPS\Member::load( \IPS\Request::i()->author );
					if ( !$member->member_id )
					{
						throw new \OutOfRangeException;
					}
					
					$file->changeAuthor( $member );
				}
				catch ( \OutOfRangeException $e )
				{
					throw new \IPS\Api\Exception( 'NO_AUTHOR', '1S303/E', 400 );
				}
			}
						
			/* Everything else */
			$this->_createOrUpdate( $file, 'edit' );
			
			/* Save and return */
			$file->save();
			return new \IPS\Api\Response( 200, $file->apiOutput( $this->member ) );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2S303/C', 404 );
		}
	}
	
	/**
	 * Create or update file
	 *
	 * @param	\IPS\Content\Item	$item	The item
	 * @param	string				$type	add or edit
	 * @return	\IPS\Content\Item
	 */
	protected function _createOrUpdate( \IPS\Content\Item $item, $type='add' )
	{		
		/* Description */
		if ( isset( \IPS\Request::i()->description ) )
		{
			$descriptionContents = \IPS\Request::i()->description;
			if ( $this->member )
			{
				$descriptionContents = \IPS\Text\Parser::parseStatic( $descriptionContents, TRUE, NULL, $this->member, 'bitracker_Torrents' );
			}
			$item->desc = $descriptionContents;
		}
		
		/* Version */
		if ( isset( \IPS\Request::i()->version ) )
		{
			$item->version = \IPS\Request::i()->version;
		}

		/* Changelog */
		if ( isset( \IPS\Request::i()->changelog ) )
		{
			$item->changelog = \IPS\Request::i()->changelog;
		}
		

		$file = parent::_createOrUpdate( $item, $type );

		if ( \IPS\Application::appIsEnabled('forums') and $file->container()->forum_id and !$file->hidden() )
		{
			$file->syncTopic();
		}
		return $file;
	}
	
	/**
	 * Recalculate stored properties
	 *
	 * @param	\IPS\bitracker\File	$file	The file
	 * @return	\IPS\bitracker\File
	 */
	protected function _recalculate( $file )
	{
		/* File size */
		$file->size = \floatval( \IPS\Db::i()->select( 'SUM(record_size)', 'bitracker_torrents_records', array( 'record_file_id=? AND record_type=? AND record_backup=0', $file->id, 'upload' ) )->first() );
		
		/* Work out the new primary screenshot */
		try
		{
			$file->primary_screenshot = \IPS\Db::i()->select( 'record_id', 'bitracker_torrents_records', array( 'record_file_id=? AND ( record_type=? OR record_type=? ) AND record_backup=0', $file->id, 'ssupload', 'sslink' ), 'record_default DESC, record_id ASC' )->first();
		}
		catch ( \UnderflowException $e ) { }
		
		/* Return */
		return $file;
	}
	
	/**
	 * GET /bitracker/torrents/{id}/comments
	 * Get comments on an torrent
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		hidden		If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @throws		2S303/2	INVALID_ID	The file ID does not exist or the authorized user does not have permission to view it
	 * @return		\IPS\Api\PaginatedResponse<IPS\bitracker\File\Comment>
	 */
	public function GETitem_comments( $id )
	{
		try
		{
			return $this->_comments( $id, 'IPS\bitracker\File\Comment' );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2S303/2', 404 );
		}
	}
	
	/**
	 * GET /bitracker/torrents/{id}/download
	 * Download a torrent, increments download counter
	 *
	 * @apimemberonly
	 * @param		int		$id					ID Number
	 * @apiparam	int		version				If specified, will show a previous version of a torrent (see GET /bitracker/torrents/{id}/versions)
	 * @return		array
	 * @throws		2S303/M	INVALID_ID			The torrent ID does not exist or the authorized user does not have permission to view it
	 * @throws		2S303/N	NO_PERMISSION		The torrent cannot be downloaded by the authorized member
	 * @throws		2S303/O	INVALID_VERSION		The version ID does not exist
	 * @apiresponse	[\IPS\File]					torrents				The torrents
	 */
	public function GETitem_download( $id )
	{
		try
		{
			$file = \IPS\bitracker\File::load( $id );
			$backup = FALSE;
			if ( $this->member and !$file->can( 'read', $this->member ) )
			{
				throw new \OutOfRangeException;
			}

			if ( !$file->canDownload( $this->member ) )
			{
				throw new \IPS\Api\Exception( 'NO_PERMISSION', '2S303/N', 404 );
			}

			if ( isset( \IPS\Request::i()->version ) )
			{
				try
				{
					$backup = \IPS\Db::i()->select( '*', 'bitracker_filebackup', array( 'b_id=? AND b_fileid=?', \IPS\Request::i()->version, $file->id ) )->first();
				}
				catch ( \UnderflowException $e )
				{
					throw new \IPS\Api\Exception( 'INVALID_VERSION', '2S303/O', 404 );
				}
			}

			if ( $file->container()->log !== 0 )
			{
				\IPS\Db::i()->insert( 'bitracker_downloads', array(
					'dfid'		=> $file->id,
					'dtime'		=> time(),
					'dip'		=> \IPS\Request::i()->ipAddress(),
					'dmid'		=> (int) $this->member->member_id,
					'dsize'		=> 0,
					'dua'		=> 'REST API',
					'dbrowsers'	=> '',
					'dos'		=> ''
				) );
			}

			$file->downloads++;
			$file->save();

			if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->bit_nexus_on and ( $file->cost or $file->nexus ) )
			{
				\IPS\nexus\Customer::load( $this->member->member_id )->log( 'download', array( 'type' => 'bit', 'id' => $file->id, 'name' => $file->name ) );
			}

			$member = $this->member;
			return new \IPS\Api\Response( 200, array( 'files' => array_values( array_map( function( $file ) use ( $member ) {
						return $file->apiOutput( $member );
					}, iterator_to_array( $file->files( $backup ? $backup['b_id'] : NULL ) ) ) ) ) );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2S303/M', 404 );
		}
	}
	
	/**
	 * GET /bitracker/torrents/{id}/reviews
	 * Get reviews on an torrent
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		hidden		If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @throws		2S303/3	INVALID_ID	The file ID does not exist or the authorized user does not have permission to view it
	 * @return		\IPS\Api\PaginatedResponse<IPS\bitracker\File\Review>
	 */
	public function GETitem_reviews( $id )
	{
		try
		{
			return $this->_comments( $id, 'IPS\bitracker\File\Review' );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2S303/3', 404 );
		}
	}
	
	/**
	 * GET /bitracker/torrents/{id}/history
	 * Get previous versions for a torrent
	 *
	 * @param		int		$id			ID Number
	 * @throws		2S303/4	INVALID_ID	The file ID does not exist or the authorized user does not have permission to view it
	 * @return		array
	 * @apiresponse	int		id			The version ID number (use to get more information about this version in GET /bitracker/torrents/{id})
	 * @apiresponse	string	version		The version number provided by the user
	 * @apiresponse	string	changelog	What was new in this version
	 * @apiresponse	datetime	date	Datetime the backup was stored
	 * @apiresponse	bool	hidden		If this version is hidden
	 */
	public function GETitem_history( $id )
	{
		try
		{
			$file = \IPS\bitracker\File::load( $id );
			if ( $this->member and !$file->can( 'read', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			
			$versions = array();

			foreach ( \IPS\Db::i()->select( '*', 'bitracker_filebackup', array( 'b_fileid=?', $id ), 'b_backup DESC' ) as $backup )
			{
				$versions[] = array(
					'id'		=> $backup['b_id'],
					'version'	=> $backup['b_version'],
					'changelog'	=> $backup['b_changelog'],
					'hidden'	=> (bool) $backup['b_hidden'],
					'date'		=> \IPS\DateTime::ts( $backup['b_backup'] )->rfc3339()
				);
			}
			
			return new \IPS\Api\Response( 200, $versions );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2S303/4', 404 );
		}
	}
	
	/**
	 * POST /bitracker/torrents/{id}/history
	 * Upload a new torrent version
	 *
	 * @apiparam	string				title			The file name
	 * @apiparam	string				description		The description as HTML (e.g. "<p>This is an file.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type.
	 * @apiparam	string				version			The version number
	 * @apiparam	string				changelog		What changed in this version
	 * @apiparam	int					save			If 1 this will be saved as a new version and the previous version available in the history. If 0, will simply replace the existing files/screenshots. Defaults to 1. Ignored if category does not have versioning enabled or authorized user does not have permission to disable.
	 * @reqapiparam	object				files			Files. Keys should be filename (e.g. 'file.txt') and values should be file content - will replace all current files
	 * @apiparam	object				screenshots		Screenshots. Keys should be filename (e.g. 'screenshot1.png') and values should be file content - will replace all current screenshots
	 * @param		int		$id			ID Number
	 * @throws		2S303/F				INVALID_ID		The file ID is invalid or the authorized user does not have permission to view it
	 * @throws		1S303/G				NO_TORRENTS	    No torrents were supplied
	 * @throws		2S303/Q				NO_PERMISSION	The authorized user doesnot have permission to edit the file
	 * @throws		2S303/P				PENDING_VERSION	The file already has a new version waiting for approval, this version must be deleted or approved by a moderator first.
	 * @throws		1S303/I				BAD_FILE_EXT	One of the files has a file type that is not allowed
	 * @throws		1S303/J				BAD_FILE_SIZE	One of the files is too big
	 * @throws		1S303/K				BAD_SS			One of the screenshots is not a valid image
	 * @throws		1S303/L				BAD_SS_SIZE		One of the screenshots is too big by filesize
	 * @throws		1S303/M				BAD_SS_DIMS		One of the screenshots is too big by dimensions
	 * @throws		1S303/N				NO_SS			No screenshots are provided, but screenshots are required for the category
	 * @return		\IPS\bitracker\File
	 */
	public function POSTitem_history( $id )
	{
		try
		{
			/* Load torrent */
			$file = \IPS\bitracker\File::load( $id );
			if ( $this->member and !$file->can( 'read', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			if ( $this->member and !$file->canEdit( $this->member ) )
			{
				throw new \IPS\Api\Exception( 'NO_PERMISSION', '2S303/O', 403 );
			}
			if( $file->hasPendingVersion() )
			{
				throw new \IPS\Api\Exception( 'PENDING_VERSION', '2S303/P', 403 );
			}
			$category = $file->container();
			
			/* Validate torrents */
			if ( !isset( \IPS\Request::i()->torrents ) or !\is_array( \IPS\Request::i()->torrents ) or empty( \IPS\Request::i()->torrents ) )
			{
				throw new \IPS\Api\Exception( 'NO_TORRENTS', '1L296/B', 400 );
			}
			if ( $this->member )
			{
				$this->_validateTorrentsForMember( $category );
			}
			
			/* Save current version? */
			$save = FALSE;
			if ( $category->versioning !== 0 )
			{
				if ( $this->member and !$this->member->group['bit_bypass_revision'] )
				{
					$save = TRUE;
				}
				else
				{
					$save = isset( \IPS\Request::i()->save ) ? ( (bool) \IPS\Request::i()->save ) : TRUE;
				}
			}
			if ( $save )
			{
				$file->saveVersion();
			}
			else
			{
				foreach ( \IPS\Db::i()->select( 'record_location', 'bitracker_torrents_records', array( 'record_file_id=?', $file->id ) ) as $record )
				{
					if ( \in_array( $record['record_type'], array( 'upload', 'ssupload' ) ) )
					{
						try
						{
							\IPS\File::get( $record['record_type'] == 'upload' ? 'bitracker_Torrents' : ''bitracker_Screenshots', $record['record_location'] )->delete();
						}
						catch ( \Exception $e ) { }
					}
				}
				\IPS\Db::i()->delete( 'bitracker_torrents_records', array( 'record_file_id=?', $file->id ) );
			}
			
			/* Insert the new records */
			foreach ( array_keys( \IPS\Request::i()->torrents ) as $name )
			{
				$fileObject = \IPS\File::create( 'bitracker_Torrents', $name, $_POST['files'][ $name ] );
				
				\IPS\Db::i()->insert( 'bitracker_torrents_records', array(
					'record_file_id'	=> $file->id,
					'record_type'		=> 'upload',
					'record_location'	=> (string) $fileObject,
					'record_realname'	=> $fileObject->originalFilename,
					'record_size'		=> $fileObject->filesize(),
					'record_time'		=> time(),
				) );
			}
			if ( isset( \IPS\Request::i()->screenshots ) )
			{
				$primary = 1;
				foreach ( array_keys( \IPS\Request::i()->screenshots ) as $name )
				{
					$fileObject = \IPS\File::create( 'bitracker_Screenshots', $name, $_POST['screenshots'][ $name ] );
					
					\IPS\Db::i()->insert( 'bitracker_torrents_records', array(
						'record_file_id'		=> $file->id,
						'record_type'			=> 'ssupload',
						'record_location'		=> (string) $fileObject,
						'record_thumb'			=> (string) $fileObject->thumbnail( 'bitracker_Screenshots' ),
						'record_realname'		=> $fileObject->originalFilename,
						'record_size'			=> \strlen( $fileObject->contents() ),
						'record_time'			=> time(),
						'record_no_watermark'	=> NULL,
						'record_default'		=> $primary
					) );
					
					$primary = 0;
				}
			} 
			
			/* Update */
			$file = $this->_createOrUpdate( $file, 'edit' );
			$file = $this->_recalculate( $file );
			
			/* Save */
			$file->updated = time();
			$file->save();
			
			/* Send notifications */
			if ( $file->open )
			{
				$file->sendUpdateNotifications();
			}
			
			/* Return */
			return new \IPS\Api\Response( 200, $file->apiOutput( $this->member ) );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2S303/F', 404 );
		}
	}
	
	/**
	 * POST /bitracker/torrents/{id}/buy
	 * Generate an invoice to buy a file
	 *
	 * @apimemberonly
	 * @param		int			$id					ID Number
	 * @apiparam	string		currency				Desired currency. If not specified, will use member's default
	 * @apiparam	string		returnUri			The URI to return to after payment is complete. If not specified, will redirect back to the file URI
	 * @return		\IPS\nexus\Invoice
	 * @throws		2S303/V		INVALID_ID			The file ID does not exist or the authorized user does not have permission to view it
	 * @throws		1S303/R		CANNOT_BUY			The member cannot buy the file
	 * @throws		1S303/S		NOT_PURCHASABLE		The file is not currently purchasable
	 * @throws		1S303/T		BUY_PRODUCT			The file cannot be bought directly - direct user to buy associated product
	 * @throws		1S303/U		INVALID_CURRENCY	The currency specified is not available
	 */
	public function POSTitem_buy( $id )
	{
		/* Get the torrent */
		try
		{
			$file = \IPS\bitracker\File::load( $id );
			if ( $this->member and !$file->can( 'read', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2S303/V', 404 );
		}
		$customer = \IPS\nexus\Customer::load( $this->member->member_id );
				
		/* Can we buy? */
		if ( !$file->canBuy( $this->member ) )
		{
			throw new \IPS\Api\Exception( 'CANNOT_BUY', '2S303/R', 403 );
		}
		if ( !$file->isPurchasable() )
		{
			throw new \IPS\Api\Exception( 'NOT_PURCHASABLE', '2S303/S', 403 );
		}
		
		/* Is it associated with a Nexus product? */
		if ( $file->nexus )
		{
			throw new \IPS\Api\Exception( 'BUY_PRODUCT', '2S303/T', 400 );
		}
		
		/* Work out which currency to use */
		if ( isset( \IPS\Request::i()->currency ) )
		{
			if ( \in_array( \IPS\Request::i()->currency, \IPS\nexus\Money::currencies() ) )
			{
				$currency = \IPS\Request::i()->currency;
			}
			else
			{
				throw new \IPS\Api\Exception( 'INVALID_CURRENCY', '1S303/U', 400 );
			}
		}
		else
		{
			$currency = $customer->defaultCurrency();
		}
		
		/* Work out the price */
		$costs = json_decode( $file->cost, TRUE );
		if ( \is_array( $costs ) )
		{
			if ( isset( $costs[ $currency ]['amount'] ) and $costs[ $currency ]['amount'] )
			{
				$price = new \IPS\nexus\Money( $costs[ $currency ]['amount'], $currency );
			}
			else
			{
				throw new \IPS\Api\Exception( 'INVALID_CURRENCY', '1S303/U', 400 );
			}
		}
		else
		{
			$price = new \IPS\nexus\Money( $cost, $currency );
		}
		
		/* Create the item */		
		$item = new \IPS\bitracker\extensions\nexus\Item\File( $file->name, $price );
		$item->id = $file->id;
		try
		{
			$item->tax = \IPS\Settings::i()->bit_nexus_tax ? \IPS\nexus\Tax::load( \IPS\Settings::i()->bit_nexus_tax ) : NULL;
		}
		catch ( \OutOfRangeException $e ) { }
		if ( \IPS\Settings::i()->bit_nexus_gateways )
		{
			$item->paymentMethodIds = explode( ',', \IPS\Settings::i()->bit_nexus_gateways );
		}
		$item->renewalTerm = $file->renewalTerm();
		$item->payTo = $file->author();
		$item->commission = \IPS\Settings::i()->bit_nexus_percent;
		if ( $fees = json_decode( \IPS\Settings::i()->bit_nexus_transfee, TRUE ) and isset( $fees[ $price->currency ] ) )
		{
			$item->fee = new \IPS\nexus\Money( $fees[ $price->currency ]['amount'], $price->currency );
		}
				
		/* Generate the invoice */
		$invoice = new \IPS\nexus\Invoice;
		$invoice->currency = $currency;
		$invoice->member = $customer;
		$invoice->addItem( $item );
		if ( \IPS\Request::i()->returnUri )
		{
			$invoice->return_uri = \IPS\Request::i()->returnUri;
		}
		else
		{
			$invoice->return_uri = "app=bitracker&module=portal&controller=view&id={$file->id}";
		}
		$invoice->save();
		
		/* Return */
		return new \IPS\Api\Response( 200, $invoice->apiOutput( $this->member ) );
	}
	
	/**
	 * DELETE /bitracker/torrents/{id}
	 * Delete a torrent
	 *
	 * @param		int		$id			ID Number
	 * @throws		2S303/5	INVALID_ID	The file ID does not exist
	 * @throws		2S303/P	NO_PERMISSION	The authorized user does not have permission to delete the file
	 * @return		void
	 */
	public function DELETEitem( $id )
	{
		try
		{
			$item = \IPS\bitracker\File::load( $id );
			if ( $this->member and !$item->canDelete( $this->member ) )
			{
				throw new \IPS\Api\Exception( 'NO_PERMISSION', '2G316/G', 404 );
			}
			
			$item->delete();
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2S303/P', 404 );
		}
	}
}