<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       BitTracker Request Class
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4x
 * @subpackage	BitTracker
 * @version     2.0.0 RC 3
 * @source      https://github.com/GaalexxC/IPS-4.4-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     19 JUN 2019
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
 * HTTP Request Class
 */
class _Request extends \IPS\Patterns\Singleton
{
	/**
	 * @brief	Singleton Instance
	 */
	protected static $instance = NULL;
	
	/**
	 * @brief	Cookie data
	 */
	public $cookie = array();
	
	/**
	 * Constructor
	 *
	 * @return	void
	 * @note	We do not unset $_COOKIE as it is needed by session handling
	 */
	public function __construct()
	{
		if ( isset( $_SERVER['REQUEST_METHOD'] ) AND $_SERVER['REQUEST_METHOD'] == 'PUT' )
		{
			parse_str( file_get_contents('php://input'), $params );
			$this->parseIncomingRecursively( $params );
		}
		else
		{
			$this->parseIncomingRecursively( $_GET );
			$this->parseIncomingRecursively( $_POST );
		}
						
		array_walk_recursive( $_COOKIE, array( $this, 'clean' ) );

		/* If we have a cookie prefix, we have to strip it first */
		if( \IPS\COOKIE_PREFIX !== NULL )
		{
			foreach( $_COOKIE as $key => $value )
			{
				if( \IPS\COOKIE_PREFIX !== null )
				{
					if( mb_strpos( $key, \IPS\COOKIE_PREFIX ) === 0 )
					{
						$this->cookie[ preg_replace( "/^" . \IPS\COOKIE_PREFIX . "(.+?)/", "$1", $key ) ]	= $value;
					}
				}
				else
				{
					$this->cookie[ $key ]	= $value;
				}
			}
		}
		else
		{
			$this->cookie = $_COOKIE;
		}
	}

	/**
	 * Parse Incoming Data
	 *
	 * @param	array	$data	Data
	 * @return	void
	 */
	protected function parseIncomingRecursively( $data )
	{
		foreach( $data as $k => $v )
		{
			if ( \is_array( $v ) )
			{
				array_walk_recursive( $v, array( $this, 'clean' ) );
			}
			else
			{
				$this->clean( $v, $k );
			}

			/* We used to call $this->$k = $v but that resulted in breaking our cookie array if a &cookie=1 parameter was passed in the URL */
			$this->data[ $k ] = $v;
		}
	}
	
