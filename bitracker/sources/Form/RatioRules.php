<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker RatioRules
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
 * Key/Value input class
 */
class _RatioRules extends \IPS\Helpers\Form\KeyValue
{
	/**
	 * @brief	Default Options
	 * @see		\IPS\Helpers\Form\Date::$defaultOptions
	 * @code
	 	$defaultOptions = array(
	 		'start'			=> array( ... ),
	 		'end'			=> array( ... ),
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'key'		=> array(
		),
		'value'		=> array(
		),
	);

	/**
	 * @brief	Key Object
	 */
	public $keyField = NULL;
	
	/**
	 * @brief	Value Object
	 */
	public $valueField = NULL;
	
	/**
	 * Constructor
	 * Creates the two date objects
	 *
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @return	void
	 */
	public function __construct( $name, $defaultValue=NULL, $required=FALSE, $options=array() )
	{
		$options = array_merge( $this->defaultOptions, $options );

		$options = $this->addRatioRule( $options );

		\call_user_func_array( 'parent::__construct', \func_get_args() );
		$this->keyField = new \IPS\Helpers\Form\Number( "{$name}[key]", isset( $defaultValue['key'] ) ? $defaultValue['key'] : NULL, FALSE, isset( $options['key'] ) ? $options['key'] : array() );
		$this->valueField = new \IPS\Helpers\Form\Select( "{$name}[value]", isset( $defaultValue['value'] ) ? $defaultValue['value'] : NULL, FALSE, isset( $options['value'] ) ? $options['value'] : array() );
	}

	/**
	 * Add ratio rules to the options array
	 *
	 * @note	Abstracted so third parties can extend as needed
	 * @param	array 	$options	Options array
	 * @return	array
	 */
	protected function addRatioRule( $options )
	{
		$options['value']['options'] = array(
			'ban'		=> "bit_ratios_block",
			'suspend'		=> "bit_ratios_suspend",
			'moderate'		=> "bit_ratios_moderate",
			'email'	=> "bit_ratios_email",
			'allow'	=> "bit_ratios_allow",
		);

		return $options;
	}
	
	/**
	 * Format Value
	 *
	 * @return	array
	 */
	public function formatValue()
	{
		return array(
			'key'	=> $this->keyField->formatValue(),
			'value'	=> $this->valueField->formatValue()
		);
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		return \IPS\Theme::i()->getTemplate( 'forms', 'bitracker', 'admin' )->ratioProfiles( $this->keyField->html(), $this->valueField->html() );
	}
	
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @throws	\LengthException
	 * @return	TRUE
	 */
	public function validate()
	{
		$this->keyField->validate();
		$this->valueField->validate();
		
		if( $this->customValidationCode !== NULL )
		{
			call_user_func( $this->customValidationCode, $this->value );
		}
	}
}