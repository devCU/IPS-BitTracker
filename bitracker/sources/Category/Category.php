<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker Category Node
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4x
 * @subpackage	BitTracker
 * @version     2.0.0 Beta 1
 * @source      https://github.com/GaalexxC/IPS-4.4-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     29 MAR 2019
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
 * Category Node
 */
class _Category extends \IPS\Node\Model implements \IPS\Node\Permissions
{
	use \IPS\Content\ClubContainer;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'bitracker_categories';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'c';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static $databaseColumnParent = 'parent';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'categories';
			
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = array(
		'app'		=> 'bitracker',
		'module'	=> 'bitracker',
		'prefix' => 'categories_'
	);
	
	/**
	 * @brief	[Node] App for permission index
	 */
	public static $permApp = 'bitracker';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static $permType = 'category';
	
	/**
	 * @brief	The map of permission columns
	 */
	public static $permissionMap = array(
		'view' 				=> 'view',
		'read'				=> 2,
		'add'				=> 3,
		'download'			=> 4,
		'reply'				=> 5,
		'review'			=> 6
	);
	
	/**
	 * @brief	Bitwise values for members_bitoptions field
	 */
	public static $bitOptions = array(
		'bitoptions' => array(
			'bitoptions' => array(
				'allowss'				=> 1,	// Allow screenshots?
				'reqss'					=> 2,	// Require screenshots?
				'comments'				=> 4,	// Enable comments?
				'moderation'			=> 8,	// Require files to be approved?
				'comment_moderation'	=> 16,	// Require comments to be approved?
				# 32 is deprecated
				'moderation_edits'		=> 64,	// Edits must be approved?
				'submitter_log'			=> 128,	// File submitter can view downloads logs?
				'reviews'				=> 256,	// Enable reviews?
				'reviews_mod'			=> 512,	// Reviews must be approved?
				'reviews_bitrack'		=> 1024,// Users must have downloaded before they can review?
				'topic_delete'			=> 2048,// Delete created topics when file is deleted?
				'topic_screenshot'		=> 4096,// Include screenshot with topics?
			)
		)
	);

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'bitracker_category_';
	
	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static $descriptionLangSuffix = '_desc';
	
	/**
	 * @brief	[Node] Moderator Permission
	 */
	public static $modPerm = 'bitrack_categories';
	
	/**
	 * @brief	Content Item Class
	 */
	public static $contentItemClass = 'IPS\bitracker\File';
	
	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static $permissionLangPrefix = 'perm_file_';
	
	/**
	 * @brief	[Node] Enabled/Disabled Column
	 */
	public static $databaseColumnEnabledDisabled = 'open';

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_name_furl()
	{
		if( !$this->_data['name_furl'] )
		{
			$this->name_furl	= \IPS\Http\Url\Friendly::seoTitle( \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( 'bitracker_category_' . $this->id ) );
			$this->save();
		}

		return $this->_data['name_furl'] ?: \IPS\Http\Url\Friendly::seoTitle( \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( 'bitracker_category_' . $this->id ) );
	}
	
	/**
	 * Get sort order
	 *
	 * @return	string
	 */
	public function get__sortBy()
	{
		return $this->sortorder;
	}
	
	/**
	 * Get sort order
	 *
	 * @return	string
	 */
	public function get__sortOrder()
	{
		return $this->sortorder == 'file_name' ? 'ASC' : parent::get__sortOrder();
	}
	
	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	public function set__enabled( $enabled )
	{
		parent::set__enabled( $enabled );
		
		static::updateSearchIndexOnEnableDisable( $this, (bool) $enabled );
		
		/* Trash widgets so files in this category are not viewable in widgets */
		\IPS\Widget::deleteCaches( NULL, 'bitracker' );
	}
	
