<?php
declare(strict_types=1);

use CeusMedia\Cache\Adapter\Memory as MemoryCache;
use CeusMedia\Common\Net\HTTP\Request as HttpRequest;
use CeusMedia\REST\Server\AccessCheck\FailureLimit;
use CeusMedia\Router\Route;
use PHPUnit\Framework\TestCase;

/**
 *	FailureLimit: counts failed responses per IP and group, denies after the limit.
 */
class FailureLimitTest extends TestCase
{
	private int $now	= 1_000_000_000;

	public function testDeniesAfterLimitUntilPeriodOfFirstFailureIsOver(): void
	{
		$check	= $this->createCheck( ['auth' => [3, 900]] );
		$route	= $this->route( 'auth' );
		$a		= $this->requestFrom( '198.51.100.1' );

		$this->recordFailures( $check, $a, $route, 3, 100 );
		$denied	= $check->check( $a, $route );
		self::assertSame( 429, $denied->getStatus() );
		self::assertSame( ['Retry-After' => '700'], $denied->getHeaders(), 'period counts from the first failure' );
		self::assertTrue( $check->check( $this->requestFrom( '198.51.100.2' ), $route )->isAllowed(), 'other IP' );

		$this->now	+= 700;
		self::assertTrue( $check->check( $a, $route )->isAllowed(), 'period over' );
		$this->recordFailures( $check, $a, $route, 1 );
		self::assertTrue( $check->check( $a, $route )->isAllowed(), 'counting starts again' );
	}

	public function testOnlyFailureStatusesCount(): void
	{
		$check	= $this->createCheck( ['auth' => [1, 60]], [401, 422] );
		$route	= $this->route( 'auth' );
		$a		= $this->requestFrom( '198.51.100.1' );
		foreach( [200, 201, 204, 403, 429, 500] as $status )
			$check->observeResponse( $a, $route, $status );
		self::assertTrue( $check->check( $a, $route )->isAllowed() );
		$check->observeResponse( $a, $route, 422 );
		self::assertSame( 429, $check->check( $a, $route )->getStatus() );
	}

	public function testSuccessDoesNotReset(): void
	{
		$check	= $this->createCheck( ['auth' => [2, 60]] );
		$route	= $this->route( 'auth' );
		$a		= $this->requestFrom( '198.51.100.1' );
		$check->observeResponse( $a, $route, 401 );
		$check->observeResponse( $a, $route, 200 );
		$check->observeResponse( $a, $route, 401 );
		self::assertSame( 429, $check->check( $a, $route )->getStatus() );
	}

	public function testRoutesWithoutGroupAreNotCounted(): void
	{
		$check	= $this->createCheck( ['auth' => [1, 60]] );
		$a		= $this->requestFrom( '198.51.100.1' );
		$free	= new Route( '/data', 'GET' );
		$this->recordFailures( $check, $a, $free, 5 );
		self::assertTrue( $check->check( $a, $free )->isAllowed() );
		$unknown	= $this->route( 'other' );
		$this->recordFailures( $check, $a, $unknown, 5 );
		self::assertTrue( $check->check( $a, $unknown )->isAllowed(), 'unknown group' );
		self::assertTrue( $check->check( $a, $this->route( 'auth' ) )->isAllowed(), 'groups are separate' );
	}

	public function testRejectsInvalidConfig(): void
	{
		$this->expectException( InvalidArgumentException::class );
		new FailureLimit( new MemoryCache( NULL ), ['auth' => [0, 60]] );
	}

	/**
	 *	@param	array<string,array<int,int>>	$limits
	 *	@param	array<int,int>					$statuses
	 */
	private function createCheck( array $limits, array $statuses = [401, 403] ): FailureLimit
	{
		return new FailureLimit( new MemoryCache( NULL ), $limits, $statuses, 'failureLimit', fn(): int => $this->now );
	}

	private function recordFailures( FailureLimit $check, HttpRequest $request, Route $route, int $times, int $secondsBetween = 0 ): void
	{
		for( $i = 0; $i < $times; $i++ ){
			if( $i > 0 )
				$this->now	+= $secondsBetween;
			$check->observeResponse( $request, $route, 401 );
		}
	}

	private function route( string $group ): Route
	{
		return ( new Route( '/login', 'POST' ) )->setMeta( ['failureLimit' => $group] );
	}

	private function requestFrom( string $ip ): HttpRequest
	{
		putenv( 'REMOTE_ADDR='.$ip );
		$request	= ( new HttpRequest() )->fromEnv();
		putenv( 'REMOTE_ADDR' );
		return $request;
	}
}
