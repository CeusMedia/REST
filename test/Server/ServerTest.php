<?php
declare(strict_types=1);

use CeusMedia\REST\Server;
use CeusMedia\Router\Registry\Source\Memory as MemorySource;
use CeusMedia\Router\Route;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/Fixture/Controller_Errors.php';

/**
 *	Error handling of the server: negotiated format, no details for clients, 204 for NULL.
 */
class ServerTest extends TestCase
{
	private array $server;
	private string|false $errorLog;
	/** FALSE: request variables in $_SERVER only, like the built-in web server (php -S) */
	private bool $useEnvironment	= TRUE;

	protected function setUp(): void
	{
		$this->server	= $_SERVER;
		//  errors are logged on purpose, keep the test output clean
		$this->errorLog	= ini_set( 'error_log', sys_get_temp_dir().'/rest-server-test.log' );
	}

	protected function tearDown(): void
	{
		$_SERVER	= $this->server;
		ini_set( 'error_log', FALSE !== $this->errorLog ? $this->errorLog : '' );
		restore_error_handler();
		foreach( ['HTTP_HOST', 'REQUEST_METHOD', 'REQUEST_URI', 'SCRIPT_NAME', 'HTTP_ACCEPT'] as $key )
			putenv( $key );
	}

	public function testResult(): void
	{
		[$status, $body]	= $this->call( 'ok' );
		self::assertSame( 200, $status );
		self::assertSame( ['ok' => TRUE], json_decode( $body, TRUE ) );
	}

	public function testNullResultIsNoContent(): void
	{
		[$status, $body]	= $this->call( 'nothing' );
		self::assertSame( 204, $status );
		self::assertSame( '', $body );
	}

	public function testNoticesDoNotStopTheRequest(): void
	{
		[$status, $body]	= $this->call( 'notice' );
		self::assertSame( 200, $status );
		self::assertSame( ['still' => 'running'], json_decode( $body, TRUE ) );
	}

	public function testInternalErrorsShowNoDetails(): void
	{
		foreach( ['exception', 'typeError'] as $action ){
			[$status, $body]	= $this->call( $action );
			self::assertSame( 500, $status, $action );
			self::assertSame( ['error' => ['status' => 500, 'message' => 'Internal Server Error']], json_decode( $body, TRUE ), $action );
			self::assertStringNotContainsString( 'secret', $body );
			self::assertStringNotContainsString( '.php', $body );
		}
	}

	public function testExposeErrorsForDevelopment(): void
	{
		[, $body]	= $this->call( 'exception', ['exposeErrors' => TRUE] );
		self::assertStringContainsString( 'secret detail', $body );
	}

	public function testClientErrorsKeepStatusAndMessage(): void
	{
		[$status, $body]	= $this->call( 'clientError' );
		self::assertSame( 422, $status );
		self::assertSame( 'Field "name" is missing', $this->decode( $body )['error']['message'] ?? NULL );
	}

	public function testUnknownRouteAsJson(): void
	{
		[$status, $body]	= $this->call( 'ok', [], '/unknown' );
		self::assertSame( 404, $status );
		self::assertSame( 404, $this->decode( $body )['error']['status'] ?? NULL );
	}

	public function testFormatByExtension(): void
	{
		//  extension wins over forceMimeType (JSON by default in these tests)
		[$status, $body, $headers]	= $this->call( 'ok', [], '/errors/ok.php' );
		self::assertSame( 200, $status );
		self::assertSame( serialize( ['ok' => TRUE] ), $body );
		self::assertStringStartsWith( 'application/x-php', $headers['content-type'] ?? '' );

		//  without forceMimeType: extension wins over Accept header
		[$status, $body]	= $this->call( 'ok', ['forceMimeType' => 'text/html'], '/errors/ok.json' );
		self::assertSame( 200, $status );
		self::assertSame( ['ok' => TRUE], json_decode( $body, TRUE ) );
	}

	public function testRequestVariablesFromServerOnly(): void
	{
		$this->useEnvironment	= FALSE;
		[$status, $body]	= $this->call( 'ok', [], '/errors/ok' );
		self::assertSame( 200, $status );
		self::assertSame( ['ok' => TRUE], json_decode( $body, TRUE ) );
		self::assertSame( '/errors/ok', getenv( 'REQUEST_URI' ), 'imported to environment' );

		//  environment wins, if given: $_SERVER says "nothing" (204), environment says "ok" (200)
		putenv( 'REQUEST_URI=/errors/ok' );
		[$status]	= $this->call( 'nothing', [], NULL, NULL, 'application/json', FALSE );
		self::assertSame( 200, $status );
	}

