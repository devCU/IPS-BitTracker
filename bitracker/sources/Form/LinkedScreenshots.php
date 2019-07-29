<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker Form helper class for linked screenshots
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4x
 * @subpackage	BitTracker
 * @version     2.0.1 Beta Build
 * @source      https://github.com/GaalexxC/IPS-4.4-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     28 JUL 2019
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

namespace IPS\bitracker\Form;

 /* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Form helper class for linked screenshots
 */
class _LinkedScreenshots extends \IPS\Helpers\Form\FormAbstract
{
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @throws	\DomainException
	 * @return	TRUE
	 */
	public function validate()
	{
		parent::validate();

		if ( $this->value )
		{
			foreach( $this->formatValue() as $value )
			{
				$value = \IPS\Http\Url::createFromString( $value );

				try
				{
					$response = $value->request()->get();

					/* Check MIME */
					$contentType = ( isset( $response->httpHeaders['Content-Type'] ) ) ? $response->httpHeaders['Content-Type'] : ( ( isset( $response->httpHeaders['content-type'] ) ) ? $response->httpHeaders['content-type'] : NULL );
					if( $contentType )
					{
						if ( !preg_match( '/^image\/.+$/i', $contentType ) )
						{
							throw new \DomainException( 'form_url_bad_mime' );
						}
					}
				}
				catch ( \IPS\Http\Request\Exception $e )
				{
					throw new \DomainException( 'form_url_error' );
				}
			}
		}
	}

	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		if ( \is_array( $this->value ) and !isset( $this->value['values'] ) )
		{
			$value = array( 'values' => $this->value, 'default' => \IPS\Request::i()->screenshots_primary_screenshot );
		}
		else
		{
			$value = $this->value;
		}
		return \IPS\Theme::i()->getTemplate( 'submit', 'bitracker', 'front' )->linkedScreenshotField( $this->name, $value );
	}
}