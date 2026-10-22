<?php
declare(strict_types=1);

/**
 *	CSRF protection by origin of writing requests.
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
use CeusMedia\REST\Server\AccessResult;
use CeusMedia\Router\Route;

/**
 *	Denies writing requests (POST, PUT, PATCH, DELETE) which a browser marks as cross-site
 *	(Sec-Fetch-Site) or whose Origin does not match the host, unless the origin is allowed.
 *	Requests without these headers (e.g. from servers or CLI tools) are not affected.
 *	Protects cookie based sessions against CSRF, in addition to SameSite cookies.
 *
 *	@category		Library
 *	@package		CeusMedia_REST_Server_AccessCheck
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2026 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class Origin implements AccessCheckInterface
{
	protected const array SAFE_METHODS	= ['GET', 'HEAD', 'OPTIONS'];

	/**
	 *	@param	array<int,string>	$allowedOrigins		Foreign origins allowed to write, e.g. ['https://app.example.org']
	 */
	public function __construct(
		protected array $allowedOrigins = [],
	)
	{
	}

	public function check( HttpRequest $request, Route $route ): AccessResult
	{
		if( in_array( strtoupper( $request->getMethod()->get() ), self::SAFE_METHODS, TRUE ) )
			return AccessResult::allow();
		$origin		= $this->getHeader( $request, 'Origin' );
		if( NULL !== $origin && in_array( rtrim( $origin, '/' ), $this->allowedOrigins, TRUE ) )
			return AccessResult::allow();
		$fetchSite	= $this->getHeader( $request, 'Sec-Fetch-Site' );
		if( NULL !== $fetchSite && !in_array( $fetchSite, ['same-origin', 'none'], TRUE ) )
			return AccessResult::deny( 403, 'Cross-site request denied' );
		if( NULL !== $origin ){
			$host	= $this->getHeader( $request, 'X-Forwarded-Host' ) ?? $this->getHeader( $request, 'Host' );
			$port	= parse_url( $origin, PHP_URL_PORT );
			$originHost	= parse_url( $origin, PHP_URL_HOST ).( NULL !== $port && FALSE !== $port ? ':'.$port : '' );
			if( NULL === $host || strtolower( $originHost ) !== strtolower( $host ) )
				return AccessResult::deny( 403, 'Cross-site request denied' );
		}
		return AccessResult::allow();
	}

	protected function getHeader( HttpRequest $request, string $name ): ?string
	{
		$value	= $request->getHeader( $name, FALSE )?->getValue();
		return is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : NULL;
	}
}
