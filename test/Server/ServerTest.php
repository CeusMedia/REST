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
	private function call( string $action, array $options = [], ?string $path = NULL, ?callable $setup = NULL ): array
	{
		$path	??= '/errors/'.$action;
		foreach( ['HTTP_HOST' => 'localhost', 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $path, 'SCRIPT_NAME' => '/index.php', 'HTTP_ACCEPT' => 'application/json'] as $key => $value ){
			putenv( $key.'='.$value );
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
