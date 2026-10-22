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

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Common\Alg\Obj\Factory as ObjectFactory;
use CeusMedia\Common\Alg\Obj\MethodFactory as MethodFactory;
use CeusMedia\Common\Net\HTTP\Header\Field as HeaderField;
use CeusMedia\Common\Net\HTTP\Request as HttpRequest;
use CeusMedia\Common\Net\HTTP\Response as HttpResponse;
use CeusMedia\Common\Net\HTTP\Response\Sender as ResponseSender;
use CeusMedia\HydrogenFramework\Environment as FrameworkEnvironment;
use CeusMedia\REST\Server\AccessCheck\AccessCheckInterface;
use CeusMedia\REST\Server\AccessCheck\Legacy as LegacyAccessCheck;
use CeusMedia\REST\Server\AccessCheck\ResponseObserverInterface;
use CeusMedia\REST\Server\AccessDeniedException;
use CeusMedia\REST\Server\Context;
use CeusMedia\REST\Server\ErrorFormatter;
use CeusMedia\REST\Server\ErrorFormatterInterface;
use CeusMedia\REST\Server\Format\FormatInterface;
use CeusMedia\Router\Log;
use CeusMedia\Router\ResolverException as ResolverException;
use CeusMedia\Router\Registry\Source\SourceInterface as RouterRegistrySourceInterface;
use CeusMedia\Router\Registry\Source\JsonFile as RouterRegistrySourceJsonFile;
use CeusMedia\Router\Registry\Source\JsonFolder as RouterRegistrySourceJsonFolder;
use CeusMedia\Router\Route;
use CeusMedia\Router\Router;
use ErrorException;
use OutOfRangeException;
use RangeException;
use ReflectionException;
use RuntimeException;
use Throwable;

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
class Server
{
	protected Dictionary $options;

	protected array $defaultOptions	= [
		'classContext'		=> Server\Context::class,
		'forceMimeType'		=> NULL,
		'routesFile'		=> NULL,
		'routesFolder'		=> NULL,
		'formats'			=> [
			'HTML'		=> TRUE,
			'JSON'		=> TRUE,
			'PHP'		=> TRUE,
			'XML'		=> FALSE,
		],
		'accessControl'	=> [
			'allowOrigin'	=> '*',
			'allowMethods'	=> 'GET,POST,PUT,DELETE,OPTIONS',
			'allowHeaders'	=> 'authorization',
		],
		//  show messages of internal errors (5xx) to clients – development only
		'exposeErrors'	=> FALSE,
		//  headers added to every response, e.g. self::SECURE_RESPONSE_HEADERS
		'responseHeaders'	=> [],
	];

	/** Recommended headers for APIs with personal data, use as option responseHeaders */
	public const array SECURE_RESPONSE_HEADERS	= [
		'Cache-Control'				=> 'no-store',
		'X-Content-Type-Options'	=> 'nosniff',
		'Referrer-Policy'			=> 'no-referrer',
		'X-Frame-Options'			=> 'DENY',
	];

	/** Error levels which are logged only, the request goes on */
	protected const array NON_FATAL_ERROR_LEVELS	= [E_DEPRECATED, E_USER_DEPRECATED, E_NOTICE, E_USER_NOTICE, E_WARNING, E_USER_WARNING];

	/** Error levels which end the script, handled on shutdown */
	protected const array FATAL_ERROR_LEVELS		= [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];

	/** @var FormatInterface[] array  */
	protected array $formats		= [];

	/** @var Context|FrameworkEnvironment $context */
	protected Context|FrameworkEnvironment $context;

	/** @var array<int,AccessCheckInterface> $accessChecks */
	protected array $accessChecks	= [];

	protected ErrorFormatterInterface $errorFormatter;

	/**
	 *	Static constructor.
	 *	@param		Dictionary|array		$options
	 *	@throws		ReflectionException
	 */
	public static function create( Dictionary|array $options = [] ): self
	{
		return new self( $options );
	}

