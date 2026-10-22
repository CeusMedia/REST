<?php
declare(strict_types=1);

/**
 *	Adapter for access checks registered by class name and method.
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

use CeusMedia\Common\Alg\Obj\MethodFactory;
use CeusMedia\Common\Net\HTTP\Request as HttpRequest;
use CeusMedia\REST\Server\AccessResult;
use CeusMedia\Router\Route;
use ReflectionException;

/**
 *	Adapter for access checks registered by Server::registerAccessCheck( $className, $method, $options ):
 *	the method returns an empty string if granted, otherwise the reason (answered with 401).
 *
 *	@category		Library
 *	@package		CeusMedia_REST_Server_AccessCheck
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2026 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class Legacy implements AccessCheckInterface
{
	/**
	 *	@param	object					$context		Server context (REST context or Hydrogen environment)
	 *	@param	array<string,mixed>		$options
	 */
	public function __construct(
		protected object $context,
		protected string $className,
		protected string $method,
		protected array $options = [],
	)
	{
	}

    /**
     *  @param      HttpRequest     $request
     *  @param      Route           $route
     *  @return     AccessResult
     *  @throws     ReflectionException
     */
	public function check( HttpRequest $request, Route $route ): AccessResult
	{
		ob_start();
		/** @var string $error */
		$error	= MethodFactory::staticCallClassMethod(
			$this->className,
			$this->method,
			[$this->context, $this->options],
			[$request, $route]
		);
		ob_end_clean();
		if( '' !== trim( $error ) )
			return AccessResult::deny( 401, $error );
		return AccessResult::allow();
	}
}
