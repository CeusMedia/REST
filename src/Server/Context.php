<?php
/**
 *	...
 *
 *	Copyright (c) 2007-2019 Christian Würker (ceusmedia.de)
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_REST
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
namespace CeusMedia\REST\Server;

use CeusMedia\Router\Router as Router;
use Net_HTTP_Request_Receiver as HttpRequest;
use Net_HTTP_Response as HttpResponse;
use UI_OutputBuffer as OutputBuffer;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_REST
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class Context extends \ADT_List_Dictionary
{
	protected $request;
	protected $response;
	protected $router;
	protected $buffer;

	public function __construct()
	{
		parent::__construct();
		$this->request		= new HttpRequest();
		$this->response		= new HttpResponse();
		$this->router		= new Router();
		$this->buffer		= new OutputBuffer();
	}

	public function getRequest(): HttpRequest
	{
		return $this->request;
	}

	public function getResponse(): HttpResponse
	{
		return $this->response;
	}

	public function getRouter(): Router
	{
		return $this->router;
	}

	public function getBuffer(): OutputBuffer
	{
		return $this->buffer;
	}
}