	/**
	 *	Constructor.
	 *	@param		Dictionary|array		$options
	 *	@throws		ReflectionException
	 */
	public function __construct( Dictionary|array $options = [] )
	{
		if( FALSE === getenv( 'HTTP_HOST' ) )
			throw new RuntimeException( 'Server can only run in an HTTP environment' );

		if( $options instanceof Dictionary )
			/** @var array $options */
			$options	= $options->getAll();

		Log::debug( 'REST Server: Construction' );
		$this->errorFormatter	= new ErrorFormatter();
		$this->options	= new Dictionary( $this->mergeOptions( $this->defaultOptions, $options ) );
//		Log::debug( '> Options: ', $this->options->getAll() );
		Log::debug( '> Context Class: '.$this->options->get( 'classContext' ) );
		/** @var string $contextClassName */
		$contextClassName	= $this->options->get( 'classContext' );
		/** @var Context|FrameworkEnvironment $context */
		$context			= ObjectFactory::createObject( $contextClassName );
		$this->context		= $context;
		/** @var array $fields */
		$fields				= $this->options->get( 'accessControl' );
		$accessControl			= new Dictionary( $fields );
		$accessControlSettings	= [
			'allowOrigin'	=> 'Access-Control-Allow-Origin',
			'allowMethods'	=> 'Access-Control-Allow-Methods',
			'allowHeaders'	=> 'Access-Control-Allow-Headers',
		];

		/** @var array<string,string> $responseHeaders */
		$responseHeaders	= (array) $this->options->get( 'responseHeaders', [] );
		foreach( $responseHeaders as $headerKey => $headerValue )
			$this->getResponse()->addHeaderPair( $headerKey, $headerValue );

		foreach( $accessControlSettings as $optionKey => $headerKey ){
			/** @var string $headerValue */
			$headerValue	= $accessControl->get( $optionKey );
			if( 0 !== strlen( $headerValue ) ){
				$header	= new HeaderField( $headerKey, $headerValue );
				$this->context->getResponse()->addHeader( $header );
				Log::debug( '> Access-Control: '.$optionKey.' => '.$headerValue );
			}
		}

		/** @var ?string $routesFile */
		$routesFile	= $this->options->get( 'routesFile' );
		if( NULL !== $routesFile && 0 !== strlen( trim( $routesFile ) ) ){
			$source	= new RouterRegistrySourceJsonFile( trim( $routesFile ) );
			$this->getRouter()->getRegistry()->addSource( $source );
		}

		/** @var ?string $routesFolder */
		$routesFolder	= $this->options->get( 'routesFolder' );
		if( NULL !== $routesFolder && 0 !== strlen( trim( $routesFolder ) ) ){
			$source	= new RouterRegistrySourceJsonFolder( trim( $routesFolder ) );
			$this->getRouter()->getRegistry()->addSource( $source );
		}

		/** @var array<string,bool> $formats */
		$formats	= $this->options->get( 'formats' );
		foreach( $formats as $format => $active ){
			if( $active ){
				if( !str_starts_with( $format, '\\' ) )
					$format	= __NAMESPACE__.'\\Server\\Format\\'.$format;
				/** @var FormatInterface $object */
				$object	= ObjectFactory::createObject( $format );
				$this->formats[$format] = $object;
				Log::debug( '> support format: '.$format );
			}
		}

		set_error_handler( [$this, "handleError"] );
		register_shutdown_function( [$this, "handleFatalError"] );
	}

	/**
	 *	@param		RouterRegistrySourceInterface		$source
	 *	@return		self
	 */
	public function addRouterRegistrySource( RouterRegistrySourceInterface $source ): self
	{
		$this->getRouter()->getRegistry()->addSource( $source );
		return $this;
	}

	/**
	 *	@return		Router
	 */
	public function getRouter(): Router
	{
		return $this->context->getRouter();
	}

	/**
	 *	@return		HttpRequest
	 */
	public function getRequest(): HttpRequest
	{
		return $this->context->getRequest();
	}

	/**
	 *	@return		HttpResponse
	 */
	public function getResponse(): HttpResponse
	{
		return $this->context->getResponse();
	}

	/**
	 *	@return		Context|FrameworkEnvironment
	 */
	public function getContext(): Context|FrameworkEnvironment
	{
		return $this->context;
	}

