<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker Sites Node
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4x
 * @subpackage	BitTracker
 * @version     2.0.0 Beta 1
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
 
namespace IPS\bitracker;

 /* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Sites Node
 */
class _Sites extends \IPS\Node\Model
{
	protected static $multitons;    
    public static $databasePrefix = 'site_';
    public static $databaseColumnId = 'id';
	public static $databaseTable = 'bitracker_sites';
	public static $nodeTitle = 'module__sites';
	public static $titleLangPrefix = 'bitracker_sites_';
	public static $modalForms = TRUE; 
   
	/**
	 * Set the title
	 *
	 * @param	string	$title	Title
	 * @return	void
	 */
	public function set_title( $title )
	{
		$this->_data['site_name'] = $title;
	}

	public function get__title()
	{
		if ( !$this->id )
		{
			return '';
		}	   
       
        return $this->name;
	}   
    
	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled()
	{
		return $this->enabled;
	}

	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( $enabled )
	{
		$this->enabled	= $enabled;
	}    
            	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{		   
        /* Display form */       
		$form->addHeader( 'siteapi_settings' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'siteapi_enabled', $this->enabled ? (bool) $this->enabled : TRUE, FALSE, array() ) );
		$form->add( new \IPS\Helpers\Form\Text( 'siteapi_name', ( $this->name ) ? $this->name : '', TRUE, array() ) );
		$form->add( new \IPS\Helpers\Form\Text( 'siteapi_host', ( $this->host ) ? $this->host : '', TRUE, array() ) );
		$form->add( new \IPS\Helpers\Form\Url( 'siteapi_url', ( $this->url ) ? $this->url : '', TRUE, array() ) );
		$form->add( new \IPS\Helpers\Form\Url( 'siteapi_oembed', ( $this->oembed ) ? $this->oembed : '', FALSE, array() ) );
 	}
    
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{ 
 		foreach( $values as $k => $v )
		{
			if( mb_substr( $k, 0, 7 ) === 'siteapi_' )
			{
				unset( $values[ $k ] );
				$values[ mb_substr( $k, 7 ) ] = $v;
			}
		}	   
       
        $values['url']    = ( isset( $values['url'] ) AND $values['url'] ) ? (string) $values['url'] : NULL;
        $values['oembed'] = ( isset( $values['oembed'] ) AND $values['oembed'] ) ? (string) $values['oembed'] : NULL;	   
        
		/* Send to parent */
		return $values;
	}


	/**
	 * @brief	Cached URL
	 */
	protected $_url	= NULL;

	/**
	 * Get URL
	 *
	 * @return	\IPS\Http\Url
	 */
	public function url()
	{
		return NULL;
	}  
    
	/**
	 * Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		parent::delete();
	}
    
	/**
	 * Search
	 *
	 * @param	string		$column	Column to search
	 * @param	string		$query	Search query
	 * @param	string|null	$order	Column to order by
	 * @param	mixed		$where	Where clause
	 * @return	array
	 */
	public static function search( $column, $query, $order=NULL, $where=array() )
	{
		if ( $column === '_title' )
		{
			$column	= 'site_name';
		}

		if( $order == '_title' )
		{
			$order	= 'site_name';
		}

		return parent::search( $column, $query, $order, $where );
	}      
}