<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

/**
 *	...
 *
 *	Copyright (c) 2007-2023 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
namespace CeusMedia\REST;

use CeusMedia\Common\FS\Folder\Editor as FolderEditor;
use CeusMedia\Common\Net\HTTP\Header\Field;
use CeusMedia\Common\Net\HTTP\Header\Parser as HttpHeaderParser;
use CeusMedia\Router\Log;
use CurlHandle;
use RuntimeException;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_REST
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class Client
{
	protected string $baseUri;
	protected ?string $expectedFormat	= NULL;
	protected array $options			= [];
	protected array $requestHeaders		= [];
	protected string $responseHeader	= '';
	protected array $setCurlOptions		= [];
	protected ?string $logErrors		= NULL;
	protected ?string $logRequests		= NULL;
	protected CurlHandle $handler;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		string		$baseUri		REST server base URL
	 *	@param		array		$options		Map of connection options
	 *	@return		void
	 */
	public function __construct( string $baseUri, array $options = [] )
	{
		if( !extension_loaded( 'curl' ) )
			throw new RuntimeException( "Support for cURL is missing" );
		$this->options	= [] + $options;
		$this->baseUri	= $baseUri;

		/** @var CurlHandle|FALSE $curlHandle */
		$curlHandle		= curl_init();
		if( FALSE === $curlHandle )
			throw new RuntimeException( "Creating a cURL handle failed" );

		$this->handler			= $curlHandle;
		$callbackHeaderFunction	= [$this, 'callbackHeaderFunction'];
		$this->setCurlOption( CURLOPT_HEADER, FALSE );
		$this->setCurlOption( CURLOPT_RETURNTRANSFER, TRUE );
		$this->setCurlOption( CURLOPT_HEADERFUNCTION, $callbackHeaderFunction );
		foreach( $this->options as $key => $value ){
			$this->setCurlOption( (int) $key, $value );
		}
	}

	public function addRequestHeader( string $key, int|float|string $value ): self
	{
		$this->requestHeaders[]	= $key.": ".$value;
		return $this;
	}

	public function expectFormat( string $format ): self
	{
		$this->expectedFormat	= $format;
		return $this;
	}

	/**
	 *	Read resource from server.
	 *	@access		public
	 *	@param		string		$path			Resource path to request
	 *	@param		array		$parameters		Map of GET parameters
	 *	@return		object|array|string
	 */
	public function get( string $path, array $parameters = [] ): object|array|string
	{
		if( count( $parameters ) > 0 )
			$path	.= "?".$this->buildPostFields( $parameters );
		$this->setCurlOption( CURLOPT_CUSTOMREQUEST, 'GET' );
		$this->setCurlOption( CURLOPT_URL, $this->baseUri.$path );
		return $this->handleRequest();
	}

	/**
	 *	Create resource on server.
	 *	@access		public
	 *	@param		string		$path			Resource path to request
	 *	@param		object|array|string		$data			Map of POST parameters
	 *	@return		object|array|string
	 */
	public function post( string $path, object|array|string $data = [] ): object|array|string
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
	 *	@return		object|array|string
	 */
	public function put( string $path, array $data = [] ): object|array|string
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
	 *	@return		object|array|string
	 */
	public function delete( string $path ): object|array|string
	{
		$this->setCurlOption( CURLOPT_CUSTOMREQUEST, 'DELETE' );
		$this->setCurlOption( CURLOPT_URL, $this->baseUri.$path );
		return $this->handleRequest();
	}

	/**
	 *	Set credentials for HTTP Basic Authentication.
	 *	@access		public
	 *	@param		string		$username	HTTP Basic Auth username
	 *	@param		string		$password	HTTP Basic Auth password
	 *	@return		self
	 */
	public function setBasicAuth( string $username, string $password ): self
	{
		if( strlen( trim( $username ) ) > 0 ){
			$encoded	= base64_encode( $username . ':' . $password );
			$this->requestHeaders[]	= 'Authentication: Basic ' . $encoded;
			$this->setCurlOption( CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
			$this->setCurlOption( CURLOPT_USERPWD, $username . ':' . $password );
		}
		return $this;
	}

	/**
	 *	Set path of error log file.
	 *	@access		public
	 *	@param		?string		$filePath		Path of error log file
	 *	@return		self
	 */
	public function setLogErrors( ?string $filePath ): self
	{
		if( !is_null( $filePath ) && !file_exists( dirname( $filePath ) ) )
			FolderEditor::createFolder( $filePath );
		$this->logErrors	= $filePath;
		return $this;
	}

	/**
	 *	Set path of request log file.
	 *	@access		public
	 *	@param		?string		$filePath		Path of request log file
	 *	@return		self
	 */
	public function setLogRequests( ?string $filePath ): self
	{
		if( !is_null( $filePath ) && !file_exists( dirname( $filePath ) ) )
			FolderEditor::createFolder( $filePath );
		$this->logRequests	= $filePath;
		return $this;
	}

	//  --  PROTECTED  --  //
	/**
	 *	@todo	handle inputs like int, float etc
	 */
	protected function buildPostFields( object|array|string $data ): string
	{
		if( is_string( $data ) )
			return $data;
		if( is_object( $data ) ){
			if( method_exists( $data, 'toArray' ) )
				$data	= $data->toArray();
			else if( method_exists( $data, '__toArray' ) )
				$data	= $data->__toArray();
			else
				$data	= (array) $data;
		}
		if( is_array( $data ) )
			return http_build_query( $data, '', '&' );
		return '';
	}

	/**
	 *	@param		CurlHandle		$handler
	 *	@param		string			$header
	 *	@return		int
	 */
	protected function callbackHeaderFunction( CurlHandle $handler, string $header ): int
	{
		$this->responseHeader	.= $header;
		return strlen( $header );
	}

	/**
	 *	Returns option on CURL handler, if set.
	 *	@access		protected
	 *	@param		integer		$key		CURL option key (constant)
	 *	@return		mixed|NULL
	 */
	protected function getCurlOption( int $key ): mixed
	{
		if( isset( $this->setCurlOptions[$key] ) )
			return $this->setCurlOptions[$key];
		return NULL;
	}

	/**
	 *	...
	 *	@access		protected
	 *	@return		object|array|string
	 *	@todo		handle HTML
	 */
	protected function handleRequest(): object|array|string
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

		$body		= (string) curl_exec( $this->handler );
		$error		= curl_errno( $this->handler );
		$info		= curl_getinfo( $this->handler );
		$this->logRequest();
		Log::debug( 'handleRequest: curl info: ', $info );

		if( $error > 0 )
			throw new Client\RequestException( curl_error( $this->handler ), $error );

		if( $info['http_code'] >= 400 )
			throw new Client\ResponseException( $body, $info['http_code'] );
//		$this->requestHeader	= curl_getinfo( $this->handler, CURLINFO_HEADER_OUT );

		$responseHeaderFields	= HttpHeaderParser::parse( $this->responseHeader );

		$links		= [];
		/** @var array<Field> $fields */
		$fields		= $responseHeaderFields->getFieldsByName( 'Link' );
		foreach( $fields as $link ){
			$value	= $link->getValue();
/*			if( preg_match( "/;rel=[^;])/", $value ) ){}*/
			$links[]	= $value;
		}

		switch( $this->expectedFormat ){
			case 'HTML':
				break;
			case 'JSON':
				$body	= (object) [
					'data'		=> json_decode( $body ),
	//				'headers'	=> $headers->getFields
					'links'		=> $links,
				];
				break;
			case 'PHP':
				$body	= [
					'data'	=> unserialize( $body ),
					'links'	=> $links,
				];
				break;
			default:
				return $body;
		}
		return $body;
	}

	/**
	 *	...
	 *	@access		protected
	 *	@return		self
	 */
	protected function logRequest(): self
	{
		if( NULL !== $this->logRequests ){
			$info		= curl_getinfo( $this->handler );
			/** @var string $method */
			$method		= $this->getCurlOption( CURLOPT_CUSTOMREQUEST );
			/** @var string $url */
			$url		= $this->getCurlOption( CURLOPT_URL );
			$message	= sprintf(
				'%s %s %d %s',
				date( 'Y-m-d H:i:s' ),
				$method,
				$info['http_code'],
				$url
			);
			error_log( $message.PHP_EOL, 3, $this->logRequests );
		}
		return $this;
	}

	/**
	 *	Sets option on CURL handler. Wrapper for curl_setopt.
	 *	Will note set options to be able to get set options later.
	 *	@access		protected
	 *	@param		integer		$key		CURL option key (constant)
	 *	@param		mixed		$value		Value of CURL option to set
	 *	@return		self
	 */
	protected function setCurlOption( int $key, mixed $value ): self
	{
		$this->setCurlOptions[$key]	= $value;
		curl_setopt( $this->handler, $key, $value );
		return $this;
	}
}
