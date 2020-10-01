<?php
/**
 *	...
 *
 *	Copyright (c) 2007-2020 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
namespace CeusMedia\REST;

use ADT_List_Dictionary as Dictionary;
use Alg_Object_Factory as ObjectFactory;
use Alg_Object_MethodFactory as MethodFactory;
use CeusMedia\Router\Log;
use CeusMedia\Router\ResolverException as ResolverException;
use CeusMedia\Router\Registry\Source\SourceInterface as RouterRegistrySourceInterface;
use CeusMedia\Router\Registry\Source\JsonFile as RouterRegistrySourceJsonFile;
use CeusMedia\Router\Registry\Source\JsonFolder as RouterRegistrySourceJsonFolder;
use CeusMedia\Router\Route;
use Net_HTTP_Response_Sender as ResponseSender;
use Throwable;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_REST
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class Server
{
	protected $options;

	protected $defaultOptions	= array(
		'classContext'		=> Server\Context::class,
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

	protected $accessChecks;

	public function __construct( array $options = array() )
	{
		Log::debug( 'REST Server: Construction' );
		$this->options	= (object) $this->mergeOptions( $this->defaultOptions, $options );
//		Log::debug( '> Options: ', $this->options );
		Log::debug( '> Context Class: '.$this->options->classContext );
		$this->context	= ObjectFactory::createObject( $this->options->classContext );

		$accessControl			= new Dictionary( $this->options->accessControl );
		$accessControlSettings	= array(
			'allowOrigin'	=> 'Access-Control-Allow-Origin',
			'allowMethods'	=> 'Access-Control-Allow-Methods',
			'allowHeaders'	=> 'Access-Control-Allow-Headers',
		);

		foreach( $accessControlSettings as $optionKey => $headerKey ){
			if( strlen( $accessControl->get( $optionKey ) ) > 0 ){
				$value	= $accessControl->get( $optionKey );
				if( strlen( trim( $value ) ) > 0 ){
					$header	= new \Net_HTTP_Header_Field( $headerKey, $value );
					$this->context->getResponse()->addHeader( $header );
					Log::debug( '> Access-Control: '.$optionKey.' => '.$value );
				}
			}
		}

		if( isset( $this->options->routesFile ) ){
			if( strlen( trim( $this->options->routesFile ) ) > 0 ){
				$source	= new RouterRegistrySourceJsonFile( $this->options->routesFile );
				$this->getRouter()->getRegistry()->addSource( $source );
			}
		}

		if( isset( $this->options->routesFolder ) ){
			if( strlen( trim( $this->options->routesFolder ) ) > 0 ){
				$source	= new RouterRegistrySourceJsonFolder( $this->options->routesFolder );
				$this->getRouter()->getRegistry()->addSource( $source );
			}
		}

		foreach( $this->options->formats as $format => $active ){
			if( $active ){
//				print_m( class_exists( 'Format\\'.$format ) );die;
//				$object	= ObjectFactory::createObject( '\\CeusMedia\\Router\\Server\\Format\\'.$format );
				$object	= ObjectFactory::createObject( __NAMESPACE__.'\\Server\\Format\\'.$format );
				$this->formats[$format] = $object;
				Log::debug( '> support format: '.$format );
			}
		}

		set_error_handler( array( $this, "handleError" ) );
		register_shutdown_function( array( $this, "handleFatalError" ) );
	}

	public function addRouterRegistrySource( RouterRegistrySourceInterface $source ): self
	{
		$this->getRouter()->getRegistry()->addSource( $source );
		return $this;
	}

	public function getRouter()
	{
		return $this->context->getRouter();
	}

	public function getRequest()
	{
		return $this->context->getRequest();
	}

	public function getResponse()
	{
		return $this->context->getResponse();
	}

	public function getContext()
	{
		return $this->context;
	}

	protected function handleException( Throwable $e ): string
	{
		if( $e instanceof ResolverException ){
			$this->context->getResponse()->setStatus( 404 );
			$result		= 'No content found for this route.';
		}
		else if( $e instanceof \OutOfRangeException ){
			$this->context->getResponse()->setStatus( 404 );
		}
		else{
			if( (int) $this->context->getResponse()->getStatus() < 400 ){
				$this->context->getResponse()->setStatus( 500 );
				if( strlen( $e->getCode() ) === 3 )
					$this->context->getResponse()->setStatus( $e->getCode() );
			}
		}
		return $e->getMessage();
	}

	public function handleError( int $code, string $message, string $file, int $line )
	{
		Log::error( $message );
		$this->log( 500 );
		$this->context->getBuffer()->close();
		$this->context->getResponse()->setStatus( 500 );
		$this->context->getResponse()->setBody( '<h1>Internal Server Error</h1><p>Error: '.$message.' in '.$file.' at line '.$line );
		ResponseSender::sendResponse( $this->context->getResponse() );
		exit;
	}

	public function handleFatalError()
	{
		$error	= error_get_last();
		if( is_null( $error ) )
			return;
		Log::error( $error['message'], $error );
		$this->log( 500 );
		$this->context->getBuffer()->close();
		$this->context->getResponse()->setStatus( 500 );
		$this->context->getResponse()->setBody( $error['message'] );
		ResponseSender::sendResponse( $this->context->getResponse() );
		exit;
	}

	/**
	 *	@todo		abstract logging - use logger interface
	 */
	public function handleRequest()
	{
		$path		= $this->context->getRequest()->getPath();
		$method		= $this->context->getRequest()->getMethod();
		Log::debug( 'REST Server: handleRequest: path => '.$path );

		if( strpos( $path, '#' ) !== FALSE ){
			$fragment	= substr( $path, strpos( $path, '#' ) + 1 );			//  @todo fragment is unused atm, just cut off
			$path		= substr( $path, 0, strpos( $path, '#' ) );
		}
		if( strpos( $path, '?' ) !== FALSE ){
			$parameters	= substr( $path, strpos( $path, '?' ) + 1 );			//  @todo $parameters is unused atm, just cut off
			$path		= substr( $path, 0, strpos( $path, '?' ) );
		}
		try{
			$this->context->getRouter()->setMethod( $method );
			ob_start();
			$route		= $this->context->getRouter()->resolve( $path );
			$buffer		= ob_get_clean();
			$this->checkAccess( $route );
			$result		= $this->realizeResolvedRoute( $route );
			$format		= $this->negotiateResponseFormat();
			$content	= $format->transform( $this->context->getResponse(), $result );
			$this->log( 200 );
			$this->context->getResponse()->setBody( $content.$buffer );
			ResponseSender::sendResponse( $this->context->getResponse(), NULL, FALSE );
		}
		catch( \Exception $e ){
			$this->log( 500 );
			$text		= $this->handleException( $e ).'.';
			$buffer		= ob_get_clean();
			$content	= '<h1>'.$this->context->getResponse()->getStatus().'</h1>'.$text;
			$this->context->getResponse()->setBody( $content.$buffer );
			$this->context->getResponse()->addHeaderPair( 'Content-Type', 'text/html' );
			ResponseSender::sendResponse( $this->context->getResponse(), NULL, FALSE );
		}
	}

	public function registerAccessCheck( string $className, string $method, array $options = array() ): self
	{
		$this->accessChecks[]	= (object) [
			'className'	=> $className,
			'method'	=> $method,
			'options'	=> $options
		];
		return $this;
	}

	public function setResource( string $key, $object ): self
	{
		$this->context->set( $key, $object );
		return $this;
	}

	/*  --  PROTECTED  --  */

	protected function checkAccess( $route )
	{
		if( !$this->accessChecks )
			return;
		foreach( $this->accessChecks as $accessCheck ){
			ob_start();
			$error	= MethodFactory::callClassMethod(
				$accessCheck->className,
				$accessCheck->method,
				$accessCheck->options,
				array( $this->context->getRequest(), $route )
			);
			$buffer	= ob_get_clean();
			if( strlen( trim( $error ) ) > 0 ){
				$this->log( 401 );
				$this->context->getBuffer()->close();
				$this->context->getResponse()->setStatus( 401 );
				$this->context->getResponse()->setBody( '<h1>401 Forbidden</h1><p>'.$error.'</p>'.$buffer );
				ResponseSender::sendResponse( $this->context->getResponse() );
				exit;
			}
		}
	}

	protected function log( $status )
	{
		$log	= array(
			'date'		=> date( 'r' ),
			'ip'		=> getenv( 'REMOTE_ADDR' ),
			'method'	=> $this->context->getRequest()->getMethod(),
			'path'		=> $this->context->getRequest()->getPath(),
			'referer'	=> getenv( 'HTTP_REFERER' )
		);
//		error_log( json_encode( $log )."\n", 3, __DIR__."/log/routes.log" );
	}

	protected function negotiateResponseFormat()
	{
		Log::debug( 'REST Server: negotiateResponseFormat' );
		$path			= $this->context->getRequest()->getPath();
		$acceptHeader	= $this->context->getRequest()->getHeadersByName( 'Accept', TRUE );
		if( $acceptHeader ){
			$accepts = $acceptHeader->getValue( TRUE );
			Log::debug( '> accepts by request: '.json_encode( $accepts ) );
			if( $this->options->forceMimeType )
				$accepts	= array( $this->options->forceMimeType => 1 );

			if( preg_match( '/\.\w+$/', $path ) === 0 ){
				foreach( $this->formats as $format ){
					$extension	= preg_quote( $format->extension, '/' );
					if( preg_match( '/'.$extension.'$/', $path ) === 0 ){
						$accepts	= array( $format->mimeTypes[0] => 1 );
					}
				}
			}

			Log::debug( '> accepts finally: '.json_encode( $accepts ) );
			foreach( $accepts as $mimeType => $quality ){
				foreach( $this->formats as $format ){
					if( in_array( $mimeType, $format->mimeTypes, TRUE ) ){
						Log::debug( '> final format: '.$mimeType );
						return $format;
					}
				}
			}
		}
		throw new \RuntimeException( 'Content type is not supported' );
	}

	protected function realizeResolvedRoute( Route $route )
	{
		if( !class_exists( $route->getController() ) )
			throw new \RangeException( 'Class "'.$route->getController().'" is not existing' );
		$object		= ObjectFactory::createObject( $route->getController(), array( $this->context ) );

//  @todo handle exception in method calls
//try{
		$result		= MethodFactory::callObjectMethod( $object, $route->getAction(), $route->getArguments() );
		//  @todo make handling of dev output configurable + log
		if( $this->context->getBuffer()->has() ){
			throw new \RuntimeException( $this->context->getBuffer()->get( TRUE ), 500 );
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
	protected function mergeOptions( array &$array1, array &$array2 ): array
	{
		$merged = $array1;
		foreach( $array2 as $key => &$value ){
			$isNest	= is_array( $value ) && isset( $merged[$key] ) && is_array( $merged[$key] );
			$merged[$key] = $isNest ? $this->mergeOptions( $merged[$key], $value ) : $value;
		}
		return $merged;
	}
};
