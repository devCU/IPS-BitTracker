<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker File Storage Extension
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4x
 * @subpackage	BitTracker
 * @version     2.0.0 RC 1
 * @source      https://github.com/GaalexxC/IPS-4.4-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     10 JUN 2019
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

namespace IPS\bitracker\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Nfo
 */
class _Nfo
{
	/**
	 * Some file storage engines have the facility to upload private files that need specially signed URLs to download to prevent public access of protected files.
	 */
	public static $isPrivate = true;
	
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count()
	{
		return \IPS\Db::i()->select( 'COUNT(*)', 'bitracker_torrents_records', array( 'record_type=?', 'nfoupload' ) )->first();
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	\Underflowexception				When file record doesn't exist. Indicating there are no more files to move
	 * @return	void
	 */
	public function move( $offset, $storageConfiguration, $oldConfiguration=NULL )
	{
		$record = \IPS\Db::i()->select( '*', 'bitracker_torrents_records', array( 'record_type=?', 'nfoupload' ), 'record_id', array( $offset, 1 ) )->first();
		
		try
		{
			$file = \IPS\File::get( $oldConfiguration ?: 'bitracker_Torrents', $record['record_location'] )->move( $storageConfiguration );
			
			if ( (string) $file != $record['record_location'] )
			{
				\IPS\Db::i()->update( 'bitracker_torrents_records', array( 'record_location' => (string) $file ), array( 'record_id=?', $record['record_id'] ) );
			}
		}
		catch( \Exception $e )
		{
			/* Any issues are logged */
		}
	}

	/**
	 * Fix all URLs
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @return void
	 */
	public function fixUrls( $offset )
	{
		$record = \IPS\Db::i()->select( '*', 'bitracker_torrents_records', array( 'record_type=?', 'nfoupload' ), 'record_id', array( $offset, 1 ) )->first();
		
		if ( $new = \IPS\File::repairUrl( $record['record_location'] ) )
		{
			\IPS\Db::i()->update( 'bitracker_torrents_records', array( 'record_location' => $new ), array( 'record_id=?', $record['record_id'] ) );
		}
	}

	/**
	 * Check if a file is valid
	 *
	 * @param	string	$file		The file path to check
	 * @return	bool
	 */
	public function isValidFile( $file )
	{
		try
		{
			$record	= \IPS\Db::i()->select( '*', 'bitracker_torrents_records', array( 'record_location=? AND record_type=?', (string) $file, 'nfoupload' ) )->first();

			return TRUE;
		}
		catch ( \UnderflowException $e )
		{
			return FALSE;
		}
	}


	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete()
	{
		foreach( \IPS\Db::i()->select( '*', 'bitracker_torrents_records', "record_location IS NOT NULL and record_type='nfoupload'" ) as $nfo )
		{
			try
			{
				\IPS\File::get( 'bitracker_Torrents', $nfo['record_location'] )->delete();
			}
			catch( \Exception $e ){}
		}
	}
}