	/**
	 *	PHP error handler: notices, warnings and deprecations are logged and the request goes on;
	 *	other errors are thrown as ErrorException and answered like any exception.
	 *	Details are never sent to the client (see option exposeErrors).
	 *	@param		int			$code
	 *	@param		string		$message
	 *	@param		string		$file
	 *	@param		int			$line
	 *	@return		bool		TRUE if handled, FALSE to pass on to PHP (error suppressed via @ or error_reporting)
	 *	@throws		ErrorException
	 */
	public function handleError( int $code, string $message, string $file, int $line ): bool
	{
		if( 0 === ( error_reporting() & $code ) )
			return FALSE;
		if( in_array( $code, self::NON_FATAL_ERROR_LEVELS, TRUE ) ){
			Log::warn( sprintf( 'PHP error %d: %s in %s:%d', $code, $message, $file, $line ) );
			error_log( sprintf( 'PHP error %d: %s in %s:%d', $code, $message, $file, $line ) );
			return TRUE;
		}
		throw new ErrorException( $message, 0, $code, $file, $line );
	}

	/**
	 *	Shutdown handler: answers fatal errors with a generic 500 response, details go to the log.
	 *	@return	void
	 */
	public function handleFatalError(): void
	{
		$error	= error_get_last();
		if( is_null( $error ) || !in_array( $error['type'], self::FATAL_ERROR_LEVELS, TRUE ) )
			return;
		Log::error( $error['message'], $error );
		error_log( sprintf( 'PHP fatal error: %s in %s:%d', $error['message'], $error['file'], $error['line'] ) );
		$this->log( 500 );
		while( ob_get_level() > 0 )
			ob_end_clean();
		$this->sendError( 500, TRUE === $this->options->get( 'exposeErrors' ) ? $error['message'] : 'Internal Server Error' );
	}

	/**
	 *	@todo		abstract logging - use logger interface
	 */
	public function handleRequest(): void
	{
		$path		= $this->context->getRequest()->getPath();
		$method		= $this->context->getRequest()->getMethod();
		Log::debug( 'REST Server: handleRequest: path => '.$path );

		if( str_contains( $path, '#' ) ){
			/** @var int $position */
			$position	= strpos( $path, '#' );
			$fragment	= substr( $path, $position + 1 );			//  @todo fragment is unused atm, just cut off
			$path		= substr( $path, 0, $position );
		}
		if( str_contains( $path, '?' ) ){
			/** @var int $position */
			$position	= strpos( $path, '?' );
			$parameters	= substr( $path, $position + 1 );			//  @todo $parameters is unused atm, just cut off
			$path		= substr( $path, 0, $position );
		}
		$bufferLevel	= ob_get_level();
		$route			= NULL;
		try{
			$this->context->getRouter()->setMethod( $method );
			ob_start();
			/** @var Route $route */
			$route		= $this->context->getRouter()->resolve( $path );
			Log::debug( 'REST Server: handleRequest: resolved route', $route );
			$buffer		= (string) ob_get_clean();
			$this->checkAccess( $route );
			Log::debug( 'REST Server: handleRequest: is accessible' );
			/** @var object|array|scalar|null $result */
			$result		= $this->realizeResolvedRoute( $route );
			if( NULL === $result ){
				//  nothing to return, e.g. after delete
				$this->log( 204 );
				$this->context->getResponse()->setStatus( 204 );
				$this->notifyResponseObservers( $route );
				$this->context->getResponse()->setBody( '' );
				ResponseSender::sendResponse( $this->context->getResponse(), NULL, FALSE );
				return;
			}
			$format		= $this->negotiateResponseFormat();
			Log::debug( 'REST Server: handleRequest: result:', $result );
			$content	= $format->transform( $this->context->getResponse(), $result );
			$this->log( 200 );
			$this->notifyResponseObservers( $route );
			$this->context->getResponse()->setBody( $content.$buffer );
			ResponseSender::sendResponse( $this->context->getResponse(), NULL, FALSE );
		}
		catch( Throwable $e ){
			//  drop output of the failed request, but not of buffers opened before
			while( ob_get_level() > $bufferLevel )
				ob_end_clean();
			$message	= $this->handleException( $e );
			$this->log( (int) $this->context->getResponse()->getStatus() );
			if( NULL !== $route )
				$this->notifyResponseObservers( $route );
			$this->sendError( (int) $this->context->getResponse()->getStatus(), $message, $e );
		}
	}

	/**
	 *	@param		string		$className
	 *	@param		string		$method
	 *	@param		array		$options
	 *	@return		self
	 */
	/**
	 *	Adds an access check, run in order of registration before the controller.
	 *	@param		AccessCheckInterface	$check
	 *	@return		self
	 */
	public function addAccessCheck( AccessCheckInterface $check ): self
	{
		$this->accessChecks[]	= $check;
		return $this;
	}

