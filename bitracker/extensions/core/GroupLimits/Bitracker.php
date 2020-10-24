<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker Group Limits
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

namespace IPS\bitracker\extensions\core\GroupLimits;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Group Limits
 *
 * This extension is used to define which limit values "win" when a user has secondary groups defined
 */
class _Bitracker
{
	/**
	 * Get group limits by priority
	 *
	 * @return	array
	 */
	public function getLimits()
	{
		return array (
			'exclude' 		=> array(),
			'lessIsMore'	=> array( 'bit_wait_period' ),
			'neg1IsBest'	=> array(),
			'zeroIsBest'	=> array( 'bit_throttling' ),
			'callback'		=> array( 'bit_restrictions' => function( $a, $b, $k )
			{
				// Decode
				if ( isset( $a[ $k ] ) AND $a[ $k ] )
				{
					$a = json_decode( $a[ $k ], TRUE );
				}
				else
				{
					if( !isset( $b[ $k ] ) )
					{
						return null;
					}

					return $b[ $k ];
				}
				if ( isset( $b[ $k ] ) AND $b[ $k ] )
				{
					$b = json_decode( $b[ $k ], TRUE );
				}
				else
				{
					if( !isset( $a[ $k ] ) )
					{
						return null;
					}

					return json_encode( $a[ $k ] );
				}
				$return = array();
				
				// Lower is better
				foreach ( array( 'limit_sim', 'min_posts' ) as $k )
				{
					$return[ $k ] = ( $a[ $k ] < $b[ $k ] ) ? $a[ $k ] : $b[ $k ];
				}
				
				// Higher is better
				foreach ( array( 'daily_bw', 'weekly_bw', 'monthly_bw', 'daily_dl', 'weekly_dl', 'monthly_dl' ) as $k )
				{
					if( $a[ $k ] == 0 OR $b[ $k ] == 0 )
					{
						$return[ $k ] = 0;
						continue;
					}

					$return[ $k ] = ( $a[ $k ] > $b[ $k ] ) ? $a[ $k ] : $b[ $k ];
				}
				
				// Encode
				return json_encode( $return );
			} )
		);
	}
}