	/**
	 * Is this an SSL/Secure request?
	 *
	 * @return	bool
	 * @note	A common technique to check for SSL is to look for $_SERVER['SERVER_PORT'] == 443, however this is not a correct check. Nothing requires SSL to be on port 443, or http to be on port 80.
	 */
	public function isSecure()
	{
		if( !empty( $_SERVER['HTTPS'] ) AND mb_strtolower( $_SERVER['HTTPS'] ) == 'on' )
		{
			return TRUE;
		}
		else if( !empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) AND mb_strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) == 'https' )
		{
			return TRUE;
		}
		else if ( !empty( $_SERVER['HTTP_X_FORWARDED_HTTPS'] ) AND mb_strtolower( $_SERVER['HTTP_X_FORWARDED_HTTPS'] ) == 'https' )
		{
			return TRUE;
		}
		else if( !empty( $_SERVER['HTTP_FRONT_END_HTTPS'] ) AND mb_strtolower( $_SERVER['HTTP_FRONT_END_HTTPS'] ) == 'on' )
		{
			return TRUE;
		}
		else if( !empty( $_SERVER['HTTP_SSLSESSIONID'] ) )
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Clean Value
	 *
	 * @param	mixed	$v	Value
	 * @param	mixed	$k	Key
	 * @return	void
	 */
	protected function clean( &$v, $k )
	{
		/* Remove NULL bytes and the RTL control byte */
		$v = str_replace( array( "\0", "\u202E" ), '', $v );
		
		/* Undo magic quote madness */
		if ( get_magic_quotes_gpc() === 1 )
		{
			$v = stripslashes( $v );
		}
	}

	/**
	 * Get current URL
	 *
	 * @return	\IPS\Http\Url
	 */
	public function url()
	{
		if( $this->_url === NULL )
		{
			$url = $this->isSecure() ? 'https' : 'http';
			$url .= '://';
			
			/* Nginx uses HTTP_X_FORWARDED_SERVER. @see <a href='https://plone.lucidsolutions.co.nz/web/reverseproxyandcache/setting-nginx-http-x-forward-headers-for-reverse-proxy'>Nginx Reverse Proxy</a> */
			if ( !empty( $_SERVER['HTTP_X_FORWARDED_SERVER'] ) )
			{
				if ( isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) AND mb_strstr( $_SERVER['HTTP_X_FORWARDED_HOST'], ':' ) === FALSE )
				{
					$url .= $_SERVER['HTTP_X_FORWARDED_HOST'];
				}
				else
				{
					$url .= $_SERVER['HTTP_X_FORWARDED_SERVER'];
				}
			}
			elseif ( !empty( $_SERVER['HTTP_X_FORWARDED_HOST'] ) )
			{
				$url .= $_SERVER['HTTP_X_FORWARDED_HOST'];
			}
			elseif ( !empty( $_SERVER['HTTP_HOST'] ) )
			{
				$url .= $_SERVER['HTTP_HOST'];
			}
			else
			{
				$url .= $_SERVER['SERVER_NAME'];
			}

			return $this->_url = \IPS\Http\Url::createFromString( $url, TRUE, TRUE )  . "/announce";
		}

		return $this->_url;
	}

	
	/**
	 * Get IP Address
	 *
	 * @return	string
	 */
	public function ipAddress()
	{
		$addrs = array();
		
		if ( \IPS\Settings::i()->xforward_matching )
		{
			if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
			{
				foreach( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) as $x_f )
				{
					$addrs[] = trim( $x_f );
				}
			}

			if( isset( $_SERVER['HTTP_CLIENT_IP'] ) )
			{
				$addrs[] = $_SERVER['HTTP_CLIENT_IP'];
			}
			
			if ( isset( $_SERVER['HTTP_X_CLIENT_IP'] ) )
			{
				$addrs[] = $_SERVER['HTTP_X_CLIENT_IP'];
			}

			if( isset( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ) )
			{
				$addrs[] = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
			}

			if( isset( $_SERVER['HTTP_PROXY_USER'] ) )
			{
				$addrs[] = $_SERVER['HTTP_PROXY_USER'];
			}
		}
		
		if ( isset( $_SERVER['REMOTE_ADDR'] ) )
		{
			$addrs[] = $_SERVER['REMOTE_ADDR'];
		}
		
		foreach ( $addrs as $ip )
		{
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) )
			{
				return $ip;
			}
		}

		return '';
	}
	
	/**
	 * IP address is banned?
	 *
	 * @return	bool
	 */
	public function ipAddressIsBanned()
	{
		if ( isset( \IPS\Data\Store::i()->bannedIpAddresses ) )
		{
			$bannedIpAddresses = \IPS\Data\Store::i()->bannedIpAddresses;
		}
		else
		{
			$bannedIpAddresses = iterator_to_array( \IPS\Db::i()->select( 'ban_content', 'core_banfilters', array( "ban_type=?", 'ip' ) ) );
			\IPS\Data\Store::i()->bannedIpAddresses = $bannedIpAddresses;
		}
		foreach ( $bannedIpAddresses as $ip )
		{
			if ( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( trim( $ip ), '/' ) ) . '$/', $this->ipAddress() ) )
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}

	/**
	 * Returns the cookie path
	 *
	 * @return string
	 */
	public static function getCookiePath()
	{
		if( \IPS\COOKIE_PATH !== NULL )
		{
			return \IPS\COOKIE_PATH;
		}

		$path = mb_substr( \IPS\Settings::i()->base_url, mb_strpos( \IPS\Settings::i()->base_url, ( !empty( $_SERVER['SERVER_NAME'] ) ) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'] ) + mb_strlen( ( !empty( $_SERVER['SERVER_NAME'] ) ) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'] ) );
		$path = mb_substr( $path, mb_strpos( $path, '/' ) );
		
		return $path;
	}
	
	/**
	 * Set a cookie
	 *
	 * @param	string				$name		Name
	 * @param	mixed				$value		Value
	 * @param	\IPS\DateTime|null	$expire		Expiration date, or NULL for on session end
	 * @param	bool				$httpOnly	When TRUE the cookie will be made accessible only through the HTTP protocol
	 * @param	string|null			$domain		Domain to set to. If NULL, will be detected automatically.
	 * @param	string|null			$path		Path to set to. If NULL, will be detected automatically.
	 * @return	bool
	 */
	public function setCookie( $name, $value, $expire=NULL, $httpOnly=TRUE, $domain=NULL, $path=NULL )
	{
		/* Work out the path and if cookies should be SSL only */
		$sslOnly	= FALSE;
		if( mb_substr( \IPS\Settings::i()->base_url, 0, 5 ) == 'https' AND \IPS\COOKIE_BYPASS_SSLONLY !== TRUE )
		{
			$sslOnly	= TRUE;
		}
		$path = $path ?: static::getCookiePath();

		/* Are we forcing a cookie domain? */
		if( \IPS\COOKIE_DOMAIN !== NULL AND $domain === NULL )
		{
			$domain	= \IPS\COOKIE_DOMAIN;
		}
		
		$realName = $name;
		
		/* What about a prefix? */
		if( \IPS\COOKIE_PREFIX !== NULL )
		{
			$name	= \IPS\COOKIE_PREFIX . $name;
		}
				
		/* Set the cookie */
		if ( setcookie( $name, $value, $expire ? $expire->getTimestamp() : 0, $path, $domain ?: '', $sslOnly, $httpOnly ) === TRUE )
		{
			$this->cookie[ $realName ] = $value;

			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Clear login cookies
	 *
	 * @return	void
	 */
	public function clearLoginCookies()
	{
		$this->setCookie( 'member_id', NULL );
		$this->setCookie( 'login_key', NULL );
		$this->setCookie( 'loggedIn', NULL );
		$this->setCookie( 'guestTime', NULL );
		$this->setCookie( 'noCache', NULL );

		foreach( $this->cookie as $name => $value )
		{
			if( mb_strpos( $name, "ipbforumpass_" ) !== FALSE )
			{
				$this->setCookie( $name, NULL );
			}
		}
	}
	
	/**
	 * @brief	Editor autosave keys to be cleared
	 */
	protected $clearAutoSaveCookie = array();
	
	/**
	 * Set cookie to clear autosave content from editor
	 *
	 * @param	$autoSaveKey	string	The editor's autosave key
	 * @return	void
	 */
	public function setClearAutosaveCookie( $autoSaveKey )
	{
		$this->clearAutoSaveCookie[] = $autoSaveKey;
		\IPS\Request::i()->setCookie( 'clearAutosave', implode( ',', $this->clearAutoSaveCookie ), NULL, FALSE );
	}
	
	/**
	 * Returns the request method
	 *
	 * @return string
	 */
	public function requestMethod()
	{
		return mb_strtoupper( $_SERVER['REQUEST_METHOD'] );
	}
	
	/**
	 * Flood Check
	 *
	 * @return	void
	 */
	public static function floodCheck()
	{
		$groupFloodSeconds = \IPS\Member::loggedIn()->group['g_search_flood'];
		
		if ( \IPS\Session::i()->userAgent->spider )
		{
			/* Force a 30 second flood control so if guests have it switched off, or set very low, you do not get flooded by known bots */
			$groupFloodSeconds = \IPS\BOT_SEARCH_FLOOD_SECONDS;
		}
		
		/* Flood control */
		if( $groupFloodSeconds )
		{
			$time = ( isset( \IPS\Request::i()->cookie['lastSearch'] ) ) ? \IPS\Request::i()->cookie['lastSearch'] : 0;
			
			if( $time and ( time() - $time ) < $groupFloodSeconds )
			{
				$secondsToWait = $groupFloodSeconds - ( time() - $time );
				\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack( 'search_flood_error', FALSE, array( 'pluralize' => array( $secondsToWait ) ) ), '1C205/3', 429, \IPS\Member::loggedIn()->language()->addToStack( 'search_flood_error_admin', FALSE, array( 'pluralize' => array( $secondsToWait ) ) ), array( 'Retry-After' => \IPS\DateTime::create()->add( new \DateInterval( 'PT' . $secondsToWait . 'S' ) )->format('r') ) );
			}
	
			$expire = new \IPS\DateTime;
			\IPS\Request::i()->setCookie( 'lastSearch', time(), $expire->add( new \DateInterval( 'PT' . \intval( $groupFloodSeconds ) . 'S' ) ) );
		}
	}

	/**
	 * Is PHP running as CGI?
	 *
	 * @note	Possible values: cgi, cgi-fcgi, fpm-fcgi
	 * @return	boolean
	 */
	public function isCgi()
	{
		if ( \substr( PHP_SAPI, 0, 3 ) == 'cgi' OR \substr( PHP_SAPI, -3 ) == 'cgi' )
		{
			return true;
		}
		
		return false;	
	}
	
	/**
	 * Confirmation check
	 *
	 * @param	string		$title		Lang string key for title
	 * @param	string		$message	Lang string key for confirm message
	 * @param	string		$submit		Lang string key for submit button
	 * @return	bool
	 */
	public function confirmedDelete( $title = 'delete_confirm', $message = 'delete_confirm_detail', $submit = 'delete' )
	{
		/* The confirmation dialogs will send form_submitted=1, as will displaying a form, so we check for this.
			If the admin (or user) simply visited a delete URL directly, this would not be included in the request. */
		if ( ! isset( \IPS\Request::i()->wasConfirmed ) )
		{
			$form = new \IPS\Helpers\Form( 'form', $submit );
			$form->hiddenValues['wasConfirmed']	= 1;
			$form->addMessage( $message, 'ipsMessage ipsMessage_warning' );

			/* We call sendOutput() to show the form now */
			\IPS\Output::i()->output = $form;
			\IPS\Output::i()->title	 = \IPS\Member::loggedIn()->language()->addToStack( $title );
			
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->genericBlock( $form, \IPS\Output::i()->title ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
			}
			else
			{
				\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( \IPS\Output::i()->title, \IPS\Output::i()->output, array( 'app' => \IPS\Dispatcher::i()->application->directory, 'module' => \IPS\Dispatcher::i()->module->key, 'controller' => \IPS\Dispatcher::i()->controller ) ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
			}
		}

		/* If we are here, we're all good! */
		return TRUE;
	}
	
	/**
	 * Old IPB escape-on-input routine
	 *
	 * @param	string|object	$val		The unescaped text (can be a string or an object that can be cast to a string)
	 * @return	string			The IPB3-style escaped text
	 */
	public static function legacyEscape( $val )
	{
		$val = (string) $val;
		
		$val = str_replace( "&"			, "&amp;"         , $val );
		$val = str_replace( "<!--"		, "&#60;&#33;--"  , $val );
		$val = str_replace( "-->"			, "--&#62;"       , $val );
		$val = str_ireplace( "<script"	, "&#60;script"   , $val );
		$val = str_replace( ">"			, "&gt;"          , $val );
		$val = str_replace( "<"			, "&lt;"          , $val );
		$val = str_replace( '"'			, "&quot;"        , $val );
		$val = str_replace( "\n"			, "<br />"        , $val );
		$val = str_replace( "$"			, "&#036;"        , $val );
		$val = str_replace( "!"			, "&#33;"         , $val );
		$val = str_replace( "'"			, "&#39;"         , $val );
		$val = str_replace( "\\"			, "&#092;"        , $val );
		
		return $val;
	}
	
	/**
	 * Get our referrer, looking for a specific request variable, then falling back to the header
	 *
	 * @param	bool		$allowExternal	If set to TRUE, external URL's will be allowed and returned.
	 * @param	bool		$onlyRequest	If set to TRUE, will only look for the "ref" request parameter. Useful if you need to look for HTTP_REFERER at a specific point in time.
	 * @param	string|NULL	$base			If set, will only return URL's with this base.
	 * @return	\IPS\Http\Url|NULL
	 */
	public function referrer( bool $allowExternal=FALSE, bool $onlyRequest=FALSE, ?string $base = NULL ): ?\IPS\Http\Url
	{
		/* Do we have a _ref request parameter? */
		$ref = NULL;
		if ( isset( $this->ref ) )
		{
			$ref = @base64_decode( $this->ref );
		}
		
		/* Maybe not - check HTTP_REFERER */
		if ( !$ref AND !$onlyRequest AND !empty( $_SERVER['HTTP_REFERER'] ) )
		{
			$ref = $_SERVER['HTTP_REFERER'];
		}
		
		/* Did that work? */
		if ( $ref )
		{
			try
			{
				$ref = \IPS\Http\Url::createFromString( $ref );
			}
			catch( \IPS\Http\Url\Exception $e )
			{
				/* Failed to create? Nope. */
				return NULL;
			}
			
			/* Return if URL is internal and not an open redirect, or if we're allowing external referrer references */
			if ( ( ( $ref instanceof \IPS\Http\Url\Internal ) AND !$ref->openRedirect() ) OR $allowExternal )
			{
				if ( $base !== NULL AND ( $ref instanceof \IPS\Http\Url\Internal ) )
				{
					if ( $ref->base === $base )
					{
						return $ref;
					}
					else
					{
						return NULL;
					}
				}
				else
				{
					return $ref;
				}
			}
			else
			{
				return NULL;
			}
		}
		
		/* Still here? Nothing worked */
		return NULL;
	}
}