	public function testFormatByAcceptHeaderWithoutForceMimeType(): void
	{
		foreach( [NULL, ''] as $forceMimeType ){
			[$status, $body]	= $this->call( 'ok', ['forceMimeType' => $forceMimeType] );
			self::assertSame( 200, $status, var_export( $forceMimeType, TRUE ) );
			self::assertSame( ['ok' => TRUE], json_decode( $body, TRUE ) );
		}
		[$status, $body]	= $this->call( 'ok', ['forceMimeType' => NULL], NULL, NULL, 'application/x-php' );
		self::assertSame( 200, $status );
		self::assertSame( serialize( ['ok' => TRUE] ), $body );
	}

	public function testAcceptWildcards(): void
	{
		$noForce	= ['forceMimeType' => NULL];
		foreach( ['*/*', 'application/*', 'image/png, */*;q=0.1'] as $accept ){
			[$status, $body, $headers]	= $this->call( 'ok', $noForce, NULL, NULL, $accept );
			self::assertSame( 200, $status, $accept );
			self::assertSame( ['ok' => TRUE], json_decode( $body, TRUE ), $accept.': default format JSON' );
			self::assertStringStartsWith( 'application/json', $headers['content-type'] ?? '', $accept );
		}
		//  without Accept header every format is acceptable
		[$status, $body]	= $this->call( 'ok', $noForce, NULL, NULL, NULL );
		self::assertSame( 200, $status );
		self::assertSame( ['ok' => TRUE], json_decode( $body, TRUE ) );
		//  default format by option
		[$status, $body]	= $this->call( 'ok', $noForce + ['defaultMimeType' => 'application/x-php'], NULL, NULL, '*/*' );
		self::assertSame( 200, $status );
		self::assertSame( serialize( ['ok' => TRUE] ), $body );
	}

	public function testAcceptQualities(): void
	{
		$noForce	= ['forceMimeType' => NULL];
		[, $body]	= $this->call( 'ok', $noForce, NULL, NULL, 'application/json;q=0.5, application/x-php' );
		self::assertSame( serialize( ['ok' => TRUE] ), $body, 'higher quality wins' );
		[, , $headers]	= $this->call( 'ok', $noForce, NULL, NULL, 'application/json;q=0, */*' );
		self::assertStringStartsWith( 'text/html', $headers['content-type'] ?? '', 'quality 0 is not acceptable' );
	}

	public function testNotAcceptableBeforeController(): void
	{
		//  the controller would fail with 500, so 406 shows it has not been called
		[$status, $body]	= $this->call( 'exception', ['forceMimeType' => NULL], NULL, NULL, 'image/png' );
		self::assertSame( 406, $status );
		self::assertSame( '406 Not Acceptable', $body );
	}

	public function testUnsupportedForcedMimeTypeIsServerError(): void
	{
		[$status, $body]	= $this->call( 'ok', ['forceMimeType' => 'application/xml'] );
		self::assertSame( 500, $status );
		self::assertStringNotContainsString( 'application/xml', $body );
	}

	public function testParseAcceptHeader(): void
	{
		$server	= ( new ReflectionClass( Server::class ) )->newInstanceWithoutConstructor();
		$method	= new ReflectionMethod( Server::class, 'parseAcceptHeader' );
		self::assertSame( [
			'text/html'			=> 1.0,
			'application/xml'	=> 1.0,
			'text/css'			=> 1.0,
			'text/xml'			=> 1.0,
			'application/*'		=> 1.0,
			'*/*'				=> 0.8,
		], $method->invoke( $server, 'application/*, */*;q=0.8, text/html;level=1, Application/XML, text/css, text/xml;q=1.0, invalid' ) );
	}

	public function testForceMimeTypeWithoutAcceptHeader(): void
	{
		[$status, $body]	= $this->call( 'ok', [], NULL, NULL, NULL );
		self::assertSame( 200, $status );
		self::assertSame( ['ok' => TRUE], json_decode( $body, TRUE ) );
	}

