<?php
declare(strict_types=1);

/**
 *	Builds the body of error responses.
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
 *	@package		CeusMedia_REST_Server
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2026 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */

namespace CeusMedia\REST\Server;

use Throwable;

/**
 *	Builds the body of error responses, which is then sent in the negotiated format.
 *	Set via Server::setErrorFormatter() to change structure or texts, e.g. to translate messages.
 *
 *	@category		Library
 *	@package		CeusMedia_REST_Server
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2026 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
interface ErrorFormatterInterface
{
	/**
	 *	Returns the response data of an error.
	 *	The message is safe to show: for internal errors (5xx) it is a generic text unless option exposeErrors is set.
	 *	The exception is given for inspection only (e.g. its class or fields), its message may contain details
	 *	which must not reach the client. It is NULL for fatal PHP errors.
	 *	@param		int				$status			HTTP status code
	 *	@param		string			$message		Message safe to show to the client
	 *	@param		Throwable|NULL	$exception		Cause of the error, if available
	 *	@return		array<string,mixed>
	 */
	public function format( int $status, string $message, ?Throwable $exception = NULL ): array;
}
