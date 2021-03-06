<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker Category API
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
 * @brief	BitTracker Category API
 */
class _categories extends \IPS\Node\Api\NodeController
{
	/**
	 * Class
	 */
	protected $class = 'IPS\bitracker\Category';

	/**
	 * GET /bitracker/categories
	 * Get list of categories
	 *
	 * @apiparam	int		clubs			0|1 Include club categories, default: 1
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @note		For requests using an OAuth Access Token for a particular member, only categories the authorized user can view will be included
	 * @return		\IPS\Api\PaginatedResponse<IPS\bitracker\Category>
	 */
	public function GETindex()
	{
		/* Return */
		return $this->_list();
	}

	/**
	 * GET /bitracker/categories/{id}
	 * Get specific category
	 *
	 * @param		int		$id			ID Number
	 * @return		\IPS\Api\PaginatedResponse<IPS\bitracker\Category>
	 */
	public function GETitem( $id )
	{
		/* Return */
		return $this->_view( $id );
	}

	/**
	 * POST /bitracker/categories
	 * Create a category
	 *
	 * @apiparam	int|null	parent					The ID number of the parent the category should be created in. NULL for root.
	 * @apiparam	int			moderation				Files must be approved?
	 * @apiparam	int			moderation_edits		New versions must be approved?
	 * @apiparam	int			allowss					Allow screenshots?
	 * @apiparam	int			reqss					Require screenshots?
	 * @apiparam	int			comments				Allow comments?
	 * @apiparam	int			comments_moderation		Comments must be approved?
	 * @apiparam	int			reviews					Allow reviews?
	 * @apiparam	int			reviews_mod				Reviews must be approved?
	 * @apiparam	int			reviews_bitrack 		Files must be downloaded before a review can be left?
	 * @apiparam	object		permissions			An object with the keys as permission options (view, read, add, download, reply, review) and values as permissions to use (which may be * to grant access to all groups, or an array of group IDs to permit access to)
	 * @return		\IPS\bitracker\Category
	 * @throws		1D365/2	NO_TITLE	A title for the category must be supplied
	 */
	public function POSTindex()
	{
		if ( !\IPS\Request::i()->title )
		{
			throw new \IPS\Api\Exception( 'NO_TITLE', '1D365/2', 400 );
		}

		return new \IPS\Api\Response( 201, $this->_create()->apiOutput( $this->member ) );
	}

	/**
	 * POST /bitracker/categories/{id}
	 * Edit a category
	 * 
	 * @apiparam	int|null	parent					The ID number of the parent the category should be created in. NULL for root.
	 * @apiparam	int			moderation				Files must be approved?
	 * @apiparam	int			moderation_edits		New versions must be approved?
	 * @apiparam	int			allowss					Allow screenshots?
	 * @apiparam	int			reqss					Require screenshots?
	 * @apiparam	int			comments				Allow comments?
	 * @apiparam	int			comments_moderation		Comments must be approved?
	 * @apiparam	int			reviews					Allow reviews?
	 * @apiparam	int			reviews_mod				Reviews must be approved?
	 * @apiparam	int			reviews_bitrack     	Files must be downloaded before a review can be left?
	 * @apiparam	object		permissions			    An object with the keys as permission options (view, read, add, download, reply, review) and values as permissions to use (which may be * to grant access to all groups, or an array of group IDs to permit access to)
	 * @param		int		$id			ID Number
	 * @return		\IPS\bitracker\Category
	 */
	public function POSTitem( $id )
	{
		$class = $this->class;
		$category = $class::load( $id );
		
		return new \IPS\Api\Response( 200, $this->_createOrUpdate( $category )->apiOutput( $this->member ) );
	}

	/**
	 * DELETE /bitracker/categories/{id}
	 * Delete a category
	 *
	 * @param		int			$id							ID Number
	 * @apiparam	int			deleteChildrenOrMove		The ID number of the new parent or -1 to delete all child nodes.
	 * @return		void
	 * @throws	1S359/1	INVALID_ID		The node ID does not exist
	 * @throws	1S359/2	INVALID_TARGET	The target node cannot be deleted because the new parent node does not exist
	 * @throws	1S359/3	HAS_CHILDREN	The target node cannot be deleted because it has children (pass deleteChildrenOrMove in the request to specify how to handle the children)
	 */
	public function DELETEitem( $id )
	{
		return $this->_delete( $id, \IPS\Request::i()->deleteChildrenOrMove ?? NULL );
	}

	/**
	 * Create or update node
	 *
	 * @param	\IPS\node\Model	$category				The node
	 * @return	\IPS\node\Model
	 */
	protected function _createOrUpdate( \IPS\node\Model $category )
	{
		foreach ( array( 'title' => "bitracker_category_{$category->id}", 'description' => "bitracker_category_{$category->id}_desc" ) as $fieldKey => $langKey )
		{
			if ( isset( \IPS\Request::i()->$fieldKey ) )
			{
				\IPS\Lang::saveCustom( 'bitracker', $langKey, \IPS\Request::i()->$fieldKey );

				if ( $fieldKey === 'title' )
				{
					$category->name_furl = \IPS\Http\Url\Friendly::seoTitle( \IPS\Request::i()->$fieldKey );
				}
			}
		}

		$category->parent = (int) \IPS\Request::i()->parent?: 0;

		foreach ( array( 'moderation', 'moderation_edits', 'allowss', 'reqss', 'comments', 'comment_moderation', 'reviews', 'reviews_mod', 'reviews_bitrack' ) as $k )
		{
			if ( isset( \IPS\Request::i()->$k ) )
			{
				$category->bitoptions[ $k ] = \IPS\Request::i()->$k;
			}
		}

		return parent::_createOrUpdate( $category );
	}
}