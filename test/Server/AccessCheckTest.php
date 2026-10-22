<?php
declare(strict_types=1);

use CeusMedia\Common\Net\HTTP\Request as HttpRequest;
use CeusMedia\REST\Server\AccessCheck\Auth;
use CeusMedia\REST\Server\AccessCheck\IP;
use CeusMedia\REST\Server\AccessCheck\User;
use CeusMedia\REST\Server\Context;
use PHPUnit\Framework\TestCase;

class AccessCheckTest extends TestCase
{
	private Context $context;
	private array $server;
	private string $usersFile;

	protected function setUp(): void
	{
		$this->server	= $_SERVER;
		$this->context	= new Context();
		$this->usersFile	= sys_get_temp_dir().'/rest-users-'.getmypid().'.json';
		file_put_contents( $this->usersFile, json_encode( [
			'hashed'	=> ['password' => password_hash( 'secret pw', PASSWORD_DEFAULT )],
			'plain'		=> ['password' => 'plain-pw'],
			'off'		=> ['password' => password_hash( 'x', PASSWORD_DEFAULT ), 'disabled' => TRUE],
		] ) );
	}

	protected function tearDown(): void
	{
		$_SERVER	= $this->server;
		@unlink( $this->usersFile );
		//  the context opens an output buffer
		$this->context->getBuffer()->close();
	}

	public function testAuthDeniesWithoutConfiguredTokens(): void
	{
		$_SERVER['HTTP_AUTHORIZATION']	= 'Token=super_geheimes_token';
		self::assertSame( 'Access denied', ( new Auth( $this->context ) )->perform( new HttpRequest() ), 'former hard-coded token' );
	}

	public function testAuthBearerAndLegacyFormat(): void
	{
		$check	= new Auth( $this->context, ['tokens' => ['t0ken-A', 't0ken-B']] );
		$_SERVER['HTTP_AUTHORIZATION']	= 'Bearer t0ken-B';
		self::assertSame( '', $check->perform( new HttpRequest() ) );
		$_SERVER['HTTP_AUTHORIZATION']	= 'Token="t0ken-A"';
		self::assertSame( '', $check->perform( new HttpRequest() ) );
		$_SERVER['HTTP_AUTHORIZATION']	= 'Bearer wrong';
		self::assertSame( 'Access denied', $check->perform( new HttpRequest() ) );
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		self::assertSame( 'Access denied', $check->perform( new HttpRequest() ) );
	}

	public function testUserWithHashAndPlainPassword(): void
	{
		$check	= new User( $this->context, ['filePath' => $this->usersFile] );
		self::assertSame( '', $this->asUser( $check, 'hashed', 'secret pw' ) );
		self::assertSame( '', $this->asUser( $check, 'plain', 'plain-pw' ), 'plain text still works (deprecated)' );
	}

	public function testUserMessagesRevealNothing(): void
	{
		$check	= new User( $this->context, ['filePath' => $this->usersFile] );
		foreach( [['hashed', 'wrong'], ['unknown', 'x'], ['off', 'x'], ['', ''], ['plain', 'plain-pw ']] as [$user, $password] )
			self::assertSame( 'Access denied', $this->asUser( $check, $user, $password ), $user );
	}

	public function testUserDeniesIfFileIsMissing(): void
	{
		$check	= new User( $this->context, ['filePath' => '/does/not/exist.json'] );
		self::assertSame( 'Access denied', $this->asUser( $check, 'hashed', 'secret pw' ) );
	}

	public function testIpWhitelistBlacklistAndIpv6(): void
	{
		$check	= new IP( $this->context, ['whitelist' => ['10.0.0.0/8', 'fd00::/8'], 'blacklist' => ['10.9.9.9']] );
		self::assertSame( '', $check->perform( $this->requestFrom( '10.1.2.3' ) ) );
		self::assertSame( 'Access denied', $check->perform( $this->requestFrom( '10.9.9.9' ) ), 'blacklist' );
		self::assertSame( '', $check->perform( $this->requestFrom( 'fd12::1' ) ) );
		self::assertSame( 'Access denied', $check->perform( $this->requestFrom( '2001:db8::1' ) ), 'IPv6 is checked now' );
		self::assertStringNotContainsString( '2001', $check->perform( $this->requestFrom( '2001:db8::1' ) ), 'IP not echoed' );
	}

	public function testIpIgnoresSpoofedForwardedHeader(): void
	{
		$check	= new IP( $this->context, ['whitelist' => ['127.0.0.1']] );
		$_SERVER['HTTP_X_FORWARDED_FOR']	= '127.0.0.1';
		self::assertSame( 'Access denied', $check->perform( $this->requestFrom( '203.0.113.7' ) ) );
	}

	private function asUser( User $check, string $user, string $password ): string
	{
		$_SERVER['PHP_AUTH_USER']	= $user;
		$_SERVER['PHP_AUTH_PW']		= $password;
		return $check->perform( new HttpRequest() );
	}

	private function requestFrom( string $ip ): HttpRequest
	{
		putenv( 'REMOTE_ADDR='.$ip );
		$request	= ( new HttpRequest() )->fromEnv();
		putenv( 'REMOTE_ADDR' );
		return $request;
	}
}
