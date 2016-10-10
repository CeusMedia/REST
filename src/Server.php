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
class Server{

	protected $options;
	protected $defaultOptions	= array(
		'forceMimeType'		=> NULL,
		'routesFile'		=> NULL,
		'formats'			=> array(
			'HTML'		=> TRUE,
			'JSON'		=> TRUE,
			'PHP'		=> TRUE,
			'XML'		=> FALSE,
		),
	);
	protected $formats		= array();
	protected $resources	= array();

	public function __construct( $options = array() ){
		$this->options	= (object) array_merge( $this->defaultOptions, $options );
		$this->request	= new \Net_HTTP_Request_Receiver();
		$this->response	= new \Net_HTTP_Response();
		$this->router	= new \CeusMedia\Router\Router();
		$this->rootPath	= rtrim( dirname( getEnv( 'SCRIPT_NAME' ) ), '/' ).'/';

		$this->resources	= array(
			'server'		=> $this,
			'router'		=> $this->router,
			'request'		=> $this->request,
			'response'		=> $this->response,
		);

		if( $this->options->routesFile )
			$this->router->loadRoutesFromJsonFile( $this->options->routesFile );

		foreach( $this->options->formats as $format => $active ){
			if( $active ){
//				print_m( class_exists( 'Format\\'.$format ) );die;
//				$object	= \Alg_Object_Factory::createObject( '\\CeusMedia\\Router\\Server\\Format\\'.$format );
				$object	= \Alg_Object_Factory::createObject( __NAMESPACE__.'\\Server\\Format\\'.$format );
				$this->formats[$format] = $object;
			}
		}
	}

	public function getRouter(){
		return $this->router;
	}

	public function getRequest(){
		return $this->request;
	}

	public function getResponse(){
		return $this->response;
	}

	public function getResources(){
		return $this->resources;
	}

	public function handleRequest(){
//		$path	= substr( getEnv( 'REDIRECT_URL' ), strlen( $this->rootPath ) );
		$path	= substr( getEnv( 'REQUEST_URI' ), strlen( $this->rootPath ) );
		if( strpos( $path, '?' ) !== FALSE )
			$path	= substr( $path, 0, strpos( $path, '?' ) );

		$route	= $this->router->resolve( $path, getEnv( 'REQUEST_METHOD' ) );
		if( $route ){
			error_log( json_encode( $route )."\n", 3, "routes.log" );
			if( !class_exists( $route->controller ) )
				throw new \RangeException( 'Class "'.$route->controller.'" is not existing' );
			$object		= \Alg_Object_Factory::createObject( $route->controller, $this->resources );
			$result		= \Alg_Object_MethodFactory::callObjectMethod( $object, $route->action, $route->arguments );
		}
		else{
			$this->response->setStatus( 400 );
			$result		= 'No content found for this route.';
		}
		$format		= $this->negotiateResponseFormat( $result );
		$content	= $format->transform( $this->response, $result );
		$this->response->setBody( $content );
		\Net_HTTP_Response_Sender::sendResponse( $this->response );
	}

	protected function negotiateResponseFormat( $content ){
		if( $this->request->has( 'forceAccept' ) )
			$accepts	= array( $this->request->get( 'forceAccept' ) => 1 );
		else if( $this->options->forceMimeType )
			$accepts	= array( $this->options->forceMimeType => 1 );
		else
			$accepts	= $this->request->getHeadersByName( 'Accept', TRUE )->getValue( TRUE );

		foreach( $accepts as $mimeType => $quality )
			foreach( $this->formats as $format )
				if( in_array( $mimeType, $format->mimeTypes) )
					return $format;
		throw new \RuntimeException( 'Content type is not supported' );
	}

	public function setResource( $key, $object ){
		$this->resources[$key]	= $object;
	}
};