	public function testFormatByExtensionAlsoForErrors(): void
	{
		[$status, $body]	= $this->call( 'exception', [], '/errors/exception.php' );
		self::assertSame( 500, $status );
		self::assertSame( ['error' => ['status' => 500, 'message' => 'Internal Server Error']], unserialize( $body ) );
	}

	public function testUnknownExtensionOrRouteIsNotFound(): void
	{
		self::assertSame( 404, $this->call( 'ok', [], '/errors/ok.txt' )[0] );
		self::assertSame( 404, $this->call( 'ok', [], '/unknown.json' )[0] );
		//  disabled format
		self::assertSame( 404, $this->call( 'ok', [], '/errors/ok.xml' )[0] );
	}

	public function testRouteWithDotIsResolvedAsItIs(): void
	{
		[$status, $body]	= $this->call( 'ok', [], '/files/report.php', static function( Server $server ): void {
			$route	= new Route( '/files/report.php', 'GET' );
			$route->setController( 'Controller_Errors' );
			$route->setAction( 'ok' );
			$source	= new MemorySource();
			$source->routes[]	= $route;
			$server->addRouterRegistrySource( $source );
		} );
		self::assertSame( 200, $status );
		self::assertSame( ['ok' => TRUE], json_decode( $body, TRUE ), 'not PHP, the dot belongs to the route' );
	}

	public function testDeniedAccessAsJsonWithoutExit(): void
	{
		[$status, $body]	= $this->call( 'ok', [], NULL, static function( Server $server ): void {
			$server->registerAccessCheck( \CeusMedia\REST\Server\AccessCheck\Auth::class, 'perform', ['tokens' => ['abc']] );
		} );
		self::assertSame( 401, $status );
		self::assertSame( ['error' => ['status' => 401, 'message' => 'Access denied']], json_decode( $body, TRUE ) );
	}

	public function testRateLimitAnswerWithRetryAfter(): void
	{
		$cache	= new \CeusMedia\Cache\Adapter\Memory( NULL );
		$setup	= static function( Server $server ) use ( $cache ): void {
			$server->addAccessCheck( new \CeusMedia\REST\Server\AccessCheck\RateLimit( $cache, ['default' => [1, 3600]], 'default' ) );
		};
		self::assertSame( 200, $this->call( 'ok', [], NULL, $setup )[0] );
		[$status, $body, $headers]	= $this->call( 'ok', [], NULL, $setup );
		self::assertSame( 429, $status );
		self::assertSame( 'Too Many Requests', $this->decode( $body )['error']['message'] ?? NULL );
		self::assertArrayHasKey( 'retry-after', $headers );
	}

	public function testFailureLimitCountsFailedResponses(): void
	{
		$cache	= new \CeusMedia\Cache\Adapter\Memory( NULL );
		$setup	= static function( Server $server ) use ( $cache ): void {
			$server->addAccessCheck( new \CeusMedia\REST\Server\AccessCheck\FailureLimit( $cache, ['test' => [2, 900]], [422] ) );
		};
		self::assertSame( 200, $this->call( 'ok', [], NULL, $setup )[0], 'success is not counted' );
		self::assertSame( 422, $this->call( 'clientError', [], NULL, $setup )[0] );
		self::assertSame( 500, $this->call( 'exception', [], NULL, $setup )[0], 'other status is not counted' );
		self::assertSame( 422, $this->call( 'clientError', [], NULL, $setup )[0] );
		[$status, $body, $headers]	= $this->call( 'ok', [], NULL, $setup );
		self::assertSame( 429, $status );
		self::assertSame( 'Too Many Failed Attempts', $this->decode( $body )['error']['message'] ?? NULL );
		self::assertContains( $headers['retry-after'] ?? NULL, ['899', '900'], 'period of the first failure (real clock)' );
	}

	public function testResponseHeadersAlsoOnErrors(): void
	{
		[$status, , $headers]	= $this->call( 'exception', ['responseHeaders' => Server::SECURE_RESPONSE_HEADERS] );
		self::assertSame( 500, $status );
		self::assertSame( 'no-store', $headers['cache-control'] ?? NULL );
		self::assertSame( 'nosniff', $headers['x-content-type-options'] ?? NULL );
	}