	/**
	 * Update the search index on enable / disable of a category
	 *
	 * @param	\IPS\bitracker\Category		$node		The Category
	 * @param	bool						$enabled	Enabled / Disable
	 * @return	void
	 */
	protected static function updateSearchIndexOnEnableDisable( \IPS\bitracker\Category $node, $enabled )
	{
		\IPS\Content\Search\Index::i()->massUpdate( static::$contentItemClass, $node->_id, NULL, ( $enabled ) ? $node->searchIndexPermissions() : '' );
		
		if ( $node->hasChildren( NULL ) )
		{
			foreach( $node->children( NULL ) AS $child )
			{
				static::updateSearchIndexOnEnableDisable( $child, $enabled );
			}
		}
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$customFields = array();
		foreach ( \IPS\Db::i()->select( 'cf_id', 'bitracker_cfields', NULL, 'cf_position' ) as $fieldId )
		{
			$customFields[ $fieldId ] = \IPS\Member::loggedIn()->language()->addToStack( "bitracker_field_{$fieldId}" );
		}
		
		$form->addTab( 'category_settings' );
		$form->addHeader( 'category_settings' );
		$form->add( new \IPS\Helpers\Form\Translatable( 'cname', NULL, TRUE, array( 'app' => 'bitracker', 'key' => ( $this->id ? "bitracker_category_{$this->id}" : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'cdesc', NULL, FALSE, array(
			'app'		=> 'bitracker',
			'key'		=> ( $this->id ? "bitracker_category_{$this->id}_desc" : NULL ),
			'editor'	=> array(
				'app'			=> 'bitracker',
				'key'			=> 'Categories',
				'autoSaveKey'	=> ( $this->id ? "bitracker-cat-{$this->id}" : "bitracker-new-cat" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'description' ) : NULL, 'minimize' => 'cdesc_placeholder'
			)
		) ) );

		$class = get_called_class();

		$form->add( new \IPS\Helpers\Form\Node( 'cparent', $this->parent ?: 0, FALSE, array(
			'class'		      => '\IPS\bitracker\Category',
			'disabled'	      => false,
			'zeroVal'         => 'node_no_parentd',
			'permissionCheck' => function( $node ) use ( $class )
			{
				if( isset( $class::$subnodeClass ) AND $class::$subnodeClass AND $node instanceof $class::$subnodeClass )
				{
					return FALSE;
				}

				return !isset( \IPS\Request::i()->id ) or ( $node->id != \IPS\Request::i()->id and !$node->isChildOf( $node::load( \IPS\Request::i()->id ) ) );
			}
		) ) );
		if ( !empty( $customFields ) )
		{
			$form->add( new \IPS\Helpers\Form\Select( 'ccfields', $this->id ? explode( ',', $this->_data['cfields'] ) : array(), FALSE, array( 'options' => $customFields, 'multiple' => TRUE ), NULL, NULL, NULL, 'ccfields' ) );
		}
		$form->addHeader( 'category_comments_and_reviews' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_comments', $this->id ? $this->bitoptions['comments'] : TRUE, FALSE, array( 'togglesOn' => array( 'cbitoptions_comment_moderation' ) ), NULL, NULL, NULL, 'cbitoptions_comments' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_comment_moderation', $this->bitoptions['comment_moderation'], FALSE, array(), NULL, NULL, NULL, 'cbitoptions_comment_moderation' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_reviews', $this->id ? $this->bitoptions['reviews'] : TRUE, FALSE, array( 'togglesOn' => array( 'cbitoptions_reviews_mod', 'cbitoptions_reviews_bitrack' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_reviews_bitrack', $this->bitoptions['reviews_bitrack'], FALSE, array(), NULL, NULL, NULL, 'cbitoptions_reviews_bitrack' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_reviews_mod', $this->bitoptions['reviews_mod'], FALSE, array(), NULL, NULL, NULL, 'cbitoptions_reviews_mod' ) );
		$form->addHeader( 'category_display' );
		$form->add( new \IPS\Helpers\Form\Select( 'csortorder', $this->sortorder ?: 'updated', FALSE, array( 'options' => array( 'updated' => 'sort_updated', 'last_comment' => 'last_reply', 'title' => 'file_title', 'rating' => 'sort_rating', 'date' => 'sort_date', 'num_comments' => 'sort_num_comments', 'num_reviews' => 'sort_num_reviews', 'views' => 'sort_num_views' ) ), NULL, NULL, NULL, 'csortorder' ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'cdisclaimer', NULL, FALSE, array( 'app' => 'bitracker', 'key' => ( $this->id ? "bitracker_category_{$this->id}_disclaimer" : NULL ), 'editor' => array( 'app' => 'bitracker', 'key' => 'Categories', 'autoSaveKey' => ( $this->id ? "bitracker-cat-{$this->id}-disc" : "bitracker-new-cat-disc" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'disclaimer' ) : NULL, 'minimize' => 'cdisclaimer_placeholder' ) ), NULL, NULL, NULL, 'cdisclaimer-editor' ) );
		$form->addHeader( 'category_logs' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'clog_on', $this->log !== 0, FALSE, array( 'togglesOn' => array( 'clog', 'submitter_log' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Interval( 'clog', $this->log === NULL ? -1 : $this->log, FALSE, array(
			'valueAs' => \IPS\Helpers\Form\Interval::DAYS, 'unlimited' => -1
		), NULL, NULL, ( $this->id and \IPS\Member::loggedIn()->hasAcpRestriction( 'bitracker', 'bitracker', 'categories_recount_bitracker' ) ) ? '<a data-confirm data-confirmSubMessage="' . \IPS\Member::loggedIn()->language()->addToStack('clog_recount_desc') . '" href="' . \IPS\Http\Url::internal( "app=bitracker&module=configure&controller=categories&do=recountBitracker&id={$this->id}") . '">' . \IPS\Member::loggedIn()->language()->addToStack('clog_recount') . '</a>' : '', 'clog' ) );

		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_submitter_log', $this->bitoptions['submitter_log'], FALSE, array(), NULL, NULL, NULL, 'submitter_log' ) );
		
		$form->addTab( 'category_submissions' );
		$form->addHeader( 'category_allowed_files' );
		$form->add( new \IPS\Helpers\Form\Text( 'ctypes', $this->id ? $this->_data['types'] : NULL, FALSE, array( 'autocomplete' => array( 'unique' => 'true' ), 'nullLang' => 'any_extensions' ), NULL, NULL, NULL, 'ctypes' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'cmaxfile', $this->maxfile ?: -1, FALSE, array( 'unlimited' => -1 ), function( $value ) {
			if( !$value )
			{
				throw new \InvalidArgumentException('form_required');
			}
		}, NULL, \IPS\Member::loggedIn()->language()->addToStack('filesize_raw_k'), 'cmaxfile' ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'csubmissionterms', $this->submissionterms, FALSE, array( 'app' => 'bitracker', 'key' => ( $this->id ? "bitracker_category_{$this->id}_subterms" : NULL ), 'editor' => array( 'app' => 'bitracker', 'key' => 'Categories', 'autoSaveKey' => ( $this->id ? "bitracker-cat-{$this->id}-subt" : "bitracker-new-cat-subt" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'subt' ) : NULL, 'minimize' => 'csubmissionterms_placeholder' ) ) ) );
		$form->addHeader( 'category_versioning' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cversioning_on', $this->versioning !== 0, FALSE, array( 'togglesOn' => array( 'cversioning' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'cversioning', $this->versioning === NULL ? -1 : $this->versioning, FALSE, array( 'unlimited' => -1 ), NULL, NULL, NULL, 'cversioning' ) );
		$form->addHeader( 'category_moderation' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_moderation', $this->bitoptions['moderation'], FALSE, array( 'togglesOn' => array( 'cbitoptions_moderation_edits' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_moderation_edits', $this->bitoptions['moderation_edits'], FALSE, array(), NULL, NULL, NULL, 'cbitoptions_moderation_edits' ) );
		$form->addHeader( 'category_screenshots' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_allowss', $this->id ? $this->bitoptions['allowss'] : TRUE, FALSE, array( 'togglesOn' => array( 'cbitoptions_reqss', 'cmaxss', 'cmaxdims' ) ), NULL, NULL, NULL, 'cbitoptions_allowss' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_reqss', $this->bitoptions['reqss'], FALSE, array(), NULL, NULL, NULL, 'cbitoptions_reqss' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'cmaxss', $this->maxss, FALSE, array( 'unlimited' => 0 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('filesize_raw_k'), 'cmaxss' ) );
		$form->add( new \IPS\Helpers\Form\WidthHeight( 'cmaxdims', $this->maxdims ? explode( 'x', $this->maxdims ) : array( 0, 0 ), FALSE, array( 'unlimited' => array( 0, 0 ) ), NULL, NULL, NULL, 'cmaxdims' ) );
		
		if ( \IPS\Settings::i()->tags_enabled )
		{
			$form->addHeader( 'category_tags' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'ctags_disabled', !$this->tags_disabled, FALSE, array( 'togglesOn' => array( 'ctags_noprefixes', 'ctags_predefined' ) ), NULL, NULL, NULL, 'ctags_disabled' ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'ctags_noprefixes', !$this->tags_noprefixes, FALSE, array(), NULL, NULL, NULL, 'ctags_noprefixes' ) );
			
			if ( !\IPS\Settings::i()->tags_open_system )
			{
				$form->add( new \IPS\Helpers\Form\Text( 'ctags_predefined', $this->tags_predefined, FALSE, array( 'autocomplete' => array( 'unique' => 'true' ), 'nullLang' => 'ctags_predefined_unlimited' ), NULL, NULL, NULL, 'ctags_predefined' ) );
			}
		}
		
		$form->addTab( 'category_errors', NULL, 'category_errors_blurb' );
		$form->add( new \IPS\Helpers\Form\Translatable( 'noperm_view', NULL, FALSE, array( 'app' => 'bitracker', 'key' => ( $this->id ? "bitracker_category_{$this->id}_npv" : NULL ), 'editor' => array( 'app' => 'bitracker', 'key' => 'Categories', 'autoSaveKey' => ( $this->id ? "bitracker-cat-{$this->id}-npv" : "bitracker-new-cat-npv" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'npv' ) : NULL, 'minimize' => 'noperm_view_placeholder' ), NULL, NULL, NULL, 'noperm_view' ) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'noperm_dl', NULL, FALSE, array( 'app' => 'bitracker', 'key' => ( $this->id ? "bitracker_category_{$this->id}_npd" : NULL ), 'editor' => array( 'app' => 'bitracker', 'key' => 'Categories', 'autoSaveKey' => ( $this->id ? "bitracker-cat-{$this->id}-npd" : "bitracker-new-cat-npd" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'npv' ) : NULL, 'minimize' => 'noperm_dl_placeholder' ), NULL, NULL, NULL, 'noperm_dl' ) ) );
		
		if ( \IPS\Application::appIsEnabled( 'forums' ) )
		{
			if ( $this->id )
			{
				$rebuildUrl = \IPS\Http\Url::internal( 'app=bitracker&module=bitracker&controller=categories&id=' . $this->id . '&do=rebuildTopicContent' );

				\IPS\Member::loggedIn()->language()->words['cforum_on_desc'] = \IPS\Member::loggedIn()->language()->addToStack( 'database_forum_record__desc' );
			}

			$form->addTab( 'category_forums_integration' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'cforum_on', $this->forum_id, FALSE, array( 'disableCopy' => TRUE, 'togglesOn' => array(
				'cforum_id',
				'ctopic_prefix',
				'ctopic_suffix',
				'cbitoptions_topic_delete',
				'cbitoptions_topic_screenshot'
			) ), NULL, NULL, $this->id ? \IPS\Member::loggedIn()->language()->addToStack( 'downloadcategory_topic_rebuild', NULL, array( 'sprintf' => array( $rebuildUrl ) ) ) : NULL ) );
			$form->add( new \IPS\Helpers\Form\Node( 'cforum_id', $this->forum_id ? $this->forum_id  : NULL, FALSE, array( 'class' => 'IPS\forums\Forum', 'permissionCheck' => function ( $forum ) { return $forum->sub_can_post and !$forum->redirect_url; } ), NULL, NULL, NULL, 'cforum_id' ) );
			$form->add( new \IPS\Helpers\Form\Text( 'ctopic_prefix', $this->topic_prefix, FALSE, array( 'trim' => FALSE ), NULL, NULL, NULL, 'ctopic_prefix' ) );
			$form->add( new \IPS\Helpers\Form\Text( 'ctopic_suffix', $this->topic_suffix, FALSE, array( 'trim' => FALSE ), NULL, NULL, NULL, 'ctopic_suffix' ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_topic_delete', $this->id ? $this->bitoptions['topic_delete'] : NULL, FALSE, array(), NULL, NULL, NULL, 'cbitoptions_topic_delete' ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_topic_screenshot', $this->id ? $this->bitoptions['topic_screenshot'] : NULL, FALSE, array(), NULL, NULL, NULL, 'cbitoptions_topic_screenshot' ) );
		}
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if ( !$this->id )
		{
			$this->save();
			\IPS\File::claimAttachments( 'bitracker-new-cat', $this->id, NULL, 'description', TRUE );
			\IPS\File::claimAttachments( 'bitracker-new-cat-disc', $this->id, NULL, 'disclaimer', TRUE );
			\IPS\File::claimAttachments( 'bitracker-new-cat-subt', $this->id, NULL, 'subt', TRUE );
			\IPS\File::claimAttachments( 'bitracker-new-cat-npv', $this->id, NULL, 'npv', TRUE );
			\IPS\File::claimAttachments( 'bitracker-new-cat-npd', $this->id, NULL, 'npd', TRUE );
		}
				
		foreach ( array( 'cname' => "bitracker_category_{$this->id}", 'cdesc' => "bitracker_category_{$this->id}_desc", 'cdisclaimer' => "bitracker_category_{$this->id}_disclaimer", 'csubmissionterms' => "bitracker_category_{$this->id}_subterms", 'noperm_view' => "bitracker_category_{$this->id}_npv", 'noperm_dl' => "bitracker_category_{$this->id}_npd" ) as $fieldKey => $langKey )
		{
			if ( array_key_exists( $fieldKey, $values ) )
			{
				\IPS\Lang::saveCustom( 'bitracker', $langKey, $values[ $fieldKey ] );
				
				if ( $fieldKey === 'cname' )
				{
					$this->name_furl = \IPS\Http\Url\Friendly::seoTitle( $values[ $fieldKey ][ \IPS\Lang::defaultLanguage() ] );
				}
				
				unset( $values[ $fieldKey ] );
			}
		}
		
		foreach ( array( 'moderation', 'moderation_edits', 'allowss', 'reqss', 'comments', 'comment_moderation', 'submitter_log', 'reviews', 'reviews_mod', 'reviews_bitrack', 'topic_delete', 'topic_screenshot' ) as $k )
		{
			if ( array_key_exists( "cbitoptions_{$k}", $values ) )
			{
				$values['bitoptions'][ $k ] = $values["cbitoptions_{$k}"];
				unset( $values["cbitoptions_{$k}"] );
			}
		}
		
		if ( isset( $values['cversioning_on'] ) or isset( $values['cversioning'] ) )
		{
			$values['cversioning'] = $values['cversioning_on'] ? ( ( $values['cversioning'] < 0 ) ? NULL : $values['cversioning'] ) : 0;
		}
		
		foreach ( array( 'cmaxfile', 'cmaxss' ) as $k )
		{
			if ( isset( $values[ $k ] ) and $values[ $k ] == -1 )
			{
				$values[ $k ] = NULL;
			}
		}

		if( isset( $values['clog_on'] ) AND $values['clog_on'] != 1 )
		{
			$values['clog'] = 0;
		}
		else if ( isset( $values[ 'clog' ] ) and $values[ 'clog' ] == -1 )
		{
			$values['clog'] = NULL;
		}

		if( array_key_exists( 'clog_on', $values ) )
		{
			unset( $values['clog_on'] );
		}

		if( array_key_exists( 'cversioning_on', $values ) )
		{
			unset( $values['cversioning_on'] );
		}

		if ( isset( $values['ctypes'] ) )
		{
			$values['ctypes'] = $values['ctypes'] ?: NULL;
		}

		if ( isset( $values['cmaxdims'] ) )
		{
			$values['cmaxdims'] = $values['cmaxdims'] ? implode( 'x', $values['cmaxdims'] ) : NULL;
		}

		/* Inverted for legacy reasons */
		foreach ( array( 'ctags_disabled', 'ctags_noprefixes' ) as $k )
		{
			if ( isset( $values[ $k ] ) )
			{
				$values[ $k ] = !$values[ $k ];
			}
		}
		
		if ( isset( $values['cparent'] ) )
		{
			/* Avoid "cparent cannot be null" error if no parent selected. */
			$values['cparent'] = $values['cparent'] ? \intval( $values['cparent']->id ) : 0;
		}
		
		if ( isset( $values['cforum_on'] ) and !$values['cforum_on'] )
		{
			$values['cforum_id'] = 0;
		}
		
		if( isset( $values['cforum_id'] ) AND $values['cforum_id'] )
		{
			$values['cforum_id'] = ( $values['cforum_id'] instanceof \IPS\Node\Model ) ? \intval( $values['cforum_id']->id ) : \intval( $values['cforum_id'] );
		}

		if( array_key_exists( 'cforum_on', $values ) )
		{
			unset( $values['cforum_on'] );
		}

		foreach( $values as $k => $v )
		{
			if( mb_substr( $k, 0, 1 ) === 'c' AND mb_substr( $k, 0, 2 ) !== 'cc' )
			{
				unset( $values[ $k ] );
				$values[ mb_substr( $k, 1 ) ] = $v;
			}
		}

		/* Send to parent */
		return $values;
	}
	
	/**
	 * Get acceptable file extensions
	 *
	 * @return	array|NULL
	 */
	public function get_types()
	{
		return $this->_data['types'] ? explode( ',', $this->_data['types'] ) : NULL;
	}
	
	/**
	 * @brief	Custom Field Cache
	 */
	protected $_customFields = NULL;
	
	/**
	 * Get custom fields
	 *
	 * @return	array
	 */
	protected function get_cfields()
	{
		if ( $this->_customFields === NULL )
		{
			$this->_customFields = array();
			if ( $this->_data['cfields'] )
			{
				foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'bitracker_cfields', array( \IPS\Db::i()->in( 'cf_id', explode( ',', $this->_data['cfields'] ) ) ) ), 'IPS\bitracker\Field' ) AS $field )
				{
					$this->_customFields[ $field->id ] = $field;
				}
			}
		}
		
		return $this->_customFields;
	}
	
