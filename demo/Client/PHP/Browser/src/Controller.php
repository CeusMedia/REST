<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Common\Net\HTTP\Request\Receiver as Request;
use CeusMedia\REST\Client as Client;

abstract class Controller
{

	protected string $baseUri;
	protected Client $client;
	protected Request $request;

	public function __construct( Client $client, Request $request )
	{
		$this->client	= $client;
		$this->request	= $request;

		$host	= getenv( 'SERVER_NAME' ).':'.getenv( 'SERVER_PORT' );
		$path	= dirname( getenv( 'SCRIPT_NAME' ) ).'/';
		$this->baseUri	= 'http://'.$host.$path;
	}

	protected function redirect( ?string $uri = NULL ): void
	{
		header( 'Location: '.$this->baseUri.$uri );
		exit;
	}

	abstract public function handle( Dictionary $arguments );
}
