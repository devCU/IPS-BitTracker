<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker File Storage Extension
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4.10
 * @subpackage	BitTracker
 * @version     2.2.0 Final
 * @source      https://github.com/GaalexxC/IPS-4.4-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     31 AUG 2020
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
 * File Storage Extension: FileField
 */
class _FileField
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count()
	{
		$count = 0;
		
		foreach( \IPS\Db::i()->select( '*', 'bitracker_cfields', array( 'cf_type=?', 'Upload' ) ) AS $field )
		{
			$count += \IPS\Db::i()->select( 'COUNT(*)', 'bitracker_ccontent', array( "field_{$field['cf_id']}<>? OR field_{$field['cf_id']} IS NOT NULL", '' ) )->first();
		}
		
		return $count;
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	\Underflowexception				When file record doesn't exist. Indicating there are no more files to move
	 * @return	void								FALSE when there are no more files to move
	 */
	public function move( $offset, $storageConfiguration, $oldConfiguration=NULL )
	{
        if( !\IPS\Db::i()->select( 'COUNT(*)', 'bitracker_cfields', array( 'cf_type=?', 'Upload' ) )->first() )
        {
            throw new \Underflowexception;
        }

		foreach( \IPS\Db::i()->select( '*', 'bitracker_cfields', array( 'cf_type=?', 'Upload' ) ) AS $field )
		{
			$cfield	= \IPS\Db::i()->select( '*', 'bitracker_ccontent', array( "field_{$field['cf_id']}<>? OR field_{$field['cf_id']} IS NOT NULL", '' ), 'file_id', array( $offset, 1 ) )->first();
			
			try
			{
				$file = \IPS\File::get( $oldConfiguration ?: 'bitracker_FileField', $cfield[ 'field_' . $field['cf_id'] ] )->move( $storageConfiguration );
				
				if ( (string) $file != $cfield[ 'field_' . $field['cf_id'] ] )
				{
					\IPS\Db::i()->update( 'bitracker_ccontent', array( "field_{$field['cf_id']}=?", (string) $file ), array( 'file_id=?', $cfield['file_id'] ) );
				}
			}
			catch( \Exception $e )
			{
				/* Any issues are logged */
			}
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
		if( !\IPS\Db::i()->select( 'COUNT(*)', 'bitracker_cfields', array( 'cf_type=?', 'Upload' ) )->first() )
        {
            throw new \Underflowexception;
        }

		foreach( \IPS\Db::i()->select( '*', 'bitracker_cfields', array( 'cf_type=?', 'Upload' ) ) AS $field )
		{
			$cfield	= \IPS\Db::i()->select( '*', 'bitracker_ccontent', array( "field_{$field['cf_id']}<>? OR field_{$field['cf_id']} IS NOT NULL", '' ), 'file_id', array( $offset, 1 ) )->first();
			
			if ( $new = \IPS\File::repairUrl( $cfield[ 'field_' . $field['cf_id'] ] ) )
			{
				\IPS\Db::i()->update( 'bitracker_ccontent', array( "field_{$field['cf_id']}" => $new ), array( 'file_id=?', $cfield['file_id'] ) );
			}
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
		$valid = FALSE;
		foreach( \IPS\Db::i()->select( '*', 'bitracker_cfields', array( 'cf_type=?', 'Upload' ) ) AS $field )
		{
			try
			{
				\IPS\Db::i()->select( '*', 'bitracker_ccontent', array( "field_{$field['cf_id']}=?", (string) $file ) )->first();
				
				$valid = TRUE;
				break;
			}
			catch( \UnderflowException $e ) {}
		}
		
		return $valid;
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete()
	{
		foreach( \IPS\Db::i()->select( '*', 'bitracker_cfields', array( 'cf_type=?', 'Upload' ) ) AS $field )
		{
			try
			{
				foreach( \IPS\Db::i()->select( '*', 'bitracker_ccontent', array( "field_{$field['cf_id']}<>? OR field_{$field['cf_id']} IS NOT NULL", '' ) ) as $cfield )
				{
					\IPS\File::get( 'bitracker_FileField', $cfield[ 'field_' . $field['cf_id'] ] )->delete();
				}
			}
			catch( \Exception $e ){}
		}
	}
}