	/**
	 * Get Topic Prefix
	 *
	 * @return	string
	 */
	public function get__topic_prefix()
	{
		return str_replace( '{catname}', $this->_title, $this->_data['topic_prefix'] );
	}
	
	/**
	 * Get Topic Suffix
	 *
	 * @return	string
	 */
	public function get__topic_suffix()
	{
		return str_replace( '{catname}', $this->_title, $this->_data['topic_suffix'] );
	}

	/**
	 * @brief	Cached URL
	 */
	protected $_url	= NULL;
	
	/**
	 * @brief	URL Base
	 */
	public static $urlBase = 'app=bitracker&module=portal&controller=main&id=';
	
	/**
	 * @brief	URL Base
	 */
	public static $urlTemplate = 'bitracker_cat';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static $seoTitleColumn = 'name_furl';

	/**
	 * Get message
	 *
	 * @param	string	$type	'npv', 'npd', 'disclaimer'
	 * @return	string|null
	 */
	public function message( $type )
	{
		if ( \IPS\Member::loggedIn()->language()->checkKeyExists( "bitracker_category_{$this->_id}_{$type}" ) )
		{
			$message = \IPS\Member::loggedIn()->language()->get( "bitracker_category_{$this->_id}_{$type}" );
			if ( $message and $message != '<p></p>' )
			{
				return trim( $message );
			}
		}
		
		return NULL;
	}