	/**
	 *	Registers an access check by class and method, which returns an empty string if granted.
	 *	@param		string		$className
	 *	@param		string		$method
	 *	@param		array		$options
	 *	@return		self
	 *	@deprecated	use addAccessCheck() with an AccessCheckInterface instead
	 */
	public function registerAccessCheck( string $className, string $method, array $options = [] ): self
	{
		return $this->addAccessCheck( new LegacyAccessCheck( $this->context, $className, $method, $options ) );
	}

	/**
	 *	Sets the builder of error response bodies, e.g. for an own structure or translated messages.
	 *	@param		ErrorFormatterInterface		$formatter
	 *	@return		self
	 */
	public function setErrorFormatter( ErrorFormatterInterface $formatter ): self
	{
		$this->errorFormatter	= $formatter;
		return $this;
	}

	public function setResource( string $key, object $object ): self
	{
		$this->context->set( $key, $object );
		return $this;
	}

	/*  --  PROTECTED  --  */

	/**
	 *	@param		Route		$route
	 *	@return		void
	 */
	protected function checkAccess( Route $route ): void
	{
		foreach( $this->accessChecks as $accessCheck ){
			$result	= $accessCheck->check( $this->context->getRequest(), $route );
			if( !$result->isAllowed() )
				throw new AccessDeniedException( $result->getMessage(), $result->getStatus(), $result->getHeaders() );
		}
	}

	/**
	 *	Tells access checks implementing ResponseObserverInterface the status of the response.
	 *	Their exceptions are logged only, the response stays as it is.
	 *	@param		Route		$route
	 *	@return		void
	 */
	protected function notifyResponseObservers( Route $route ): void
	{
		$status	= (int) $this->getResponse()->getStatus();
		foreach( $this->accessChecks as $accessCheck ){
			if( !$accessCheck instanceof ResponseObserverInterface )
				continue;
			try{
				$accessCheck->observeResponse( $this->getRequest(), $route, $status );
			}
			catch( Throwable $e ){
				Log::error( 'REST Server: response observer '.get_class( $accessCheck ).': '.$e->getMessage() );
				error_log( 'REST Server: response observer: '.$e );
			}
		}
	}

