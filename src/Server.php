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
		$this->buffer	= new \UI_OutputBuffer();

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

		register_shutdown_function( array( $this, "handleFatalError" ) );
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

	protected function handleException( $e ){
		if( $e instanceof ResolverException ){
			$this->response->setStatus( 404 );
			$result		= 'No content found for this route.';
		}
		else if( $e instanceof \OutOfRangeException ){
			$this->response->setStatus( 404 );
		}
		else{
			if( (int) $this->response->getStatus() < 400 ){
				$this->response->setStatus( 500 );
				if( count( $e->getCode() ) === 3 )
					$this->response->setStatus( $e->getCode() );
			}
		}
		return $e->getMessage();
	}

	public function handleFatalError(){
		$error	= error_get_last();
		if( !$error )
			return;
		$this->buffer->close();
		$this->response->setStatus( 500 );
		$this->response->setBody( $error['message'] );
		\Net_HTTP_Response_Sender::sendResponse( $this->response );
		exit;
	}

	/**
	 *	@todo		abstract logging - use logger interface
	 */
	public function handleRequest(){
		$path	= $this->request->getPath();
		$method	= $this->request->getMethod();

		if( strpos( $path, '?' ) !== FALSE )
			$path	= substr( $path, 0, strpos( $path, '?' ) );
		if( preg_match( '/\.\w+$/', $path ) )
			$path	= substr( $path, 0, strrpos( $path, '.' ) );

		try{
			$route		= $this->router->resolve( $path, $method );
			$result		= $this->realizeResolvedRoute( $route );
			$format		= $this->negotiateResponseFormat( $result );
			$content	= $format->transform( $this->response, $result );
			$this->response->setBody( $content );
			\Net_HTTP_Response_Sender::sendResponse( $this->response );
		}
		catch( \Exception $e ){
			$text	= $this->handleException( $e ).'.';
			$content	= '<h1>'.$this->response->getStatus().'</h1>'.$text;
			$this->response->setBody( $content );
			$this->response->addHeaderPair( 'Content-Type', 'text/html' );
			\Net_HTTP_Response_Sender::sendResponse( $this->response );
		}
	}

	protected function negotiateResponseFormat( $content ){
		$path		= $this->request->getPath();
		$accepts	= $this->request->getHeadersByName( 'Accept', TRUE )->getValue( TRUE );
		if( $this->options->forceMimeType )
			$accepts	= array( $this->options->forceMimeType => 1 );

		if( preg_match( '/\.\w+$/', $path ) )
			foreach( $this->formats as $format )
				if( preg_match( '/'.preg_quote( $format->extension, '/' ).'$/', $path ) )
					$accepts	= array( $format->mimeTypes[0] => 1 );

		foreach( $accepts as $mimeType => $quality )
			foreach( $this->formats as $format )
				if( in_array( $mimeType, $format->mimeTypes) )
					return $format;
		throw new \RuntimeException( 'Content type is not supported' );
	}

	protected function realizeResolvedRoute( \CeusMedia\Router\Route $route ){
//		error_log( json_encode( $route )."\n", 3, "routes.log" );
		if( !class_exists( $route->getController() ) )
			throw new \RangeException( 'Class "'.$route->getController().'" is not existing' );
		$object		= \Alg_Object_Factory::createObject( $route->getController(), $this->resources );
		$result		= \Alg_Object_MethodFactory::callObjectMethod( $object, $route->getAction(), (array) $route->getArguments() );
		if( $this->buffer->has() ){
			throw new \RuntimeException( $this->buffer->get( TRUE ), 500 );
		}
		return $result;
	}

	public function setResource( $key, $object ){
		$this->resources[$key]	= $object;
	}
};