	/**
	 * Check permissions on any node
	 *
	 * For example - can be used to check if the user has
	 * permission to create content in any node to determine
	 * if there should be a "Submit" button
	 *
	 * @param	mixed								$permission						A key which has a value in static::$permissionMap['view'] matching a column ID in core_permission_index
	 * @param	\IPS\Member|\IPS\Member\Group|NULL	$member							The member or group to check (NULL for currently logged in member)
	 * @param	array								$where							Additional WHERE clause
	 * @param	bool								$considerPostBeforeRegistering	If TRUE, and $member is a guest, will return TRUE if "Post Before Registering" feature is enabled
	 * @return	bool
	 * @throws	\OutOfBoundsException	If $permission does not exist in static::$permissionMap
	 */
	public static function canOnAny( $permission, $member=NULL, $where = array(), $considerPostBeforeRegistering = TRUE )
	{
		$member	= ( $member === NULL ) ? \IPS\Member::loggedIn() : $member;

		if ( $member->bit_block_submissions )
		{
			return FALSE;
		}

		return parent::canOnAny( $permission, $member, $where, $considerPostBeforeRegistering );
	}

	/**
	 * Get latest file information
	 *
	 * @return	\IPS\bitracker\File|NULL
	 */
	public function lastFile()
	{
		$latestFileData	= $this->getLatestFileId();
		$latestFile		= NULL;
		
		if( $latestFileData !== NULL )
		{
			try
			{
				$latestFile	= \IPS\bitracker\File::load( $latestFileData['id'] );
			}
			catch( \OutOfRangeException $e ){}
		}

		return $latestFile;
	}

