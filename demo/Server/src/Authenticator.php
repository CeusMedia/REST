<?php

use CeusMedia\Common\FS\File\JSON\Reader as JsonReader;
use CeusMedia\Common\Net\HTTP\Request as HttpRequest;
use CeusMedia\REST\Server\AuthenticatorInterface;

/**
 *	Demo authenticator: HTTP Basic auth against a JSON file of users,
 *	{"name": {"password": "<hash from password_hash()>", "roles": ["user"], "enabled": true}}.
 *	Create a hash: php -r 'echo password_hash( "myPassword", PASSWORD_DEFAULT ), PHP_EOL;'
 */
class Authenticator implements AuthenticatorInterface
{
	/** Hash to verify against for unknown users, so the response time reveals nothing */
	protected const string DUMMY_HASH	= '$2y$10$UkuQNcdghZ1n/GHb5s/iK.2K35j6YLXJqlH13c9x6fIBiefIhvJ82';

	public function __construct( protected string $filePath )
	{
	}

	public function getRoles( HttpRequest $request ): array
	{
		$username	= trim( (string) ( $_SERVER['PHP_AUTH_USER'] ?? '' ) );
		$password	= (string) ( $_SERVER['PHP_AUTH_PW'] ?? '' );
		if( '' === $username || '' === $password || !file_exists( $this->filePath ) )
			return [];

		/** @var array<string,array{password?: string, roles?: array<int,string>, enabled?: bool}> $users */
		$users	= (array) JsonReader::load( $this->filePath, TRUE );
		$user	= $users[$username] ?? NULL;
		$valid	= password_verify( $password, (string) ( $user['password'] ?? self::DUMMY_HASH ) );
		if( NULL === $user || !$valid || FALSE === ( $user['enabled'] ?? TRUE ) )
			return [];
		return array_values( array_map( 'strval', $user['roles'] ?? [] ) );
	}
}
