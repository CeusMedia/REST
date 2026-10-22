<?php
declare(strict_types=1);

/**
 *	Access by roles of the route.
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
use CeusMedia\REST\Server\AuthenticatorInterface;
use CeusMedia\Router\Route;

/**
 *	Grants access if the client has one of the roles of the route (Router: "roles").
 *	Routes without roles get the default roles; to open a route anyway, set meta "public": true.
 *	Secure default: new RouteAccess( $authenticator, ['user'] ) protects every route not marked public.
 *	Denied: 401 if the client has no roles (not authenticated), 403 if it lacks the role.
 *
 *	@category		Library
 *	@package		CeusMedia_REST_Server_AccessCheck
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2026 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class RouteAccess implements AccessCheckInterface
{
	/**
	 *	@param	AuthenticatorInterface		$authenticator		Provides the roles of the client
	 *	@param	array<int,string>			$defaultRoles		Roles for routes without own roles
	 *	@param	string						$publicMetaKey		Meta key marking a route as public
	 */
	public function __construct(
		protected AuthenticatorInterface $authenticator,
		protected array $defaultRoles = [],
		protected string $publicMetaKey = 'public',
	)
	{
	}

	public function check( HttpRequest $request, Route $route ): AccessResult
	{
		if( TRUE === $route->getMeta( $this->publicMetaKey ) )
			return AccessResult::allow();
		/** @var array<int,string> $required */
		$required	= [] !== $route->getRoles() ? $route->getRoles() : $this->defaultRoles;
		if( [] === $required )
			return AccessResult::allow();
		$given	= $this->authenticator->getRoles( $request );
		if( [] === $given )
			return AccessResult::deny( 401, 'Authentication required' );
		if( [] === array_intersect( $required, $given ) )
			return AccessResult::deny( 403, 'Access denied' );
		return AccessResult::allow();
	}
}