	/**
	 * Retrieve the latest file ID in categories and children categories
	 *
	 * @return	array|NULL
	 */
	protected function getLatestFileId()
	{
		$latestFile	= NULL;

		if ( $this->last_file_id )
		{
			$latestFile = array( 'id' => $this->last_file_id, 'date' => $this->last_file_date );
		}

		foreach( $this->children() as $child )
		{
			$childLatest = $child->getLatestFileId();

			if( $childLatest !== NULL AND ( $latestFile === NULL OR $childLatest['date'] > $latestFile['date'] ) )
			{
				$latestFile	= $childLatest;
			}
		}

		return $latestFile;
	}
	
	/**
	 * Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\File::unclaimAttachments( 'bitracker_Categories', $this->id );
		parent::delete();
		
		foreach ( array( 'cdisclaimer' => "bitracker_category_{$this->id}_disclaimer", 'csubmissionterms' => "bitracker_category_{$this->id}_subterms", 'noperm_view' => "bitracker_category_{$this->id}_npv", 'noperm_dl' => "bitracker_category_{$this->id}_npd" ) as $fieldKey => $langKey )
		{
			\IPS\Lang::deleteCustom( 'bitracker', $langKey );
		}
	}

	/**
	 * Get template for node tables
	 *
	 * @return	callable
	 */
	public static function nodeTableTemplate()
	{
		return array( \IPS\Theme::i()->getTemplate( 'browse', 'bitracker' ), 'categoryRow' );
	}
	
