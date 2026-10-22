<?php
declare(strict_types=1);

/**
 *	Controller for ServerTest.
 */
class Controller_Errors
{
	public function __construct( object $context )
	{
	}

	public function ok(): array
	{
		return ['ok' => TRUE];
	}

	public function nothing(): ?array
	{
		return NULL;
	}

	public function notice(): array
	{
		trigger_error( 'just a notice', E_USER_DEPRECATED );
		return ['still' => 'running'];
	}


	public function exception(): array
	{
		throw new RuntimeException( 'secret detail: /srv/app/config.ini' );
	}

	public function typeError(): array
	{
		return strlen( [] );
	}

	public function clientError(): array
	{
		throw new InvalidArgumentException( 'Field "name" is missing', 422 );
	}
}