	/**
	 *	Sets the response status for an exception and returns the message for the client.
	 *	Client errors (4xx) show their message; internal errors (5xx) only a generic text unless exposeErrors is set.
	 *	Details are logged.
	 *	@param		Throwable		$e
	 *	@return		string
	 */
	protected function handleException( Throwable $e ): string
	{
		$response	= $this->getResponse();
		if( $e instanceof ResolverException ){
			$response->setStatus( 404 );
			return 'No content found for this route';
		}
		if( $e instanceof AccessDeniedException ){
			$response->setStatus( $e->getCode() );
			foreach( $e->getHeaders() as $name => $value )
				$response->addHeaderPair( $name, $value, TRUE );
			return $e->getMessage();
		}
		if( $e instanceof OutOfRangeException ){
			$response->setStatus( 404 );
			return $e->getMessage();
		}
		$code	= $e->getCode();
		$status	= $code >= 400 && $code <= 599 ? $code : 500;
		$response->setStatus( $status );
		if( $status < 500 )
			return $e->getMessage();
		Log::error( 'REST Server: '.get_class( $e ).': '.$e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()] );
		error_log( 'REST Server: '.$e );
		return TRUE === $this->options->get( 'exposeErrors' ) ? $e->getMessage() : 'Internal Server Error';
	}

	/**
	 *	Sends an error response in the negotiated format, built by the error formatter,
	 *	by default {"error":{"status":404,"message":"..."}}.
	 *	Falls back to plain text if no format can be negotiated or the formatter fails.
	 *	@param		int				$status
	 *	@param		string			$message		Message safe to show to the client
	 *	@param		Throwable|NULL	$exception		Cause, given to the error formatter
	 *	@return		void
	 */
	protected function sendError( int $status, string $message, ?Throwable $exception = NULL ): void
	{
		$response	= $this->getResponse();
		$response->setStatus( $status );
		try{
			$format	= $this->negotiateResponseFormat();
			$body	= $format->transform( $response, $this->errorFormatter->format( $status, $message, $exception ) );
		}
		catch( Throwable ){
			$response->addHeaderPair( 'Content-Type', 'text/plain; charset=utf-8', TRUE );
			$body	= $status.' '.$message;
		}
		$response->setBody( $body );
		ResponseSender::sendResponse( $response, NULL, FALSE );
	}

	/**
	 *	@param		int			$status
	 *	@param		int|NULL	$code
	 *	@return		void
	 */
	protected function log( int $status, ?int $code = NULL ): void
	{
		$log	= [
			'date'		=> date( 'r' ),
			'ip'		=> getenv( 'REMOTE_ADDR' ),
			'status'	=> $status,
			'code'		=> $code ?? '-',
			'method'	=> $this->context->getRequest()->getMethod(),
			'path'		=> $this->context->getRequest()->getPath(),
			'referer'	=> getenv( 'HTTP_REFERER' )
		];
//		error_log( json_encode( $log )."\n", 3, __DIR__."/log/routes.log" );
	}

	/**
	 *	@param		array		$accepts
	 *	@return		FormatInterface|NULL
	 */
	protected function matchAcceptsWithFormats( array $accepts ): ?FormatInterface
	{
		foreach( $accepts as $mimeType => $quality ){
			foreach( $this->formats as $format ){
				if( in_array( $mimeType, $format->getMimeTypes(), TRUE ) ){
					Log::debug( '> matched format: '.$mimeType );
					return $format;
				}
			}
		}
		return NULL;
	}

	/**
	 *	@return		FormatInterface
	 */
	protected function negotiateResponseFormat(): FormatInterface
	{
		Log::debug( 'REST Server: negotiateResponseFormat' );
		$path			= $this->context->getRequest()->getPath();
		Log::debug( '> path: '.$path );
		/** @var HeaderField|NULL $acceptHeader */
		$acceptHeader	= $this->context->getRequest()->getHeadersByName( 'Accept', TRUE );
		if( NULL === $acceptHeader )
			throw new RuntimeException( 'No accept header set' );

		/** @var array $accepts */
		$accepts	= $acceptHeader->getValue( TRUE );
		Log::debug( '> accepts by request: '.json_encode( $accepts ) );
		if( '' !== $this->options->get( 'forceMimeType', '' ) ){
			$accepts	= [$this->options->get( 'forceMimeType' ) => 1];
			Log::debug( '> accepts by force: '.json_encode( $accepts ) );
		}

		if( preg_match( '/\.\w+$/', $path ) === 0 ){
			foreach( $this->formats as $format ){
				$extension	= preg_quote( $format->getExtension(), '/' );
				if( preg_match( '/'.$extension.'$/', $path ) === 1 ){
					$accepts	= [$format->getMimeTypes()[0] => 1];
					Log::debug( '> accepts by extension: '.json_encode( $accepts ) );
				}
			}
		}
		Log::debug( '> accepts finally: '.json_encode( $accepts ) );

		$format	= $this->matchAcceptsWithFormats( $accepts );
		if( NULL === $format )
			throw new RuntimeException( 'Content type is not supported (no acceptable format found)' );
		return $format;
	}

	/**
	 *	@param		Route		$route
	 *	@return		mixed
	 *	@throws		ReflectionException
	 */
	protected function realizeResolvedRoute( Route $route ): mixed
	{
		if( !class_exists( $route->getController() ) )
			throw new RangeException( 'Class "'.$route->getController().'" is not existing' );
		$object		= ObjectFactory::createObject( $route->getController(), [$this->context] );

//  @todo handle exception in method calls
//try{
		$factory	= new MethodFactory( $object );
		$result		= $factory->callMethod( $route->getAction(), $route->getArguments() );
		//  @todo make handling of dev output configurable + log
		if( $this->context->getBuffer()->has() ){
			throw new RuntimeException( $this->context->getBuffer()->get( TRUE ), 500 );
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
	* array_merge_recursive(['key' => 'org value'], ['key' => 'new value']);
	*     => ['key' => ['org value', 'new value']];
	*
	* array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
	* Matching keys' values in the second array overwrite those in the first array, as is the
	* case with array_merge, i.e.:
	*
	* array_merge_recursive_distinct(['key' => 'org value'], ['key' => 'new value']);
	*     => ['key' => ['new value']];
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
		$merged	= $array1;
		foreach( $array2 as $key => &$value ){
			$isNest			= is_array( $value ) && isset( $merged[$key] ) && is_array( $merged[$key] );
			$merged[$key]	= $isNest ? $this->mergeOptions( $merged[$key], $value ) : $value;
		}
		return $merged;
	}
}
