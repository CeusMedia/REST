<?php
declare(strict_types=1);

/**
 *	Result of an access check.
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


/**
 *	Result of an access check: allowed, or denied with HTTP status, message and headers.
 *
 *	@category		Library
 *	@package		CeusMedia_REST_Server
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2026 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
final readonly class AccessResult
{
	/**
	 *	@param	bool					$allowed
	 *	@param	int						$status
	 *	@param	string					$message
	 *	@param	array<string,string>	$headers
	 */
	private function __construct(
		private bool   $allowed,
		private int    $status = 200,
		private string $message = '',
		private array  $headers = [],
	)
	{
	}

	public static function allow(): self
	{
		return new self( TRUE );
	}

	/**
	 *	@param		int						$status		HTTP status, e.g. 401, 403, 429
	 *	@param		string					$message	Message for the client
	 *	@param		array<string,string>	$headers	Additional response headers, e.g. Retry-After
	 *	@return		self
	 */
	public static function deny( int $status = 403, string $message = 'Access denied', array $headers = [] ): self
	{
		return new self( FALSE, $status, $message, $headers );
	}

	public function isAllowed(): bool
	{
		return $this->allowed;
	}

	public function getStatus(): int
	{
		return $this->status;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 *	@return		array<string,string>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}
}