	/**
	 * Get last comment time
	 *
	 * @note	This should return the last comment time for this node only, not for children nodes
	 * @return	\IPS\DateTime|NULL
	 */
	public function getLastCommentTime()
	{
		return $this->last_file_date ? \IPS\DateTime::ts( $this->last_file_date ) : NULL;
	}
	
	/**
	 * Set last file data
	 *
	 * @param	\IPS\bitracker\File|NULL	$file	The latest file or NULL to work it out
	 * @return	void
	 */
	public function setLastFile( \IPS\bitracker\File $file=NULL )
	{
		if( $file === NULL )
		{
			try
			{
				$file	= \IPS\bitracker\File::constructFromData( \IPS\Db::i()->select( '*', 'bitracker_torrents', array( 'file_cat=? AND file_open=1', $this->id ), 'file_submitted DESC', 1, NULL, NULL, \IPS\Db::SELECT_FROM_WRITE_SERVER )->first() );
			}
			catch ( \UnderflowException $e )
			{
				$this->last_file_id		= 0;
				$this->last_file_date	= 0;
				return;
			}
		}
	
		$this->last_file_id		= $file->id;
		$this->last_file_date	= $file->submitted;
	}
	
	/**
	 * Set last comment
	 *
	 * @param	\IPS\Content\Comment|NULL	$comment	The latest comment or NULL to work it out
	 * @return	int
	 * @note	We actually want to set the last file info, not the last comment, so we ignore $comment
	 */
	public function setLastComment( \IPS\Content\Comment $comment=NULL )
	{
		$this->setLastFile();
	}

	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if ( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		$oldId = $this->id;

		parent::__clone();

		foreach ( array( 'cdisclaimer' => "bitracker_category_{$this->id}_disclaimer", 'csubmissionterms' => "bitracker_category_{$this->id}_subterms", 'noperm_view' => "bitracker_category_{$this->id}_npv", 'noperm_dl' => "bitracker_category_{$this->id}_npd" ) as $fieldKey => $langKey )
		{
			$oldLangKey = str_replace( $this->id, $oldId, $langKey );
			\IPS\Lang::saveCustom( 'bitracker', $langKey, iterator_to_array( \IPS\Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', $oldLangKey ) )->setKeyField( 'lang_id' )->setValueField('word_custom') ) );
		}
	}
	
	/* !Clubs */
	
	/**
	 * Get acp language string
	 *
	 * @return	string
	 */
	public static function clubAcpTitle()
	{
		return 'bitracker_categories';
	}
	
	/**
	 * Set form for creating a node of this type in a club
	 *
	 * @param	\IPS\Helpers\Form	$form	Form object
	 * @return	void
	 */
	public function clubForm( \IPS\Helpers\Form $form )
	{
		$itemClass = static::$contentItemClass;
		$form->add( new \IPS\Helpers\Form\Text( 'club_node_name', $this->_id ? $this->_title : \IPS\Member::loggedIn()->language()->addToStack( $itemClass::$title . '_pl' ), TRUE, array( 'maxLength' => 255 ) ) );
		$form->add( new \IPS\Helpers\Form\Editor( 'club_node_description', $this->_id ? \IPS\Member::loggedIn()->language()->get( static::$titleLangPrefix . $this->_id . '_desc' ) : NULL, FALSE, array( 'app' => 'bitracker', 'key' => 'Categories', 'autoSaveKey' => ( $this->id ? "bitracker-cat-{$this->id}" : "bitracker-new-cat" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'description' ) : NULL, 'minimize' => 'cdesc_placeholder' ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_comments', $this->id ? $this->bitoptions['comments'] : TRUE, FALSE, array(), NULL, NULL, NULL, 'cbitoptions_comments' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_reviews', $this->id ? $this->bitoptions['reviews'] : TRUE, FALSE, array( 'togglesOn' => array( 'cbitoptions_reviews_bitrack' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_allowss', $this->id ? $this->bitoptions['allowss'] : TRUE, FALSE, array( 'togglesOn' => array( 'cbitoptions_reqss', 'cmaxss', 'cmaxdims' ) ), NULL, NULL, NULL, 'cbitoptions_allowss' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cbitoptions_reqss', $this->bitoptions['reqss'], FALSE, array(), NULL, NULL, NULL, 'cbitoptions_reqss' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'ctypes', $this->id ? $this->_data['types'] : NULL, FALSE, array( 'autocomplete' => array( 'unique' => 'true' ), 'nullLang' => 'any_extensions' ), NULL, NULL, NULL, 'ctypes' ) );
	}
	
	/**
	 * Class-specific routine when saving club form
	 *
	 * @param	\IPS\Member\Club	$club	The club
	 * @param	array				$values	Values
	 * @return	void
	 */
	public function _saveClubForm( \IPS\Member\Club $club, $values )
	{
		foreach ( array( 'allowss', 'reqss', 'comments', 'reviews' ) as $k )
		{
			$this->bitoptions[ $k ] = $values[ 'cbitoptions_' . $k ];
		}
		
		if( isset( $values['ctypes'] ) )
		{
			$this->types = implode( ',', $values['ctypes'] );
		}
		
		if ( $values['club_node_name'] )
		{
			$this->name_furl = \IPS\Http\Url\Friendly::seoTitle( $values['club_node_name'] );
		}
		
		if ( !$this->_id )
		{
			$this->save();
			\IPS\File::claimAttachments( 'downloads-new-cat', $this->id, NULL, 'description' );
		}
	}
	
	/**
	 * Files in clubs the member can view
	 *
	 * @return	int
	 */
	public static function filesInClubNodes()
	{
		return \IPS\bitracker\File::getItemsWithPermission( array( array( static::$databasePrefix . static::clubIdColumn() . ' IS NOT NULL' ) ), NULL, 1, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, TRUE, FALSE, FALSE, TRUE );
	}
}