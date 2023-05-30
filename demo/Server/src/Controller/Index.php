<?php

use CeusMedia\REST\Server\Context;
use CeusMedia\REST\Server\Controller as ServerController;

class Controller_Index extends ServerController
{
	public function __construct( Context $context )
	{
		$this->resources	= $context;
	}

	public function index(): string
	{
		return 'HTTP REST Server Index | Try path: test';
	}
}
