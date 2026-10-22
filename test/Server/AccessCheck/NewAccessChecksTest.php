<?php
declare(strict_types=1);

use CeusMedia\Cache\Adapter\Memory as MemoryCache;
use CeusMedia\Common\Net\HTTP\Request as HttpRequest;
use CeusMedia\REST\Server\AccessCheck\Origin;
use CeusMedia\REST\Server\AccessCheck\RateLimit;
use CeusMedia\REST\Server\AccessCheck\RouteAccess;
use CeusMedia\REST\Server\AuthenticatorInterface;
use CeusMedia\Router\Route;
use PHPUnit\Framework\TestCase;

/**
 *	RouteAccess, RateLimit and Origin.
 */
class NewAccessChecksTest extends TestCase
{
	public function testRouteAccess(): void
	{
		$check	= new RouteAccess( $this->authenticator( [] ), ['full'] );
		$route	= new Route( '/data', 'GET' );
		self::assertSame( 401, $check->check( new HttpRequest(), $route )->getStatus(), 'default roles protect routes' );

		$public	= ( new Route( '/login', 'POST' ) )->setMeta( ['public' => TRUE] );
		self::assertTrue( $check->check( new HttpRequest(), $public )->isAllowed() );

		$check	= new RouteAccess( $this->authenticator( ['setup'] ), ['full'] );
		self::assertSame( 403, $check->check( new HttpRequest(), $route )->getStatus(), 'role missing' );
		$route->setRoles( ['setup', 'full'] );
		self::assertTrue( $check->check( new HttpRequest(), $route )->isAllowed(), 'route roles replace default roles' );

		$open	= new RouteAccess( $this->authenticator( [] ) );
		self::assertTrue( $open->check( new HttpRequest(), new Route( '/x', 'GET' ) )->isAllowed(), 'no roles anywhere: open' );
	}

	public function testRateLimit(): void
	{
		//  20 seconds into a 60 second window (1_000_000_020 is a multiple of 60)
		$now	= 1_000_000_040;
		$check	= new RateLimit( new MemoryCache( NULL ), ['auth' => [3, 60], 'api' => ['limit' => 100, 'period' => 1]], NULL, 'rateLimit', static function() use ( &$now ): int {
			return $now;
		} );
		$route	= ( new Route( '/login', 'POST' ) )->setMeta( ['rateLimit' => 'auth'] );
		$a		= $this->requestFrom( '198.51.100.1' );
		$b		= $this->requestFrom( '198.51.100.2' );

		for( $i = 0; $i < 3; $i++ )
			self::assertTrue( $check->check( $a, $route )->isAllowed() );
		$denied	= $check->check( $a, $route );
		self::assertSame( 429, $denied->getStatus() );
		self::assertSame( ['Retry-After' => '40'], $denied->getHeaders(), 'until the end of the window' );
		self::assertTrue( $check->check( $b, $route )->isAllowed(), 'other IP' );

		$now	+= 40;
		self::assertTrue( $check->check( $a, $route )->isAllowed(), 'next window' );
		self::assertTrue( $check->check( $a, new Route( '/free', 'GET' ) )->isAllowed(), 'no group, no default: not limited' );
	}

	public function testRateLimitRejectsInvalidConfig(): void
	{
		$this->expectException( InvalidArgumentException::class );
		new RateLimit( new MemoryCache( NULL ), ['auth' => [0, 60]] );
	}

	public function testOrigin(): void
	{
		$check	= new Origin( ['https://partner.example'] );
		$route	= new Route( '/data', 'POST' );
		self::assertTrue( $check->check( $this->request( 'GET', ['Origin' => 'https://evil.example', 'Sec-Fetch-Site' => 'cross-site'] ), $route )->isAllowed(), 'reading is fine' );
		self::assertTrue( $check->check( $this->request( 'POST', [] ), $route )->isAllowed(), 'no browser headers' );
		self::assertTrue( $check->check( $this->request( 'POST', ['Origin' => 'http://app.local:8080', 'Host' => 'app.local:8080', 'Sec-Fetch-Site' => 'same-origin'] ), $route )->isAllowed() );
		self::assertSame( 403, $check->check( $this->request( 'POST', ['Sec-Fetch-Site' => 'cross-site'] ), $route )->getStatus() );
		self::assertSame( 403, $check->check( $this->request( 'DELETE', ['Origin' => 'https://evil.example', 'Host' => 'app.local'] ), $route )->getStatus() );
		self::assertTrue( $check->check( $this->request( 'PUT', ['Origin' => 'https://partner.example', 'Sec-Fetch-Site' => 'cross-site', 'Host' => 'app.local'] ), $route )->isAllowed(), 'allowed origin' );
	}

	/**
	 *	@param	array<int,string>	$roles
	 */
	private function authenticator( array $roles ): AuthenticatorInterface
	{
		return new class( $roles ) implements AuthenticatorInterface {
			/** @param array<int,string> $roles */
			public function __construct( private readonly array $roles )
			{
			}

			public function getRoles( HttpRequest $request ): array
			{
				return $this->roles;
			}
		};
	}

	private function requestFrom( string $ip ): HttpRequest
	{
		putenv( 'REMOTE_ADDR='.$ip );
		$request	= ( new HttpRequest() )->fromEnv();
		putenv( 'REMOTE_ADDR' );
		return $request;
	}

	/**
	 *	@param	array<string,string>	$headers
	 */
	private function request( string $method, array $headers ): HttpRequest
	{
		$request	= new HttpRequest();
		$request->getMethod()->set( $method );
		foreach( $headers as $name => $value )
			$request->addHeaderPair( $name, $value );
		return $request;
	}
}
