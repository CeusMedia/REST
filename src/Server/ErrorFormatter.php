<?php
declare(strict_types=1);

/**
 *	Default builder of error response bodies.
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
 *	Default error response body: {"error":{"status":404,"message":"..."}}.
 *	Extend and override format() or getMessage() to change structure or texts.
 *
 *	@category		Library
 *	@package		CeusMedia_REST_Server
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2026 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class ErrorFormatter implements ErrorFormatterInterface
{
	/**
	 *	@param		array<int,string>	$messages		Texts by HTTP status, replacing the given message (e.g. translations)
	 */
	public function __construct( protected array $messages = [] )
	{
	}

	/**
	 *	@param		int				$status
	 *	@param		string			$message
	 *	@param		Throwable|NULL	$exception
	 *	@return		array<string,mixed>
	 */
	public function format( int $status, string $message, ?Throwable $exception = NULL ): array
	{
		return ['error' => [
			'status'	=> $status,
			'message'	=> $this->getMessage( $status, $message ),
		]];
	}

	protected function getMessage( int $status, string $message ): string
	{
		return $this->messages[$status] ?? $message;
	}
}
