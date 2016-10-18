<?php
/**
 *	...
 *
 *	Copyright (c) 2007-2016 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2016 Christian Würker
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
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class Client{

	protected $username;
	protected $password;
	protected $baseUri;
	protected $expectedFormat	= "HTML";
	protected $options			= array();
	protected $requestHeader;
	protected $responseHeader;

	public function __construct( $baseUri, $options = array() ){
		if( !extension_loaded( 'curl' ) )
			throw new \RuntimeException( "Support for cURL is missing" );
		$this->options	= array_merge( array(), $options );
		$this->baseUri	= $baseUri;
		$this->handler	= curl_init();

		$callbackHeaderFunction	= array( $this, 'callbackHeaderFunction' );
		curl_setopt( $this->handler, CURLOPT_HEADER, FALSE );
		curl_setopt( $this->handler, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $this->handler, CURLOPT_HEADERFUNCTION, $callbackHeaderFunction );
	}

	protected function callbackHeaderFunction( $handler, $header ){
		$this->responseHeader	.= $header;
		return strlen( $header );
	}

	protected function buildPostFields( $data ){
		if( is_object( $data ) ){
			if( method_exists( $data, 'toArray' ) )
				$data	= $data->toArray();
			else if( method_exists( $data, '__toArray' ) )
				$data	= $data->__toArray();
			else
				$data	= (array) $data;
		}
		return http_build_query( $data, NULL, '&' );
	}

	public function expectFormat( $format ){
		$this->expectedFormat	= $format;
	}

	public function get( $path, $parameters = array() ){
		if( $parameters )
			$path	.= "?".$this->buildPostFields( $parameters );
		curl_setopt( $this->handler, CURLOPT_CUSTOMREQUEST, 'GET' );
		curl_setopt( $this->handler, CURLOPT_URL, $this->baseUri.$path );
		return $this->handleRequest();
	}

	public function post( $path, $data = array() ){
		curl_setopt( $this->handler, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $this->handler, CURLOPT_POSTFIELDS, $this->buildPostFields( $data ) );
		curl_setopt( $this->handler, CURLOPT_URL, $this->baseUri.$path );
		return $this->handleRequest();
	}

	public function put( $path, $data = array() ){
		curl_setopt( $this->handler, CURLOPT_CUSTOMREQUEST, 'PUT' );
		curl_setopt( $this->handler, CURLOPT_POSTFIELDS, $this->buildPostFields( $data ) );
		curl_setopt( $this->handler, CURLOPT_URL, $this->baseUri.$path );
		return $this->handleRequest();
	}

	public function delete( $path ){
		curl_setopt( $this->handler, CURLOPT_CUSTOMREQUEST, 'DELETE' );
		curl_setopt( $this->handler, CURLOPT_URL, $this->baseUri.$path );
		return $this->handleRequest();
	}

	protected function handleRequest(){
		$this->requestHeader	= '';
		$this->responseHeader	= '';
		$headers	= array();
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

//		curl_setopt( $this->handler, CURLINFO_HEADER_OUT, TRUE );
		curl_setopt( $this->handler, CURLOPT_HTTPHEADER, $headers );

		$body		= curl_exec( $this->handler );
		$error		= curl_errno( $this->handler );
		if( $error )
			throw new Client\RequestException( curl_error( $this->handler ), $error );

		$info		= curl_getinfo( $this->handler );
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

	public function setBasicAuth( $username, $password ){
		curl_setopt( $this->handler, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		curl_setopt( $this->handler, CURLOPT_USERPWD, $this->username . ':' . $this->password );
	}
}
