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

use CeusMedia\Router\Router as Router;
use CeusMedia\Router\ResolverException as ResolverException; 
use CeusMedia\Router\Registry\Source\SourceInterface as RouterRegistrySourceInterface;
use CeusMedia\Router\Registry\Source\JsonFile as RouterRegistrySourceJsonFile;
use CeusMedia\Router\Registry\Source\JsonFolder as RouterRegistrySourceJsonFolder;
use Net_HTTP_Request_Receiver as RequestReceiver;
use Net_HTTP_Response_Sender as ResponseSender;

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
class Server
{
	protected $options;
	protected $defaultOptions	= array(
		'forceMimeType'		=> NULL,
		'routesFile'		=> NULL,
		'routesFolder'		=> NULL,
		'formats'			=> array(
			'HTML'		=> TRUE,
			'JSON'		=> TRUE,
			'PHP'		=> TRUE,
			'XML'		=> FALSE,
		),
		'accessControl'	=> array(
			'allowOrigin'	=> '*',
			'allowMethods'	=> 'GET,POST,PUT,DELETE,OPTIONS',
			'allowHeaders'	=> 'authorization',
		),
	);
	protected $formats		= array();
	protected $context;
	protected $buffer;
	protected $request;
	protected $response;
	protected $router;

	protected $accessChecks;

	public function __construct( $options = array() )
	{
		$this->options	= (object) $this->mergeOptions( $this->defaultOptions, $options );

		$this->context				= new Server\Context();
		$this->context->request		= new RequestReceiver();
		$this->context->response	= new \Net_HTTP_Response();
		$this->context->router		= new Router();
		$this->context->buffer		= new \UI_OutputBuffer();

		$accessControl			= new \ADT_List_Dictionary( $this->options->accessControl );
		$accessControlSettings	= array(
			'allowOrigin'	=> 'Access-Control-Allow-Origin',
			'allowMethods'	=> 'Access-Control-Allow-Methods',
			'allowHeaders'	=> 'Access-Control-Allow-Headers',
		);

		foreach( $accessControlSettings as $optionKey => $headerKey ){
			if( strlen( $accessControl->get( $optionKey ) ) ){
				if( $accessControl->get( $optionKey ) ){
					$header	= new \Net_HTTP_Header_Field( $headerKey, $accessControl->get( $optionKey ) );
					$this->context->response->addHeader( $header );
				}
			}
		}

		if( !empty( $this->options->routesFile ) ){
			$source	= new RouterRegistrySourceJsonFile( $this->options->routesFile );
			$this->getRouter()->getRegistry()->addSource( $source );
		}

		if( !empty( $this->options->routesFolder ) ){
			$source	= new RouterRegistrySourceJsonFolder( $this->options->routesFolder );
			$this->getRouter()->getRegistry()->addSource( $source );
		}

		foreach( $this->options->formats as $format => $active ){
			if( $active ){
//				print_m( class_exists( 'Format\\'.$format ) );die;
//				$object	= \Alg_Object_Factory::createObject( '\\CeusMedia\\Router\\Server\\Format\\'.$format );
				$object	= \Alg_Object_Factory::createObject( __NAMESPACE__.'\\Server\\Format\\'.$format );
				$this->formats[$format] = $object;
			}
		}

		set_error_handler( array( $this, "handleError" ) );
		register_shutdown_function( array( $this, "handleFatalError" ) );
	}

	public function addRouterRegistrySource( RouterRegistrySourceInterface $source )
	{
		$this->getRouter()->getRegistry()->addSource( $source );
	}

	public function getRouter()
	{
		return $this->context->router;
	}

	public function getRequest()
	{
		return $this->context->request;
	}

	public function getResponse()
	{
		return $this->context->response;
	}

	public function getContext()
	{
		return $this->context;
	}

	protected function handleException( $e )
	{
		if( $e instanceof ResolverException ){
			$this->context->response->setStatus( 404 );
			$result		= 'No content found for this route.';
		}
		else if( $e instanceof \OutOfRangeException ){
			$this->context->response->setStatus( 404 );
		}
		else{
			if( (int) $this->context->response->getStatus() < 400 ){
				$this->context->response->setStatus( 500 );
				if( strlen( $e->getCode() ) === 3 )
					$this->context->response->setStatus( $e->getCode() );
			}
		}
		return $e->getMessage();
	}

	public function handleError( $code, $message, $file, $line )
	{
		$this->log( 500 );
		$this->context->buffer->close();
		$this->context->response->setStatus( 500 );
		$this->context->response->setBody( '<h1>Internal Server Error</h1><p>Error: '.$message.' in '.$file.' at line '.$line );
		ResponseSender::sendResponse( $this->context->response );
		exit;
	}

	public function handleFatalError()
	{
		$error	= error_get_last();
		if( !$error )
			return;
		$this->log( 500 );
		$this->context->buffer->close();
		$this->context->response->setStatus( 500 );
		$this->context->response->setBody( $error['message'] );
		ResponseSender::sendResponse( $this->context->response );
		exit;
	}

