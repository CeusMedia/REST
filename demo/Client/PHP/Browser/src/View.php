<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Common\Net\HTTP\Request\Receiver as Request;
use CeusMedia\REST\Client;

abstract class View
{
	protected Client $client;
	protected Request $request;
	protected Dictionary $data;

	public function __construct( Client $client, Request $request )
	{
		$this->client	= $client;
		$this->request	= $request;
		$this->data		= new Dictionary();
	}

	public function add( $key, $value ): bool
	{
		return $this->data->set( $key, $value );
	}

	protected function get( $key )
	{
		return $this->data->get( $key );
	}

	abstract public function render(): string;
}
