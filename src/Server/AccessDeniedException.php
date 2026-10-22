<?php
declare(strict_types=1);

/**
 *	Exception of a failed access check.
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

use RuntimeException;

/**
 *	Exception of a failed access check, answered with status 401 (or the given code) in the negotiated format.
 *
 *	@category		Library
 *	@package		CeusMedia_REST_Server
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2026 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class AccessDeniedException extends RuntimeException
{
	/**
	 *	@param	string					$message
	 *	@param	int						$code		HTTP status
	 *	@param	array<string,string>	$headers	Additional response headers, e.g. Retry-After
	 */
	public function __construct( string $message = 'Access denied', int $code = 401, protected array $headers = [] )
	{
		parent::__construct( $message, $code );
	}

	/**
	 *	@return		array<string,string>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}
}
