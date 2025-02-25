<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

/**
 *	...
 *
 *	Copyright (c) 2007-2023 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
namespace CeusMedia\REST\Server;

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Router\Router as Router;
use CeusMedia\Common\Net\HTTP\Request as HttpRequest;
use CeusMedia\Common\Net\HTTP\Response as HttpResponse;
use CeusMedia\Common\UI\OutputBuffer as OutputBuffer;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_REST
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class Context extends Dictionary
{
	protected HttpRequest $request;
	protected HttpResponse $response;
	protected Router $router;
	protected OutputBuffer $buffer;

	public function __construct()
	{
		parent::__construct();
		$this->request		= new HttpRequest();
		$this->response		= new HttpResponse();
		$this->router		= new Router();
		$this->buffer		= new OutputBuffer();
		$this->request->fromEnv();
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
