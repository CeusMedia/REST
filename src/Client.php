<?php
/**
 *	...
 *
 *	Copyright (c) 2007-2019 Christian Würker (ceusmedia.de)
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_REST
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
namespace CeusMedia\REST;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_REST
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class Client
{
	protected $username;
	protected $password;
	protected $baseUri;
	protected $expectedFormat	= "HTML";
	protected $options			= array();
	protected $requestHeaders	= array();
	protected $responseHeader;
	protected $setCurlOptions	= array();
	protected $logErrors;
	protected $logRequests;
	protected $handler;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$baseUri		REST server base URL
	 *	@param		array		$options		Map of connection options
	 *	@return		void
	 */
	public function __construct( $baseUri, $options = array() )
	{
		if( !extension_loaded( 'curl' ) )
			throw new \RuntimeException( "Support for cURL is missing" );
		$this->options	= array() + $options;
		$this->baseUri	= $baseUri;
		$this->handler	= curl_init();

		$callbackHeaderFunction	= array( $this, 'callbackHeaderFunction' );
		$this->setCurlOption( CURLOPT_HEADER, FALSE );
		$this->setCurlOption( CURLOPT_RETURNTRANSFER, TRUE );
		$this->setCurlOption( CURLOPT_HEADERFUNCTION, $callbackHeaderFunction );
		foreach( $this->options as $key => $value ){
			$this->setCurlOption( (int) $key, $value );
		}
	}

	public function addRequestHeader( $key, $value )
	{
		$this->requestHeaders[]	= $key.": ".$value;
	}

	protected function callbackHeaderFunction( $handler, $header )
	{
		$this->responseHeader	.= $header;
		return strlen( $header );
	}

	protected function buildPostFields( $data )
	{
		if( is_object( $data ) ){
			if( method_exists( $data, 'toArray' ) )
				$data	= $data->toArray();
			else if( method_exists( $data, '__toArray' ) )
				$data	= $data->__toArray();
			else
				$data	= (array) $data;
		}
		return http_build_query( $data, '', '&' );
	}

	public function expectFormat( $format )
	{
		$this->expectedFormat	= $format;
	}

	/**
	 *	Read resource from server.
	 *	@access		public
	 *	@param		string		$path			Resource path to request
	 *	@param		array		$parameters		Map of GET parameters
	 *	@return		mixed
	 */
	public function get( $path, $parameters = array() )
	{
		if( $parameters )
			$path	.= "?".$this->buildPostFields( $parameters );
		$this->setCurlOption( CURLOPT_CUSTOMREQUEST, 'GET' );
		$this->setCurlOption( CURLOPT_URL, $this->baseUri.$path );
		return $this->handleRequest();
	}

	/**
	 *	Returns option on CURL handler, if set.
	 *	@access		protected
	 *	@param		integer		$key		CURL option key (constant)
	 *	@return		mixed|NULL
	 */
	protected function getCurlOption( $key )
	{
		if( isset( $this->setCurlOptions[$key] ) )
			return $this->setCurlOptions[$key];
		return NULL;
	}

	protected function logRequest()
	{
		if( !$this->logRequests )
			return;
		$info		= curl_getinfo( $this->handler );
		$message	= sprintf(
			'%s %s %d %s',
			date( 'Y-m-d H:i:s' ),
			$this->getCurlOption( CURLOPT_CUSTOMREQUEST ),
			$info['http_code'],
			$this->getCurlOption( CURLOPT_URL )
		);
		error_log( $message.PHP_EOL, 3, $this->logRequests );
	}

	/**
	 *	Create resource on server.
	 *	@access		public
	 *	@param		string		$path			Resource path to request
	 *	@param		array		$data			Map of POST parameters
	 *	@return		mixed
	 */
	public function post( $path, $data = array() )
	{
		$this->setCurlOption( CURLOPT_CUSTOMREQUEST, 'POST' );
		$this->setCurlOption( CURLOPT_POSTFIELDS, $this->buildPostFields( $data ) );
		$this->setCurlOption( CURLOPT_URL, $this->baseUri.$path );
		return $this->handleRequest();
	}

	/**
	 *	Update resource on server.
	 *	@access		public
	 *	@param		string		$path			Resource path to request
	 *	@param		array		$data			Map of PUT parameters
	 *	@return		mixed
	 */
	public function put( $path, $data = array() )
	{
		$this->setCurlOption( CURLOPT_CUSTOMREQUEST, 'PUT' );
		$this->setCurlOption( CURLOPT_POSTFIELDS, $this->buildPostFields( $data ) );
		$this->setCurlOption( CURLOPT_URL, $this->baseUri.$path );
		return $this->handleRequest();
	}

	/**
	 *	Remove resource on server.
	 *	@access		public
	 *	@param		string		$path			Resource path to request
	 *	@return		mixed
	 */
	public function delete( $path )
	{
		$this->setCurlOption( CURLOPT_CUSTOMREQUEST, 'DELETE' );
		$this->setCurlOption( CURLOPT_URL, $this->baseUri.$path );
		return $this->handleRequest();
	}

	protected function handleRequest()
	{
		$this->responseHeader	= '';
		$headers	= $this->requestHeaders;

		switch( $this->expectedFormat ){
			case 'HTML':
				$headers[]	= 'Accept: text/html;q=1';
				break;
			case 'JSON':
				$headers[]	= 'Accept: application/json;q=1';
				break;
			case 'PHP':
				$headers[]	= 'Accept: application/x-php;q=1';
				break;
		}

//		$this->setCurlOption( CURLINFO_HEADER_OUT, TRUE );
		$this->setCurlOption( CURLOPT_HTTPHEADER, $headers );

		$body		= curl_exec( $this->handler );
		$error		= curl_errno( $this->handler );
		$info		= curl_getinfo( $this->handler );
		$this->logRequest();

		if( $error )
			throw new Client\RequestException( curl_error( $this->handler ), $error );

		if( $info['http_code'] >= 400 )
			throw new Client\ResponseException( $body, $info['http_code'] );
//		$this->requestHeader	= curl_getinfo( $this->handler, CURLINFO_HEADER_OUT );

		$responseHeaderFields	= \Net_HTTP_Header_Parser::parse( $this->responseHeader );

		$links		= array();
		foreach( $responseHeaderFields->getFieldsByName( 'Link' ) as $link ){
			$value	= $link->getValue();
/*			if( preg_match( "/;rel=[^;])/", $value ) ){}*/
			$links[]	= $value;
		}

		switch( $this->expectedFormat ){
			case 'HTML':
				break;
			case 'JSON':
				$body	= (object) array(
					'data'		=> json_decode( $body ),
	//				'headers'	=> $headers->getFields
					'links'		=> $links,
				);
				break;
			case 'PHP':
				$body	= array(
					'data'	=> unserialize( $body ),
					'links'	=> $links,
				);
				break;
		}
		return $body;
	}

	/**
	 *	Set credentials for HTTP Basic Authentication.
	 *	@access		public
	 *	@param		string		$username	HTTP Basic Auth username
	 *	@param		string		$password	HTTP Basic Auth password
	 *	@return		void
	 */
	public function setBasicAuth( $username, $password )
	{
		if( !strlen( trim( $username ) ) )
			return;
		$encoded	= base64_encode( $username . ':' . $password );
		$this->requestHeaders[]	= 'Authentication: Basic ' . $encoded;
		$this->setCurlOption( CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		$this->setCurlOption( CURLOPT_USERPWD, $username . ':' . $password );
	}

	/**
	 *	Sets option on CURL handler. Wrapper for curl_setopt.
	 *	Will note set options to be able to get set options later.
	 *	@access		protected
	 *	@param		integer		$key		CURL option key (constant)
	 *	@param		mixed		$value		Value of CURL option to set
	 *	@return		void
	 */
	protected function setCurlOption( $key, $value )
	{
		$this->setCurlOptions[$key]	= $value;
		curl_setopt( $this->handler, $key, $value );
	}

	public function setLogErrors( $filePath )
	{
		if( !file_exists( dirname( $filePath ) ) )
			\FS_Folder_Editor::createFolder( $filePath );
		$this->logErrors	= $filePath;
	}

	public function setLogRequests( $filePath )
	{
		if( !file_exists( dirname( $filePath ) ) )
			\FS_Folder_Editor::createFolder( $filePath );
		$this->logRequests	= $filePath;
	}
}