	/**
	 *	@todo		abstract logging - use logger interface
	 */
	public function handleRequest()
	{
		$path	= $this->context->request->getPath();
		$method	= $this->context->request->getMethod();

		if( strpos( $path, '?' ) !== FALSE )
			$path	= substr( $path, 0, strpos( $path, '?' ) );
		if( preg_match( '/\.\w+$/', $path ) )
			$path	= substr( $path, 0, strrpos( $path, '.' ) );
		try{
			$this->context->router->setMethod( $method );
			ob_start();
			$route		= $this->context->router->resolve( $path );
			$buffer		= ob_get_clean();
			$this->checkAccess( $route );
			$result		= $this->realizeResolvedRoute( $route );
			$format		= $this->negotiateResponseFormat( $result );
			$content	= $format->transform( $this->context->response, $result );
			$this->log( 200 );
			$this->context->response->setBody( $content.$buffer );
			ResponseSender::sendResponse( $this->context->response, NULL, FALSE );
		}
		catch( \Exception $e ){
			$this->log( 500 );
			$text		= $this->handleException( $e ).'.';
			$buffer		= ob_get_clean();
			$content	= '<h1>'.$this->context->response->getStatus().'</h1>'.$text;
			$buffer		= strlen( trim( $buffer ) ) ? $buffer : $buffer;
			$this->context->response->setBody( $content.$buffer );
			$this->context->response->addHeaderPair( 'Content-Type', 'text/html' );
			ResponseSender::sendResponse( $this->context->response, NULL, FALSE );
		}
	}

	public function registerAccessCheck( $className, $method )
	{
		$this->accessChecks[]	= (object) array( 'className' => $className, 'method' => $method );
	}

	public function setResource( $key, $object )
	{
		$this->context->set( $key, $object );
	}

	/*  --  PROTECTED  --  */

	protected function checkAccess( $route )
	{
		if( !$this->accessChecks )
			return;
		foreach( $this->accessChecks as $accessCheck ){
			ob_start();
			$error	= \Alg_Object_MethodFactory::callClassMethod(
				$accessCheck->className,
				$accessCheck->method,
				array(),
				array( $this->context->request, $route )
			);
			$buffer	= ob_get_clean();
			if( $error ){
				$this->log( 401 );
				$this->context->buffer->close();
				$this->context->response->setStatus( 401 );
				$this->context->response->setBody( '<h1>401 Forbidden</h1><p>'.$error.'</p>'.$buffer );
				ResponseSender::sendResponse( $this->context->response );
				exit;
			}
		}
	}

	protected function log( $status )
	{
		$log	= array(
			'date'		=> date( 'r' ),
			'ip'		=> getEnv( 'REMOTE_ADDR' ),
			'method'	=> $this->context->request->getMethod(),
			'path'		=> $this->context->request->getPath(),
			'referer'	=> getEnv( 'HTTP_REFERER' )
		);
//		error_log( json_encode( $log )."\n", 3, __DIR__."/log/routes.log" );
	}

	protected function negotiateResponseFormat( $content )
	{
		$path		= $this->context->request->getPath();
		$accepts	= $this->context->request->getHeadersByName( 'Accept', TRUE )->getValue( TRUE );
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

	protected function realizeResolvedRoute( \CeusMedia\Router\Route $route )
	{
		if( !class_exists( $route->getController() ) )
			throw new \RangeException( 'Class "'.$route->getController().'" is not existing' );
		$object		= \Alg_Object_Factory::createObject( $route->getController(), array( $this->context ) );

//  @todo handle exception in method calls
//try{
		$result		= \Alg_Object_MethodFactory::callObjectMethod( $object, $route->getAction(), (array) $route->getArguments() );
		if( $this->context->buffer->has() ){
			throw new \RuntimeException( $this->context->buffer->get( TRUE ), 500 );
		}
//}
//catch( Exception $e ){}
		return $result;
	}


	/**
	* array_merge_recursive does indeed merge arrays, but it converts values with duplicate
	* keys to arrays rather than overwriting the value in the first array with the duplicate
	* value in the second array, as array_merge does. I.e., with array_merge_recursive,
	* this happens (documented behavior):
	*
	* array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
	*     => array('key' => array('org value', 'new value'));
	*
	* array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
	* Matching keys' values in the second array overwrite those in the first array, as is the
	* case with array_merge, i.e.:
	*
	* array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
	*     => array('key' => array('new value'));
	*
	* Parameters are passed by reference, though only for performance reasons. They're not
	* altered by this function.
	*
	* @param array $array1
	* @param array $array2
	* @return array
	* @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
	* @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
	*/
	protected function mergeOptions( array &$array1, array &$array2 ){
		$merged = $array1;
		foreach( $array2 as $key => &$value ){
			$isNest	= is_array( $value ) && isset( $merged[$key] ) && is_array( $merged[$key] );
			$merged[$key] = $isNest ? $this->mergeOptions( $merged[$key], $value ) : $value;
		}
		return $merged;
	}
};
