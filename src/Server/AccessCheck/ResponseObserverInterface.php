<?php
declare(strict_types=1);

/**
 *	Observer of responses, for access checks which learn from results.
 *
 *	Copyright (c) 2026 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_REST_Server_AccessCheck
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2026 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */

namespace CeusMedia\REST\Server\AccessCheck;

use CeusMedia\Common\Net\HTTP\Request as HttpRequest;
use CeusMedia\Router\Route;

/**
 *	Access checks implementing this interface are told the status of every response of a resolved route,
 *	also of errors and denied requests, e.g. to count failures (see FailureLimit).
 *
 *	@category		Library
 *	@package		CeusMedia_REST_Server_AccessCheck
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2026 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
interface ResponseObserverInterface
{
	/**
	 *	Called after the response status is known, before it is sent.
	 *	Exceptions are logged and do not change the response.
	 *	@param		HttpRequest		$request
	 *	@param		Route			$route		Resolved route, incl. roles and meta information
	 *	@param		int				$status		HTTP status of the response
	 *	@return		void
	 */
	public function observeResponse( HttpRequest $request, Route $route, int $status ): void;
}
