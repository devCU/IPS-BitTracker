<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker bitTempFileCleanup Task
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4.10
 * @subpackage	BitTracker
 * @version     2.2.0 Final
 * @source      https://github.com/GaalexxC/IPS-4.4-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     29 AUG 2020
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

namespace IPS\bitracker\tasks;

 /* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * bitTempFileCleanup Task
 */
class _bitTempFileCleanup extends \IPS\Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	\IPS\Task\Exception
	 */
	public function execute()
	{
		foreach ( \IPS\Db::i()->select( '*', 'bitracker_torrents_records', array( 'record_file_id=0 AND record_time<?', \IPS\DateTime::create()->sub( new \DateInterval( 'P1D' ) )->getTimestamp() ) ) as $file )
		{
			try
			{
				\IPS\File::get( $file['record_type'] === 'upload' ? 'bitracker_Torrents' : 'bitracker_Screenshots', $file['record_location'] )->delete();
			}
			catch ( \Exception $e ) { }

			if( $file['record_thumb'] )
			{
				try
				{
					\IPS\File::get( 'bitracker_Screenshots', $file['record_thumb'] )->delete();
				}
				catch ( \Exception $e ) { }
			}

			if( $file['record_no_watermark'] )
			{
				try
				{
					\IPS\File::get( 'bitracker_Screenshots', $file['record_no_watermark'] )->delete();
				}
				catch ( \Exception $e ) { }
			}
		}
		
		\IPS\Db::i()->delete( 'bitracker_torrents_records', array( 'record_file_id=0 AND record_time<?', \IPS\DateTime::create()->sub( new \DateInterval( 'P1D' ) )->getTimestamp() ) );
		
		\IPS\Db::i()->delete( 'bitracker_sessions', array( 'dsess_start<?', \IPS\DateTime::create()->sub( new \DateInterval( 'PT6H' ) )->getTimestamp() ) );
		
		return NULL;
	}
}