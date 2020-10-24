<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker Rebuild Screenshot Watermarks
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	BitTracker
 * @version     2.5.0 Stable
 * @source      https://github.com/devCU/IPS-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
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

namespace IPS\bitracker\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class _RebuildScreenshotWatermarks
{
	/**
	 * @brief Number of items to rebuild per cycle
	 */
	public $rebuild	= \IPS\REBUILD_SLOW;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array
	 */
	public function preQueueData( $data )
	{
		try
		{
			$watermark = \IPS\Settings::i()->bit_watermarkpath ? \IPS\File::get( 'core_Theme', \IPS\Settings::i()->bit_watermarkpath )->contents() : NULL;
			$where = array( array( 'record_type=?', 'ssupload' ) );
			if ( !$watermark )
			{
				$where[] = array( 'record_no_watermark<>?', '' );
			}

			$data['count']		= \IPS\Db::i()->select( 'MAX(record_id)', 'bitracker_torrents_records', $where )->first();
			$data['realCount']	= \IPS\Db::i()->select( 'COUNT(*)', 'bitracker_torrents_records', $where )->first();
		}
		catch( \Exception $ex )
		{
			return NULL;
		}

		if( $data['count'] == 0 or $data['realCount'] == 0 )
		{
			return NULL;
		}

		$data['indexed']	= 0;

		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( $data, $offset )
	{
		$last = NULL;
		$watermark = \IPS\Settings::i()->bit_watermarkpath ? \IPS\Image::create( \IPS\File::get( 'core_Theme', \IPS\Settings::i()->bit_watermarkpath )->contents() ) : NULL;

		$where = array( array( 'record_id>? AND record_type=?', $offset, 'ssupload' ) );
		if ( !$watermark )
		{
			$where[] = array( 'record_no_watermark<>?', '' );
		}

		$select = \IPS\Db::i()->select( '*', 'bitracker_torrents_records', $where, 'record_id', array( 0, $this->rebuild ) );

		foreach ( $select as $row )
		{
			try
			{
				if ( $row['record_no_watermark'] )
				{
					$original = \IPS\File::get( 'bitracker_Screenshots', $row['record_no_watermark'] );

					try
					{
						\IPS\File::get( 'bitracker_Screenshots', $row['record_location'] )->delete();
						\IPS\File::get( 'bitracker_Screenshots', $row['record_thumb'] )->delete();
					}
					catch ( \Exception $e ) { }

					if ( !$watermark )
					{
						\IPS\Db::i()->update( 'bitracker_torrents_records', array(
							'record_location'		=> (string) $original,
							'record_thumb'			=> (string) $original->thumbnail( 'bitracker_Screenshots' ),
							'record_no_watermark'	=> NULL
						), array( 'record_id=?', $row['record_id'] ) );

						$data['indexed']++;
						$last = $row['record_id'];

						continue;
					}
				}
				else
				{
					$original = \IPS\File::get( 'bitracker_Screenshots', $row['record_location'] );
				}

				$image = \IPS\Image::create( $original->contents() );
				$image->watermark( $watermark );

				$newFile = \IPS\File::create( 'bitracker_Screenshots', $original->originalFilename, $image );

				\IPS\Db::i()->update( 'bitracker_torrents_records', array(
					'record_location'		=> (string) $newFile,
					'record_thumb'			=> (string) $newFile->thumbnail( 'bitracker_Screenshots' ),
					'record_no_watermark'	=> (string) $original
				), array( 'record_id=?', $row['record_id'] ) );
			}
			catch ( \Exception $e ) { }

			$data['indexed']++;
			$last = $row['record_id'];
		}

		if( $last === NULL )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		return $last;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( $data, $offset )
	{
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('bitracker_rebuilding_screenshots'), 'complete' => ( $data['realCount'] * $data['indexed'] ) > 0 ? round( ( $data['realCount'] * $data['indexed'] ) * 100, 2 ) : 0 );
	}
}