	public function testErrorFormatterWithMessagesByStatus(): void
	{
		$setup	= static function( Server $server ): void {
			$server->setErrorFormatter( new \CeusMedia\REST\Server\ErrorFormatter( [500 => 'Interner Fehler.'] ) );
		};
		[$status, $body]	= $this->call( 'exception', [], NULL, $setup );
		self::assertSame( 500, $status );
		self::assertSame( ['error' => ['status' => 500, 'message' => 'Interner Fehler.']], json_decode( $body, TRUE ) );
		//  other statuses keep their message
		[, $body]	= $this->call( 'clientError', [], NULL, $setup );
		self::assertSame( 'Field "name" is missing', $this->decode( $body )['error']['message'] ?? NULL );
	}

	public function testOwnErrorFormatGetsSafeMessageAndException(): void
	{
		$formatter	= new class implements \CeusMedia\REST\Server\ErrorFormatterInterface {
			public function format( int $status, string $message, ?Throwable $exception = NULL ): array
			{
				return ['code' => 'e'.$status, 'text' => $message, 'type' => NULL !== $exception ? get_class( $exception ) : NULL];
			}
		};
		$setup	= static function( Server $server ) use ( $formatter ): void {
			$server->setErrorFormatter( $formatter );
		};
		[$status, $body]	= $this->call( 'exception', [], NULL, $setup );
		self::assertSame( 500, $status );
		self::assertSame( ['code' => 'e500', 'text' => 'Internal Server Error', 'type' => RuntimeException::class], json_decode( $body, TRUE ) );
		self::assertStringNotContainsString( 'secret', $body );

		[$status, $body]	= $this->call( 'ok', [], '/unknown', $setup );
		self::assertSame( 404, $status );
		self::assertMatchesRegularExpression( '/"code":\s*"e404"/', $body );
	}

	public function testFailingErrorFormatterFallsBackToText(): void
	{
		$formatter	= new class implements \CeusMedia\REST\Server\ErrorFormatterInterface {
			public function format( int $status, string $message, ?Throwable $exception = NULL ): array
			{
				throw new LogicException( 'broken formatter' );
			}
		};
		[$status, $body]	= $this->call( 'exception', [], NULL, static function( Server $server ) use ( $formatter ): void {
			$server->setErrorFormatter( $formatter );
		} );
		self::assertSame( 500, $status );
		self::assertSame( '500 Internal Server Error', $body );
	}

	/**
	 *	@return		array{error?: array{status?: int, message?: string}}
	 */
	private function decode( string $body ): array
	{
		/** @var array{error?: array{status?: int, message?: string}} $data */
		$data	= json_decode( $body, TRUE, 512, JSON_THROW_ON_ERROR );
		return $data;
	}

	/**
	 *	@return		array{int, string, array<string,string>}		Status, body and headers (lower case names)
	 */
	private function call( string $action, array $options = [], ?string $path = NULL, ?callable $setup = NULL, ?string $accept = 'application/json', bool $clearEnvironment = TRUE ): array
	{
		$path	??= '/errors/'.$action;
		foreach( ['HTTP_HOST' => 'localhost', 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $path, 'SCRIPT_NAME' => '/index.php', 'HTTP_ACCEPT' => $accept] as $key => $value ){
			if( NULL === $value ){
				putenv( $key );
				unset( $_SERVER[$key] );
				continue;
			}
			if( $this->useEnvironment )
				putenv( $key.'='.$value );
			else if( $clearEnvironment )
				putenv( $key );
			$_SERVER[$key]	= $value;
		}
		//  the server context opens its own output buffer: collect everything above the current level
		$level	= ob_get_level();
		$server	= new Server( $options + ['forceMimeType' => 'application/json'] );
		$source	= new MemorySource();
		foreach( ['ok', 'nothing', 'notice', 'exception', 'typeError', 'clientError'] as $name ){
			$route	= new Route( '/errors/'.$name, 'GET' );
			$route->setController( 'Controller_Errors' );
			$route->setAction( $name );
			$route->setMeta( ['failureLimit' => 'test'] );
			$source->routes[]	= $route;
		}
		$server->addRouterRegistrySource( $source );
		if( NULL !== $setup )
			$setup( $server );

		ob_start();
		$server->handleRequest();
		$body	= '';
		while( ob_get_level() > $level )
			$body	= (string) ob_get_clean().$body;
		restore_error_handler();
		$status	= (int) $server->getResponse()->getStatus();
		$headers	= [];
		foreach( $server->getResponse()->getHeaders() as $field )
			$headers[strtolower( $field->getName() )]	= (string) $field->getValue();
		return [$status, $body, $headers];
	}
}
