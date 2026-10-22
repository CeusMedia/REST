<?php
declare(strict_types=1);

/**
 *	Rate limit per client IP and route group.
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
use CeusMedia\Router\Log;
use CeusMedia\Router\Route;
use Closure;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

/**
 *	Limits requests per client IP and group (fixed time windows). The group comes from the route meta
 *	"rateLimit" (e.g. "auth"), otherwise the default group; routes without group are not limited.
 *	Counters are kept in any PSR-16 cache (e.g. CeusMedia/Cache adapters Memcache, Redis, Folder).
 *	The client IP respects trusted proxies (CeusMedia/Common Request::setTrustedProxies()).
 *	Note: PSR-16 has no atomic increment, so concurrent requests may exceed the limit slightly.
 *
 *	Example: new RateLimit( $cache, ['auth' => [10, 60], 'api' => [600, 60]], 'api' )
 *
 *	@category		Library
 *	@package		CeusMedia_REST_Server_AccessCheck
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2026 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class RateLimit implements AccessCheckInterface
{
	/** @var array<string,array{limit: int, period: int}> */
	protected array $limits	= [];

	/** @var Closure(): int */
	protected Closure $clock;

	/**
	 *	@param	CacheInterface							$cache
	 *	@param	array<string,array<int|string,int>>		$limits			Group => [limit, period in seconds] or ['limit' => …, 'period' => …]
	 *	@param	string|NULL								$defaultGroup	Group for routes without meta, NULL: not limited
	 *	@param	string									$metaKey		Route meta key of the group
	 *	@param	callable|NULL							$clock			Returns the current Unix time (tests)
	 */
	public function __construct(
		protected CacheInterface $cache,
		array $limits,
		protected ?string $defaultGroup = NULL,
		protected string $metaKey = 'rateLimit',
		?callable $clock = NULL,
	)
	{
		foreach( $limits as $group => $limit ){
			$count	= $limit['limit'] ?? $limit[0] ?? 0;
			$period	= $limit['period'] ?? $limit[1] ?? 0;
			if( $count < 1 || $period < 1 )
				throw new InvalidArgumentException( 'Invalid rate limit for group "'.$group.'": limit and period must be positive' );
			$this->limits[$group]	= ['limit' => $count, 'period' => $period];
		}
		$this->clock	= NULL !== $clock ? Closure::fromCallable( $clock ) : static fn(): int => time();
	}

	public function check( HttpRequest $request, Route $route ): AccessResult
	{
		$group	= $route->getMeta( $this->metaKey, $this->defaultGroup );
		if( !is_string( $group ) || '' === $group )
			return AccessResult::allow();
		if( !isset( $this->limits[$group] ) ){
			Log::warn( 'RateLimit: unknown group "'.$group.'", not limited' );
			return AccessResult::allow();
		}
		['limit' => $limit, 'period' => $period]	= $this->limits[$group];
		$now	= ( $this->clock )();
		$window	= intdiv( $now, $period );
		$ip		= '' !== $request->getIp() ? $request->getIp() : 'unknown';
		$key	= 'rest.rateLimit.'.$group.'.'.$window.'.'.hash( 'sha256', $ip );
		$stored	= $this->cache->get( $key, 0 );
		//  some cache adapters return strings
		$count	= ( is_int( $stored ) || ( is_string( $stored ) && ctype_digit( $stored ) ) ? (int) $stored : 0 ) + 1;
		$this->cache->set( $key, $count, $period );
		if( $count > $limit ){
			$retryAfter	= ( $window + 1 ) * $period - $now;
			return AccessResult::deny( 429, 'Too Many Requests', ['Retry-After' => (string) max( 1, $retryAfter )] );
		}
		return AccessResult::allow();
	}
}
