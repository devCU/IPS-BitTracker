<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker View File Controller
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

namespace IPS\bitracker\modules\front\portal;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * View File Controller
 */
class _view extends \IPS\Content\Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static $contentModel = 'IPS\bitracker\File';

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		try
		{
			$this->file = \IPS\bitracker\File::load( \IPS\Request::i()->id );
			
			$this->file->container()->clubCheckRules();
			
			/* Downloading and viewing the embed does not need to check the permission, as there is a separate download permission already and embed method need to return it's own error  */
			if ( !$this->file->canView( \IPS\Member::loggedIn() ) and \IPS\Request::i()->do != 'download' and \IPS\Request::i()->do != 'embed' )
			{
				\IPS\Output::i()->error( $this->file->container()->message('npv') ?: 'node_error', '2D161/2', 403, '' );
			}
			
			if ( $this->file->primary_screenshot )
			{
				\IPS\Output::i()->metaTags['og:image'] = \IPS\File::get( 'bitracker_Screenshots', $this->file->primary_screenshot_thumb )->url;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2D161/1', 404, '' );
		}
		
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_view.js', 'bitracker', 'front' ) );
		
		parent::execute();
	}
	
	/**
	 * View File
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Init */
		parent::manage();
				
		/* Sort out comments and reviews */
		$tabs = $this->file->commentReviewTabs();
		$_tabs = array_keys( $tabs );
		$tab = isset( \IPS\Request::i()->tab ) ? \IPS\Request::i()->tab : array_shift( $_tabs );
		$activeTabContents = $this->file->commentReviews( $tab );
		$commentsAndReviews = \count( $tabs ) ? \IPS\Theme::i()->getTemplate( 'global', 'core' )->commentsAndReviewsTabs( \IPS\Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $tab, $activeTabContents, $this->file->url(), 'tab', FALSE, TRUE ), md5( $this->file->url() ) ) : NULL;
		if ( \IPS\Request::i()->isAjax() and !isset( \IPS\Request::i()->changelog ) )
		{
			\IPS\Output::i()->output = $activeTabContents;
			return;
		}
		
		/* Any previous versions? */
		$versionData = array( 'b_version' => $this->file->version, 'b_changelog' => $this->file->changelog, 'b_backup' => $this->file->updated );
		$versionWhere = array( array( "b_fileid=?", $this->file->id ) );
		if ( !\IPS\bitracker\File::canViewHiddenItems( NULL, $this->file->container() ) )
		{
			$versionWhere[] = array( 'b_hidden=0' );
		}
		$previousVersions = iterator_to_array( \IPS\Db::i()->select( '*', 'bitracker_filebackup', $versionWhere, 'b_backup DESC' )->setKeyField( 'b_id' ) );
		if ( isset( \IPS\Request::i()->changelog ) and isset( $previousVersions[ \IPS\Request::i()->changelog ] ) )
		{
			$versionData = $previousVersions[ \IPS\Request::i()->changelog ];
		}
	
		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( \IPS\Theme::i()->getTemplate( 'view' )->changeLog( $this->file, $versionData ) );
		}
		
		/* Online User Location */
		\IPS\Session::i()->setLocation( $this->file->url(), $this->file->onlineListPermissions(), 'loc_bitracker_viewing_file', array( $this->file->name => FALSE ) );
		
		/* Custom Field Formatting */
		$cfields	= array();
		$fields		= $this->file->customFields();

		foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( 'pfd.*', array( 'bitracker_cfields', 'pfd' ), NULL, 'pfd.cf_position' ), 'IPS\bitracker\Field' ) as $field )
		{
			if( array_key_exists( 'field_' . $field->id, $this->file->customFields() ) )
			{
				if ( $fields[ 'field_' . $field->id ] !== null AND $fields[ 'field_' . $field->id ] !== '' )
				{
					/* Check for download permission, this is also used to determine if the torrent has been purchased by the viewer
					If this is flagged as a paid field, and download is not available, do not show it */
					if( $field->paid_field AND !$this->file->canDownload( NULL, \IPS\Member::loggedIn() ) )
					{
						continue;
					}

					$cfields[ 'field_' . $field->id ] = array( 
						'type'	=> $field->type, 
						'key'	=> 'field_' . $field->id, 
						'value'	=> $field->displayValue( $fields[ 'field_' . $field->id ] ),
						'location'	=> $field->display_location
					);
				}
			}
		}

		/* Add JSON-ld */
		\IPS\Output::i()->jsonLd['download']	= array(
			'@context'		=> "http://schema.org",
			'@type'			=> "WebApplication",
			"operatingSystem"	=> "N/A",
			'url'			=> (string) $this->file->url(),
			'name'			=> $this->file->mapped('title'),
			'description'	=> $this->file->truncated( TRUE, NULL ),
			'applicationCategory'	=> $this->file->container()->_title,
			'downloadUrl'	=> (string) $this->file->url( 'download' ),
			'dateCreated'	=> \IPS\DateTime::ts( $this->file->submitted )->format( \IPS\DateTime::ISO8601 ),
			'fileSize'		=> \IPS\Output\Plugin\Filesize::humanReadableFilesize( $this->file->filesize() ),
			'softwareVersion'	=> $this->file->version ?: '1.0',
			'author'		=> array(
				'@type'		=> 'Person',
				'name'		=> \IPS\Member::load( $this->file->submitter )->name,
				'image'		=> \IPS\Member::load( $this->file->submitter )->get_photo()
			),
			'interactionStatistic'	=> array(
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "http://schema.org/ViewAction",
					'userInteractionCount'	=> $this->file->views
				),
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "http://schema.org/DownloadAction",
					'userInteractionCount'	=> $this->file->downloads
				)
			)
		);

		/* Do we have a real author? */
		if( $this->file->submitter )
		{
			\IPS\Output::i()->jsonLd['download']['author']['url']	= (string) \IPS\Member::load( $this->file->submitter )->url();
		}

		if( $this->file->updated != $this->file->submitted )
		{
			\IPS\Output::i()->jsonLd['download']['dateModified']	= \IPS\DateTime::ts( $this->file->updated )->format( \IPS\DateTime::ISO8601 );
		}

		if( $this->file->container()->bitoptions['reviews'] AND $this->file->reviews AND $this->file->averageReviewRating() )
		{
			\IPS\Output::i()->jsonLd['download']['aggregateRating'] = array(
				'@type'			=> 'AggregateRating',
				'ratingValue'	=> $this->file->averageReviewRating(),
				'reviewCount'	=> $this->file->reviews,
				'bestRating'	=> \IPS\Settings::i()->reviews_rating_out_of
			);
		}

		if( $this->file->screenshots()->getInnerIterator()->count() )
		{
			\IPS\Output::i()->jsonLd['download']['screenshot'] = array();

			$thumbnails = iterator_to_array( $this->file->screenshots( 1 ) );

			foreach( $this->file->screenshots() as $id => $screenshot )
			{
				\IPS\Output::i()->jsonLd['download']['screenshot'][]	= array(
					'@type'		=> 'ImageObject',
					'url'		=> (string) $screenshot->url,
					'thumbnail'	=> array(
						'@type'		=> 'ImageObject',
						'url'		=> (string) $thumbnails[ $id ]->url
					)
				);
			}
		}

		if( $versionData['b_changelog'] )
		{
			\IPS\Output::i()->jsonLd['download']['releaseNotes'] = $versionData['b_changelog'];
		}

		if( $this->file->topic() )
		{
			\IPS\Output::i()->jsonLd['download']['sameAs'] = (string) $this->file->topic()->url();
		}

		if( $this->file->isPaid() )
		{
			\IPS\Output::i()->jsonLd['download']['potentialAction'] = array(
				'@type'		=> 'BuyAction',
				'target'	=> (string) $this->file->url( 'buy' ),
			);

			/* Get the price */
			$price = $this->file->price();

			if( $price instanceof \IPS\nexus\Money )
			{
				$price = $price->amountAsString();
			}

			\IPS\Output::i()->jsonLd['download']['offers'] = array(
				'@type'		=> 'Offer',
				'url'		=> (string) $this->file->url( 'buy' ),
				'price'		=> $price,
				'priceCurrency'	=> \IPS\nexus\Customer::loggedIn()->defaultCurrency(),
				'availability'	=> "http://schema.org/InStock"
			);
		}

		if( $this->file->container()->bitoptions['comments'] )
		{
			\IPS\Output::i()->jsonLd['download']['interactionStatistic'][] = array(
				'@type'					=> 'InteractionCounter',
				'interactionType'		=> "http://schema.org/CommentAction",
				'userInteractionCount'	=> $this->file->mapped('num_comments')
			);

			\IPS\Output::i()->jsonLd['download']['commentCount'] = $this->file->mapped('num_comments');
		}
		
		if( $this->file->container()->bitoptions['reviews'] )
		{
			\IPS\Output::i()->jsonLd['download']['interactionStatistic'][] = array(
				'@type'					=> 'InteractionCounter',
				'interactionType'		=> "http://schema.org/ReviewAction",
				'userInteractionCount'	=> $this->file->mapped('num_reviews')
			);
		}

		/* Display */
		\IPS\Output::i()->sidebar['sticky'] = TRUE;
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'view' )->view( $this->file, $commentsAndReviews, $versionData, $previousVersions, $this->file->nextItem(), $this->file->prevItem(), $cfields );
	}
	
	/**
	 * Purchase Status
	 *
	 * @return	void
	 */
	public function purchaseStatus()
	{
		\IPS\Session::i()->csrfCheck();
		
		if ( \IPS\Request::i()->value )
		{
			$method = 'canEnablePurchases';
		}
		else
		{
			$method = 'canDisablePurchases';
		}
		
		if ( !$this->file->$method() )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2D161/L', 403, '' );
		}
		
		$this->file->purchasable = (bool) \IPS\Request::i()->value;
		$this->file->save();
		
		\IPS\Session::i()->modLog( 'modlog__action_purchasestatus', array( (string) $this->file->url() => FALSE, $this->file->name => FALSE ), $this->file );
		\IPS\Output::i()->redirect( $this->file->url(), 'saved' );
	}
	
	/**
	 * Buy file
	 *
	 * @return	void
	 */
	protected function buy()
	{
		\IPS\Session::i()->csrfCheck();
		
		/* Can we buy? */
		if ( !$this->file->canBuy() )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2D161/E', 403, '' );
		}
		
		if ( !$this->file->isPurchasable() )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2D161/K', 403, '' );
		}

		/* Have we accepted the terms? */
		if ( $downloadTerms = $this->file->container()->message('disclaimer') and \in_array( $this->file->container()->disclaimer_location, [ 'purchase', 'both'] ) and !isset( \IPS\Request::i()->confirm ) )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'view' )->purchaseTerms( $this->file, $downloadTerms, $this->file->url('buy')->csrf()->setQueryString( 'confirm', 1 ) );
			return;
		}
		
		/* Is it associated with a Nexus product? */
		if ( $this->file->nexus )
		{
			$productIds = explode( ',', $this->file->nexus );
			
			if ( \count( $productIds ) === 1 )
			{
				try
				{
					\IPS\Output::i()->redirect( \IPS\nexus\Package::load( array_pop( $productIds ) )->url() );
				}
				catch ( \OutOfRangeException $e )
				{
					\IPS\Output::i()->error( 'node_error', '2D161/F', 404, '' );
				}
			}
			
			$category = $this->file->container();
			try
			{
				foreach ( $category->parents() as $parent )
				{
					\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
				\IPS\Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
			}
			catch ( \Exception $e ) { }

			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'store.css', 'nexus' ) );
			\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';
			\IPS\Output::i()->sidebar['enabled'] = FALSE;
			\IPS\Output::i()->breadcrumb[] = array( $this->file->url(), $this->file->name );
			\IPS\Output::i()->title = $this->file->name;
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('nexus')->chooseProduct( \IPS\nexus\Package\Item::getItemsWithPermission( array( array( \IPS\Db::i()->in( 'p_id', $productIds ) ) ), 'p_position' ) );
			return;
		}
		
		/* Create the item */		
		$price = $this->file->price();
		if ( !$price )
		{
			\IPS\Output::i()->error( 'file_no_price_for_currency', '1D161/H', 403, '' );
		}
		$item = new \IPS\bitracker\extensions\nexus\Item\File( $this->file->name, $price );
		$item->id = $this->file->id;
		try
		{
			$item->tax = \IPS\Settings::i()->bit_nexus_tax ? \IPS\nexus\Tax::load( \IPS\Settings::i()->bit_nexus_tax ) : NULL;
		}
		catch ( \OutOfRangeException $e ) { }
		if ( \IPS\Settings::i()->bit_nexus_gateways )
		{
			$item->paymentMethodIds = explode( ',', \IPS\Settings::i()->bit_nexus_gateways );
		}
		$item->renewalTerm = $this->file->renewalTerm();
		$item->payTo = $this->file->author();
		$item->commission = \IPS\Settings::i()->bit_nexus_percent;
		if ( $fees = json_decode( \IPS\Settings::i()->bit_nexus_transfee, TRUE ) and isset( $fees[ $price->currency ] ) )
		{
			$item->fee = new \IPS\nexus\Money( $fees[ $price->currency ]['amount'], $price->currency );
		}
				
		/* Generate the invoice */
		$invoice = new \IPS\nexus\Invoice;
		$invoice->currency = ( isset( \IPS\Request::i()->cookie['currency'] ) and \in_array( \IPS\Request::i()->cookie['currency'], \IPS\nexus\Money::currencies() ) ) ? \IPS\Request::i()->cookie['currency'] : \IPS\nexus\Customer::loggedIn()->defaultCurrency();
		$invoice->member = \IPS\nexus\Customer::loggedIn();
		$invoice->addItem( $item );
		$invoice->return_uri = "app=bitracker&module=portal&controller=view&id={$this->file->id}";
		$invoice->save();
		
		/* Take them to it */
		\IPS\Output::i()->redirect( $invoice->checkoutUrl() );
	}
		
	/**
	 * Download file - Show terms and file selection
	 *
	 * @return	void
	 */
	protected function download()
	{
		/* No direct linking check */
		if ( \IPS\Settings::i()->bit_antileech )
		{
			if ( !isset( \IPS\Request::i()->csrfKey ) )
			{
				\IPS\Output::i()->redirect( $this->file->url() );
			}
			
			\IPS\Output::i()->metaTags['robots'] = 'noindex';
			\IPS\Session::i()->csrfCheck();
		}
		
		/* Can we download? */
		try
		{
			$this->file->downloadCheck();
		}
		catch ( \DomainException $e )
		{
			\IPS\Output::i()->error( $e->getMessage(), '1D161/3', 403, '' );
		}
			
		/* What's the URL to confirm? */
		$confirmUrl = $this->file->url()->setQueryString( array( 'do' => 'download', 'confirm' => 1 ) );
		if ( isset( \IPS\Request::i()->version ) )
		{
			$confirmUrl = $confirmUrl->setQueryString( 'version', \IPS\Request::i()->version );
		}
		if ( \IPS\Settings::i()->bit_antileech )
		{
			$confirmUrl = $confirmUrl->csrf();
		}
		
		/* Set navigation */
		$category = $this->file->container();
		try
		{
			foreach ( $category->parents() as $parent )
			{
				\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
			}
			\IPS\Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
		}
		catch ( \Exception $e ) { }

		\IPS\Output::i()->breadcrumb[] = array( $this->file->url(), $this->file->name );
		\IPS\Output::i()->title = $this->file->name;
		
		/* What files do we have? */
		$files = $this->file->files( isset( \IPS\Request::i()->version ) ? \IPS\Request::i()->version : NULL );
		
		/* Have we accepted the terms? */
		if ( $downloadTerms = $category->message('disclaimer') and \in_array( $this->file->container()->disclaimer_location, [ 'download', 'both'] ) and !isset( \IPS\Request::i()->confirm ) )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'view' )->download( $this->file, $downloadTerms, null, $confirmUrl, \count( $files ) > 1 );
			return;
		}

		/* File Selected? */
		if ( \count( $files ) === 1 or ( isset( \IPS\Request::i()->r ) ) )
		{
			/* Which file? */
			foreach ( $files as $k => $file )
			{
				$data = $files->data();
				if ( isset( \IPS\Request::i()->r ) and \IPS\Request::i()->r == $k )
				{
					break;
				}
			}
			
			/* Check it */
			try
			{
				$this->file->downloadCheck( $data );
			}
			catch ( \DomainException $e )
			{
				\IPS\Output::i()->error( $e->getMessage(), '1D161/4', 403, '' );
			}
			
			/* Time Delay */
			if ( \IPS\Member::loggedIn()->group['bit_wait_period'] AND ( !$this->file->isPaid() OR \IPS\Member::loggedIn()->group['bit_paid_restrictions'] ) )
			{
				if ( isset( \IPS\Request::i()->t ) )
				{
					$timerKey = "bitracker_delay_" . md5( (string) $file );
					$cookieDelay = 0;
					
					if ( !isset( \IPS\Request::i()->cookie[ $timerKey ] ) )
					{
						\IPS\Request::i()->setCookie( $timerKey, time() );
					}
					else
					{
						$cookieDelay = \IPS\Request::i()->cookie[ $timerKey ];
					}
					
					if ( \IPS\Request::i()->isAjax() )
					{
						\IPS\Output::i()->json( array( 'download' => time() + \IPS\Member::loggedIn()->group['bit_wait_period'], 'currentTime' => time() ) );
					}
					
					if ( $cookieDelay > ( time() - \IPS\Member::loggedIn()->group['bit_wait_period'] ) )
					{
						\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'view' )->download( $this->file, null, $files, $confirmUrl, \count( $files ) > 1, $data['record_id'], ( $cookieDelay + \IPS\Member::loggedIn()->group['bit_wait_period'] ) - time() );
						return;
					}
					else
					{
						\IPS\Request::i()->setCookie( $timerKey, -1 );
					}
				}
				else
				{
					\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'view' )->download( $this->file, null, $files, $confirmUrl, \count( $files ) > 1 );
					return;
				}
			}
			
			/* Log */
			$_log	= true;
			if( isset( $_SERVER['HTTP_RANGE'] ) )
			{
				if( !\IPS\Http\Ranges::isStartOfFile() )
				{
					$_log	= false;
				}
			}
			if( $_log )
			{
				if ( $category->log !== 0 )
				{
					\IPS\Db::i()->insert( 'bitracker_downloads', array(
						'dfid'		=> $this->file->id,
						'dtime'		=> time(),
						'dip'		=> \IPS\Request::i()->ipAddress(),
						'dmid'		=> (int) \IPS\Member::loggedIn()->member_id,
						'dsize'		=> $data['record_size'],
						'dua'		=> \IPS\Session::i()->userAgent->useragent,
						'dbrowsers'	=> \IPS\Session::i()->userAgent->browser ?: '',
						'dos'		=> ''
					) );
				}

				$this->file->torrents++;
				$this->file->save();
			}
			if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->bit_nexus_on and ( $this->file->cost or $this->file->nexus ) )
			{
				\IPS\nexus\Customer::loggedIn()->log( 'download', array( 'type' => 'bit', 'id' => $this->file->id, 'name' => $this->file->name ) );
			}
			
			/* Download */
			if ( $data['record_type'] === 'link' )
			{
				\IPS\Output::i()->redirect( $data['record_location'] );
			}
			else
			{
				$file = \IPS\File::get( 'bitracker_Torrents', $data['record_location'], $data['record_size'] );
				$file->originalFilename = $data['record_realname'] ?: $file->originalFilename;
				$this->_download( $file );
			}
		}
		
		/* Nope - choose one */
		else
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'view' )->download( $this->file, null, $files, $confirmUrl, \count( $files ) > 1 );
		}
	}
	
	/**
	 * Actually send the file for download
	 *
	 * @param	\IPS\File	$file	The file to download
	 * @return	void
	 */
	protected function _download( \IPS\File $file )
	{
        if ( !$file->filesize() )
        {
            \IPS\Output::i()->error( 'bitracker_no_file', '3D161/G', 404, '' );
        }
		
		/* Log session (but we don't need to create a new session on subsequent requests) */
		if( !isset( $_SERVER['HTTP_RANGE'] ) )
		{
			$torrentSessionId = \IPS\Login::generateRandomString();
			\IPS\Db::i()->insert( 'bitracker_sessions', array(
				'dsess_id'		=> $torrentSessionId,
				'dsess_mid'		=> (int) \IPS\Member::loggedIn()->member_id,
				'dsess_ip'		=> \IPS\Request::i()->ipAddress(),
				'dsess_file'	=> $this->file->id,
				'dsess_start'	=> time()
			) );
		}

		/* If a user aborts the connection the shutdown function is not executed, and we need it to be */
		ignore_user_abort( true );

		register_shutdown_function( function() use( $torrentSessionId ) {
			\IPS\Db::i()->delete( 'bitracker_sessions', array( 'dsess_id=?', $torrentSessionId ) );
		} );
		
		/* If it's an AWS file just redirect to it */
		if ( $signedUrl = $file->generateTemporaryDownloadUrl() )
		{
			\IPS\Output::i()->redirect( $signedUrl );
		}

		/* Print the file, honoring ranges */
		try
		{
			if ( \IPS\Member::loggedIn()->group['bit_throttling'] AND ( !$this->file->isPaid() OR \IPS\Member::loggedIn()->group['bit_paid_restrictions'] ) )
			{
				$ranges	= new \IPS\Http\Ranges( $file, (int) \IPS\Member::loggedIn()->group['bit_throttling'] );
			}
			else
			{
				$ranges = new \IPS\Http\Ranges( $file );
			}
		}
		catch( \RuntimeException $e )
		{
			\IPS\Log::log( $e, 'file_torrent' );

			\IPS\Output::i()->error( 'bitracker_no_file', '4D161/J', 403, '' );
		}

		/* If using PHP-FPM, close the request so that __destruct tasks are run after data is flushed to the browser
			@see http://www.php.net/manual/en/function.fastcgi-finish-request.php */
		if( \function_exists( 'fastcgi_finish_request' ) )
		{
			fastcgi_finish_request();
		}

		exit;
	}
	
	/**
	 * Restore a previous version
	 *
	 * @return	void
	 */
	protected function restorePreviousVersion()
	{
		/* Permission check */
		if ( !$this->file->canEdit() or !\IPS\Member::loggedIn()->group['bit_bypass_revision'] )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2D161/5', 403, '' );
		}

		\IPS\Session::i()->csrfCheck();
		
		/* Load the desired version */
		try
		{
			$version = \IPS\Db::i()->select( '*', 'bitracker_filebackup', array( 'b_id=?', \IPS\Request::i()->version ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2D161/6', 404, '' );
		}
		
		/* Delete the current versions and any versions in between */
		foreach ( new \IPS\File\Iterator( \IPS\Db::i()->select( 'record_location', 'bitracker_torrents_records', array( 'record_file_id=? AND record_backup=0', $this->file->id ) ), 'bitracker_Torrents' ) as $file )
		{
			try
			{
				$file->delete();
			}
			catch ( \Exception $e ) {}
		}
		\IPS\Db::i()->delete( 'bitracker_torrents_records', array( 'record_file_id=? AND record_backup=0', $this->file->id ) );
		
		/* Delete any versions in between */
		foreach ( \IPS\Db::i()->select( 'b_records', 'bitracker_filebackup', array( 'b_fileid=? AND b_backup>?', $this->file->id, $version['b_backup'] ) ) as $backup )
		{
			foreach ( new \IPS\File\Iterator( \IPS\Db::i()->select( 'record_location', 'bitracker_torrents_records', array( array( 'record_type=?', 'upload' ), \IPS\Db::i()->in( 'record_id', explode( ',', $backup ) ) ) ), 'bitracker_Torrents' ) as $file )
			{
				try
				{
					$file->delete();
				}
				catch ( \Exception $e ) { }
			}
			foreach ( new \IPS\File\Iterator( \IPS\Db::i()->select( 'record_location', 'bitracker_torrents_records', array( array( 'record_type=?', 'ssupload' ), \IPS\Db::i()->in( 'record_id', explode( ',', $backup ) ) ) ), 'bitracker_Torrents' ) as $file )
			{
				try
				{
					$file->delete();
				}
				catch ( \Exception $e ) { }
			}
			
			\IPS\Db::i()->delete( 'bitracker_torrents_records', \IPS\Db::i()->in( 'record_id', explode( ',', $backup ) ) );
		}
		\IPS\Db::i()->delete( 'bitracker_filebackup', array( 'b_fileid=? AND b_backup>=?', $this->file->id, $version['b_backup'] ) );
		
		/* Restore the records */
		\IPS\Db::i()->update( 'bitracker_torrents_records', array( 'record_backup' => 0 ), array( 'record_file_id=?', $this->file->id ) );
		
		/* Update the file information */
		$this->file->name = $version['b_filetitle'];
		$this->file->desc = $version['b_filedesc'];
		$this->file->version = $version['b_version'];
		$this->file->changelog = $version['b_changelog'];
		$this->file->save();

		/* Moderator log */
		\IPS\Session::i()->modLog( 'modlog__action_restorebackup', array( (string) $this->file->url() => FALSE, $this->file->name => FALSE ), $this->file );

		/* Redirect */
		\IPS\Output::i()->redirect( $this->file->url() );
	}
	
	/**
	 * Toggle Previous Version Visibility
	 *
	 * @return	void
	 */
	protected function previousVersionVisibility()
	{
		/* Permission check */
		if ( !$this->file->canEdit() or !\IPS\Member::loggedIn()->group['bit_bypass_revision'] )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2D161/8', 403, '' );
		}

		\IPS\Session::i()->csrfCheck();
		
		/* Load the desired version */
		try
		{
			$version = \IPS\Db::i()->select( '*', 'bitracker_filebackup', array( 'b_id=?', \IPS\Request::i()->version ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2D161/7', 404, '' );
		}
		
		/* Change visibility */
		\IPS\Db::i()->update( 'bitracker_filebackup', array( 'b_hidden' => !$version['b_hidden'] ), array( 'b_id=?', $version['b_id'] ) );

		/* Moderator log */
		\IPS\Session::i()->modLog( 'modlog__action_visibilitybackup', array( (string) $this->file->url() => FALSE, $this->file->name => FALSE ), $this->file );
		
		/* Redirect */
		\IPS\Output::i()->redirect( $this->file->url()->setQueryString( 'changelog', $version['b_id'] ) );
	}
	
	/**
	 * Delete Previous Version
	 *
	 * @return	void
	 */
	protected function deletePreviousVersion()
	{
		/* Permission check */
		if ( !$this->file->canEdit() or !\IPS\Member::loggedIn()->group['bit_bypass_revision'] )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2D161/9', 403, '' );
		}

		\IPS\Session::i()->csrfCheck();
		
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		/* Load the desired version */
		try
		{
			$version = \IPS\Db::i()->select( '*', 'bitracker_filebackup', array( 'b_id=?', \IPS\Request::i()->version ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2D161/A', 404, '' );
		}

		/* Base file iterator */
		$fileIterator = function( $recordType, $storageExtension ) use( $version )
		{
			return new \IPS\File\Iterator(
				\IPS\Db::i()->select(
					'record_location', 'bitracker_torrents_records', array(
						array( 'record_type=?', $recordType ),
						\IPS\Db::i()->in( 'record_id', explode( ',', $version['b_records'] ) ),
						array( 'record_location NOT IN (?)', \IPS\Db::i()->select(
							'record_location', 'bitracker_torrents_records', array( 'record_type=?', $recordType ), NULL,
							NULL, 'record_location', 'COUNT(*) > 1'
						) )
					)
				), $storageExtension
			);
		};

		/* Delete */
		foreach ( $fileIterator( 'upload', 'bitracker_Torrents' ) as $file )
		{
			try
			{
				$file->delete();
			}
			catch ( \Exception $e ) { }
		}

		foreach ( $fileIterator( 'ssupload', 'bitracker_Screenshots' ) as $file )
		{
			try
			{
				$file->delete();
			}
			catch ( \Exception $e ) { }
		}

		\IPS\Db::i()->delete( 'bitracker_torrents_records', \IPS\Db::i()->in( 'record_id', explode( ',', $version['b_records'] ) ) );
		\IPS\Db::i()->delete( 'bitracker_filebackup', array( 'b_id=?', $version['b_id'] ) );

		/* Moderator log */
		\IPS\Session::i()->modLog( 'modlog__action_deletebackup', array( (string) $this->file->url() => FALSE, $this->file->name => FALSE ), $this->file );

		/* Redirect */
		\IPS\Output::i()->redirect( $this->file->url()->setQueryString( 'changelog', $version['b_id'] ) );
	}
	
	/**
	 * View download log
	 *
	 * @return	void
	 */
	protected function log()
	{
		/* Permission check */
		if ( !$this->file->canViewDownloaders() )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2D161/B', 403, '' );
		}
		
		$table = new \IPS\Helpers\Table\Db( 'bitracker_downloads', $this->file->url()->setQueryString( 'do', 'log' ), array( 'dfid=?', $this->file->id ) );
		$table->tableTemplate = array( \IPS\Theme::i()->getTemplate( 'view' ), 'logTable' );
		$table->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'view' ), 'logRows' );
		$table->sortBy = 'dtime';
		$table->limit = 10;

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'view' )->log( $this->file, (string) $table );
	}
	
	/**
	 * Upload a new version
	 *
	 * @return	void
	 */
	protected function newVersion()
	{
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_submit.js', 'bitracker', 'front' ) );

		/* Permission check */
		if ( !$this->file->canEdit() OR $this->file->hasPendingVersion() )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2D161/C', 403, '' );
		}
		
		$category = $this->file->container();
		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		/* Club */
		try
		{
			if ( $club = $category->club() )
			{
				\IPS\core\FrontNavigation::$clubTabActive = TRUE;
				\IPS\Output::i()->breadcrumb = array();
				\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), \IPS\Member::loggedIn()->language()->addToStack('module__core_clubs') );
				\IPS\Output::i()->breadcrumb[] = array( $club->url(), $club->name );
				\IPS\Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
			}
		}
		catch ( \OutOfRangeException $e ) { }
		
		/* Require approval */
		$requireApproval = FALSE;
		if( $category->bitoptions['moderation'] and $category->bitoptions['moderation_edits'] and !$this->file->canUnhide() )
		{
			$requireApproval = TRUE;
		}

		$postingInformation = ( $requireApproval ) ? \IPS\Theme::i()->getTemplate( 'forms', 'core' )->postingInformation( NULL, TRUE, TRUE ) : NULL;
		
		/* Build form */
		$form = new \IPS\Helpers\Form;
		$form->addHeader( 'new_version_details' );

		if ( $category->versioning !== 0 and \IPS\Member::loggedIn()->group['bit_bypass_revision'] )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'file_save_revision', TRUE ) );
		}

		if( $category->version_numbers )
		{
			$form->add( new \IPS\Helpers\Form\Text( 'file_version', $this->file->version, ( $category->version_numbers == 2 ) ? TRUE : FALSE, array( 'maxLength' => 32 ) ) );
		}
		$form->add( new \IPS\Helpers\Form\Editor( 'file_changelog', NULL, FALSE, array( 'app' => 'bitracker', 'key' => 'Bitracker', 'autoSaveKey' => "bitracker-{$this->file->id}-changelog", 'allowAttachments' => FALSE ) ) );

		$defaultFiles = iterator_to_array( $this->file->files( NULL, FALSE ) );
		if( !$category->multiple_files )
		{
			$defaultFiles = array_pop( $defaultFiles );
		}

		$fileField = new \IPS\Helpers\Form\Upload( 'files', $defaultFiles, ( !\IPS\Member::loggedIn()->group['bit_linked_files'] and !\IPS\Member::loggedIn()->group['bit_import_files'] ), array( 'storageExtension' => 'bitracker_Torrents', 'allowedFileTypes' => $category->types, 'maxFileSize' => $category->maxfile ? ( $category->maxfile / 1024 ) : NULL, 'multiple' => $category->multiple_files, 'retainDeleted' => TRUE ) );
		$fileField->label = \IPS\Member::loggedIn()->language()->addToStack('bitracker_file');
		$form->add( $fileField );

		$linkedFiles = iterator_to_array( \IPS\Db::i()->select( 'record_location', 'bitracker_torrents_records', array( 'record_file_id=? AND record_type=? AND record_backup=0 AND record_hidden=0', $this->file->id, 'link' ) ) );

		if ( \IPS\Member::loggedIn()->group['bit_linked_torrents'] )
		{
			$form->add( new \IPS\Helpers\Form\Stack( 'url_files', $linkedFiles, FALSE, array( 'stackFieldType' => 'Url' ), array( 'IPS\bitracker\File', 'blacklistCheck' ) ) );
		}
		else if ( \count( $linkedFiles ) > 0 )
		{
			$form->addMessage( 'url_files_no_perm' );
		}

		if ( \IPS\Member::loggedIn()->group['bit_import_torrents'] )
		{
			$form->add( new \IPS\Helpers\Form\Stack( 'import_files', array(), FALSE, array( 'placeholder' => \IPS\ROOT_PATH ), function( $val )
			{
				if( \is_array( $val ) )
				{
					foreach ( $val as $file )
					{
						if ( !is_file( $file ) )
						{
							throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack('err_import_files', FALSE, array( 'sprintf' => array( $file ) ) ) );
						}
					}
				}
			} ) );
		}

		if ( $category->bitoptions['allowss'] )
		{
			$screenshots = iterator_to_array( $this->file->screenshots( 2, FALSE ) );

			if( $this->file->_primary_screenshot and isset( $screenshots[ $this->file->_primary_screenshot ] ) )
			{
				$screenshots[ $this->file->_primary_screenshot ] = array( 'fileurl' => $screenshots[ $this->file->_primary_screenshot ], 'default' => true );
			}

			$image = TRUE;
			if ( $category->maxdims and $category->maxdims != '0x0' )
			{
				$maxDims = explode( 'x', $category->maxdims );
				$image = array( 'maxWidth' => $maxDims[0], 'maxHeight' => $maxDims[1] );
			}

			$form->add( new \IPS\Helpers\Form\Upload( 'screenshots', $screenshots, ( $category->bitoptions['reqss'] and !\IPS\Member::loggedIn()->group['bit_linked_files'] ), array(
				'storageExtension'	=> 'bitracker_Screenshots',
				'image'				=> $image,
				'maxFileSize'		=> $category->maxss ? ( $category->maxss / 1024 ) : NULL,
				'multiple'			=> TRUE,
				'retainDeleted'		=> TRUE,
				'template'			=> "bitracker.submit.screenshot",
			) ) );

			if ( \IPS\Member::loggedIn()->group['bit_linked_files'] )
			{
				$form->add( new \IPS\bitracker\Form\LinkedScreenshots( 'url_screenshots', array(
					'values'	=> iterator_to_array( \IPS\Db::i()->select( 'record_id, record_location', 'bitracker_torrents_records', array( 'record_file_id=? AND record_type=? AND record_backup=0 AND record_hidden=0', $this->file->id, 'sslink' ) )->setKeyField('record_id')->setValueField('record_location') ),
					'default'	=> $this->file->_primary_screenshot
				), FALSE, array( 'IPS\bitracker\File', 'blacklistCheck' ) ) );
			}
		}

		/* Check for any extra form elements */
		$this->file->newVersionFormElements( $form );

		/* Output */
		\IPS\Output::i()->title = $this->file->name;

		/* Handle submissions */
		if ( $values = $form->values() )
		{			
			/* Check */
			if ( empty( $values['files'] ) and empty( $values['url_files'] ) and empty( $values['import_files'] ) )
			{
				$form->error = \IPS\Member::loggedIn()->language()->addToStack('err_no_torrents');
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'submit' )->submissionForm( $form, $category, $category->message('subterms'), FALSE, 0, $postingInformation, $category->versioning !== 0 );
				return (string) $form;
			}
			elseif ( !$category->multiple_files AND \is_array( $values['files'] ) AND ( \count( $values['files'] ?? [] ) + \count( $values['url_files'] ?? [] ) + \count( $values['import_files'] ?? [] ) > 1 ) )
			{
				$form->error = \IPS\Member::loggedIn()->language()->addToStack('err_too_many_torrents');
				return \IPS\Theme::i()->getTemplate( 'submit' )->submissionForm( $form, $category, $category->message('subterms'), FALSE, 0, $postingInformation, $category->versioning !== 0 );
			}
			if ( $category->bitoptions['reqss'] and empty( $values['screenshots'] ) and empty( $values['url_screenshots'] ) )
			{
				$form->error = \IPS\Member::loggedIn()->language()->addToStack('err_no_screenshots');
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'submit' )->submissionForm( $form, $category, $category->message('subterms'), FALSE, 0, $postingInformation, $category->versioning !== 0 );
				return (string) $form;
			}
			
			/* Versioning */
			if( $requireApproval )
			{
				$fileObj = new \IPS\bitracker\File\PendingVersion;
				$fileObj->file_id = $this->file->id;
				$fileObj->member_id = \IPS\Member::loggedIn()->member_id;
				$fileObj->form_values = $values;
			}
			else
			{
				$fileObj = $this->file;
			}

			$existingRecords = array();
			$existingScreenshots = array();
			$existingLinks = array();
			$existingScreenshotLinks = array();
			if ( $category->versioning !== 0 and ( !\IPS\Member::loggedIn()->group['bit_bypass_revision'] or $values['file_save_revision'] ) )
			{
				$fileObj->saveVersion();
			}
			else
			{
				$existingRecords = array_unique( iterator_to_array( \IPS\Db::i()->select( 'record_id, record_location', 'bitracker_torrents_records', array( 'record_file_id=? AND record_type=? AND record_backup=?', $this->file->id, 'upload', 0 ) )->setKeyField('record_id')->setValueField('record_location') ) );
				$existingScreenshots = array_unique( iterator_to_array( \IPS\Db::i()->select( 'record_id, record_location', 'bitracker_torrents_records', array( 'record_file_id=? AND record_type=? AND record_backup=?', $this->file->id, 'ssupload', 0 ) )->setKeyField('record_id')->setValueField('record_location') ) );
				$existingLinks = array_unique( iterator_to_array( \IPS\Db::i()->select( 'record_id, record_location', 'bitracker_torrents_records', array( 'record_file_id=? AND record_type=? AND record_backup=?', $this->file->id, 'link', 0 ) )->setKeyField('record_id')->setValueField('record_location') ) );
                $existingScreenshotLinks = array_unique( iterator_to_array( \IPS\Db::i()->select( 'record_id, record_location', 'bitracker_torrents_records', array( 'record_file_id=? AND record_type=? AND record_backup=?', $this->file->id, 'sslink', 0 ) )->setKeyField('record_id')->setValueField('record_location') ) );
			}

			/* Files may not be an array since we have an option to limit to a single upload */
			if( !\is_array( $values['files'] ) )
			{
				$values['files'] = [ $values['files'] ];
			}

			/* Insert the new records */
			foreach ( $values['files'] as $file )
			{
				$key = array_search( (string) $file, $existingRecords );
				
				if ( $key !== FALSE )
				{
					unset( $existingRecords[ $key ] );
				}
				else
				{
					\IPS\Db::i()->insert( 'bitracker_torrents_records', array(
						'record_file_id'	=> $this->file->id,
						'record_type'		=> 'upload',
						'record_location'	=> (string) $file,
						'record_realname'	=> $file->originalFilename,
						'record_size'		=> $file->filesize(),
						'record_time'		=> time(),
						'record_hidden'		=> $requireApproval
					) );
				}
			}

			if ( isset( $values['import_files'] ) )
			{
				foreach ( $values['import_files'] as $path )
				{
					$file = \IPS\File::create( 'bitracker_Torrents', mb_substr( $path, mb_strrpos( $path, DIRECTORY_SEPARATOR ) + 1 ), file_get_contents( $path ) );
					
					$key = array_search( (string) $file, $existingRecords );
					if ( $key !== FALSE )
					{
						unset( $existingRecords[ $key ] );
					}
					else
					{
						\IPS\Db::i()->insert( 'bitracker_torrents_records', array(
							'record_file_id'	=> $this->file->id,
							'record_type'		=> 'upload',
							'record_location'	=> (string) $file,
							'record_realname'	=> $file->originalFilename,
							'record_size'		=> $file->filesize(),
							'record_time'		=> time(),
							'record_hidden'		=> $requireApproval
						) );
					}
				}
			}

			if ( isset( $values['url_files'] ) )
			{
				foreach ( $values['url_files'] as $url )
				{
					$key = array_search( $url, $existingLinks );
					if ( $key !== FALSE )
					{
						unset( $existingLinks[ $key ] );
					}
					else
					{
						\IPS\Db::i()->insert( 'bitracker_torrents_records', array(
							'record_file_id'	=> $this->file->id,
							'record_type'		=> 'link',
							'record_location'	=> (string) $url,
							'record_realname'	=> NULL,
							'record_size'		=> 0,
							'record_time'		=> time(),
							'record_hidden'		=> $requireApproval
						) );
					}
				}
			}

			if ( isset( $values['screenshots'] ) )
			{
				foreach ( $values['screenshots'] as $_key => $file )
				{
					/* If this was the primary screenshot, convert back */
					if( \is_array( $file ) )
					{
						$file = $file['fileurl'];
					}

					$key = array_search( (string) $file, $existingScreenshots );
					if ( $key !== FALSE )
					{
						\IPS\Db::i()->update( 'bitracker_torrents_records', array(
							'record_default'		=> ( \IPS\Request::i()->screenshots_primary_screenshot AND \IPS\Request::i()->screenshots_primary_screenshot == $_key ) ? 1 : 0
						), array( 'record_id=?', $_key ) );

						unset( $existingScreenshots[ $key ] );
					}
					else
					{
						$noWatermark = NULL;
						$watermarked = FALSE;
						if ( \IPS\Settings::i()->bit_watermarkpath )
						{
							try
							{
								$noWatermark = (string) $file;
								$watermark = \IPS\Image::create( \IPS\File::get( 'core_Theme', \IPS\Settings::i()->bit_watermarkpath )->contents() );
								$image = \IPS\Image::create( $file->contents() );
								$image->watermark( $watermark );
								$file = \IPS\File::create( 'bitracker_Screenshots', $file->originalFilename, $image );
								$watermarked = TRUE;
							}
							catch ( \Exception $e ) { }
						}
						
						/**
						 * We only need to generate a new thumbnail if we are using watermarking.
						 * If we are not, then we can simply use the previous thumbnail, if this existed previously, rather than generating a new one every time we upload a new version, which is unnecessary extra processing as well as disk usage.
						 * If we are, then it is impossible to know if the watermark has since changed, so we need to go ahead and do it anyway.
						 */
						$existing = NULL;
						if ( $watermarked !== TRUE )
						{
							try
							{
								$existing = \IPS\Db::i()->select( '*', 'bitracker_torrents_records', array( "record_location=? AND record_file_id=?", (string) $file, $this->file->id ), NULL, NULL, NULL, NULL, \IPS\Db::SELECT_FROM_WRITE_SERVER )->first();
							}
							catch( \UnderflowException $e ) {}
						}
						
						\IPS\Db::i()->insert( 'bitracker_torrents_records', array(
							'record_file_id'		=> $this->file->id,
							'record_type'			=> 'ssupload',
							'record_location'		=> (string) $file,
							'record_thumb'			=> ( $watermarked OR !$existing ) ? (string) $file->thumbnail( 'bitracker_Screenshots' ) : $existing['record_thumb'],
							'record_realname'		=> $file->originalFilename,
							'record_size'			=> $file->filesize(),
							'record_time'			=> time(),
							'record_no_watermark'	=> $noWatermark,
							'record_default'		=> ( \IPS\Request::i()->screenshots_primary_screenshot AND \IPS\Request::i()->screenshots_primary_screenshot == $_key ) ? 1 : 0,
							'record_hidden'			=> $requireApproval
						) );
					}
				}
			}

			if ( isset( $values['url_screenshots'] ) )
			{
				foreach ( $values['url_screenshots'] as $_key => $url )
				{
					$key = array_search( (string) $file, $existingScreenshotLinks );
					if ( $key !== FALSE )
					{
						\IPS\Db::i()->update( 'bitracker_torrents_records', array(
							'record_default'		=> ( \IPS\Request::i()->screenshots_primary_screenshot AND \IPS\Request::i()->screenshots_primary_screenshot == $_key ) ? 1 : 0
						), array( 'record_id=?', $_key ) );
						unset( $existingScreenshotLinks[ $key ] );
					}
					else
					{
						\IPS\Db::i()->insert( 'bitracker_torrents_records', array(
							'record_file_id'	=> $this->file->id,
							'record_type'		=> 'sslink',
							'record_location'	=> (string) $url,
							'record_realname'	=> NULL,
							'record_size'		=> 0,
							'record_time'		=> time(),
							'record_default'	=> ( \IPS\Request::i()->screenshots_primary_screenshot AND \IPS\Request::i()->screenshots_primary_screenshot == $_key ) ? 1 : 0,
							'record_hidden'		=> $requireApproval
						) );
					}
				}
			}
			
			$deletions = array( 'records' => array(), 'links' => array() );
			
			/* Delete any we're not using anymore */
			foreach ( $existingRecords as $recordId => $url )
			{
				$deletions['records'][ $recordId ] = array( 'handler' => 'bitracker_Torrents', 'url' => $url );
			}
			foreach ( $existingScreenshots as $recordId => $url )
			{
				$deletions['records'][ $recordId ] = array( 'handler' => 'bitracker_Screenshots', 'url' => $url );
			}
			foreach ( ( $existingLinks + $existingScreenshotLinks ) as $id => $url )
			{
				$deletions['links'][ $id ] = $id;
			}

            if( $requireApproval )
			{
				$fileObj->record_deletions = $deletions;
			}
            else
			{
				array_walk( $deletions['records'], function( $arr, $key  ) use( $fileObj ) {
					$fileObj->deleteRecords( $key, $arr['url'], $arr['handler'] );
				});
				$fileObj->deleteRecords( $deletions['links'] );

				/* Set the new details */
				$fileObj->version = ( isset( $values['file_version'] ) ) ? $values['file_version'] : NULL;
				$fileObj->changelog = $values['file_changelog'];
			}
			
			/* These are specific to unapproved updates */
			if ( !$requireApproval )
			{
				$fileObj->size = \floatval( \IPS\Db::i()->select( 'SUM(record_size)', 'bitracker_torrents_records', array( 'record_file_id=? AND record_type=? AND record_backup=0', $this->file->id, 'upload' ), NULL, NULL, NULL, NULL, \IPS\Db::SELECT_FROM_WRITE_SERVER )->first() );

				/* Work out the new primary screenshot */
				try
				{
					$this->file->primary_screenshot = \IPS\Db::i()->select( 'record_id', 'bitracker_torrents_records', array( 'record_file_id=? AND ( record_type=? OR record_type=? ) AND record_backup=0 AND record_hidden=0', $this->file->id, 'ssupload', 'sslink' ), 'record_default DESC, record_id ASC', NULL, NULL, NULL, \IPS\Db::SELECT_FROM_WRITE_SERVER )->first();
				}
				catch ( \UnderflowException $e ) { }
			}
			
			/* Save */
			$fileObj->updated = time();
			$fileObj->save();

			if( $requireApproval )
			{
				$this->file->save();
				$fileObj->sendUnapprovedNotification();
			}
			else
			{
				/* Send notifications */
				if ( $this->file->open )
				{
					$this->file->sendUpdateNotifications();
				}
				else
				{
					$this->file->sendUnapprovedNotification();
				}

				$this->file->processAfterNewVersion( $values );
			}

			/* Boink */
			\IPS\Output::i()->redirect( $this->file->url() );
		}
		
		/* Set navigation */
		try
		{
			foreach ( $category->parents() as $parent )
			{
				\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
			}
			\IPS\Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
		}
		catch ( \Exception $e ) { }
		\IPS\Output::i()->breadcrumb[] = array( $this->file->url(), $this->file->name );

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'submit' )->submissionForm( $form, $this->file->container(), $this->file->container()->message('subterms'), FALSE, 0, $postingInformation, $category->versioning !== 0 );
	}
	
	/**
	 * Change Author
	 *
	 * @return	void
	 */
	public function changeAuthor()
	{
		/* Permission check */
		if ( !$this->file->canChangeAuthor() )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2D161/D', 403, '' );
		}
		
		/* Build form */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Member( 'author', NULL, TRUE ) );
		$form->class .= 'ipsForm_vertical';

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$this->file->changeAuthor( $values['author'] );			
			\IPS\Output::i()->redirect( $this->file->url() );
		}
		
		/* Display form */
		\IPS\Output::i()->output = $form->customTemplate( array( \IPS\Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}

	/**
	 * Subscribe
	 *
	 * @return void
	 */
	function toggleSubscription()
	{
		\IPS\Session::i()->csrfCheck();

		if( $this->file->subscribed() )
		{
			\IPS\Db::i()->delete( 'bitracker_torrents_notify', array( 'notify_member_id=? and notify_file_id=?', \IPS\Member::loggedIn()->member_id, $this->file->id ) );
			$subscribed = FALSE;
		}
		else
		{
			\IPS\Db::i()->replace( 'bitracker_torrents_notify', array( 'notify_member_id' => \IPS\Member::loggedIn()->member_id, 'notify_file_id' => $this->file->id ) );
			$subscribed = TRUE;
		}

		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( $subscribed ? 'subscribed' : 'unsubscribed' );
		}
		else
		{
			\IPS\Output::i()->redirect( $this->file->url(), $subscribed ? 'file_subscribed' : 'file_unsubscribed' );
		}
	}

	/**
	 * Subscribe Hover
	 *
	 * @return void
	 */
	function subscribeBlurb()
	{
		$notificationConfiguration = \IPS\Member::loggedIn()->notificationsConfiguration();
		$notificationConfiguration = isset( $notificationConfiguration[ 'new_file_version' ] ) ? $notificationConfiguration[ 'new_file_version' ] : array();

		$options = NULL;
		if( \count( $notificationConfiguration ) )
		{
			foreach( $notificationConfiguration as $option )
			{
				$methods[] = \IPS\Member::loggedIn()->language()->addToStack( 'member_notifications_' . $option );
			}

			$options = \IPS\Member::loggedIn()->language()->formatList( $methods );
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'view' )->notifyBlurb( $this->file, $options );
	}
}