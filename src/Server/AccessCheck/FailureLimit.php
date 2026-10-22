<?php
declare(strict_types=1);

/**
 *	Limit of failed requests per client IP and route group.
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
 *	Limits failed requests per client IP and group, e.g. wrong passwords or codes on login routes.
 *	Only routes with the route meta "failureLimit" (group name, e.g. "auth") are counted and checked.
 *	A response with one of the failure statuses (default 401, 403) counts as failure. After the limit is
 *	reached, requests of the group are denied with 429 and Retry-After until the period after the first
 *	counted failure is over. Successful requests do not reset the counter, so a valid account cannot be used
 *	to clear the failures of an attack.
 *	Counters are kept in any PSR-16 cache; the client IP respects trusted proxies.
 *	Note: PSR-16 has no atomic increment, so concurrent requests may exceed the limit slightly.
 *
 *	Example: new FailureLimit( $cache, ['auth' => [20, 900]] ) and route meta {"failureLimit": "auth"}
 *
 *	@category		Library
 *	@package		CeusMedia_REST_Server_AccessCheck
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2026 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class FailureLimit implements AccessCheckInterface, ResponseObserverInterface
{
	/** @var array<string,array{limit: int, period: int}> */
	protected array $limits	= [];

	/** @var Closure(): int */
	protected Closure $clock;

	/**
	 *	@param	CacheInterface							$cache
	 *	@param	array<string,array<int|string,int>>		$limits				Group => [failures, period in seconds] or ['limit' => …, 'period' => …]
	 *	@param	array<int,int>							$failureStatuses	Response statuses counted as failure
	 *	@param	string									$metaKey			Route meta key of the group
	 *	@param	callable|NULL							$clock				Returns the current Unix time (tests)
	 */
	public function __construct(
		protected CacheInterface $cache,
		array $limits,
		protected array $failureStatuses = [401, 403],
		protected string $metaKey = 'failureLimit',
		?callable $clock = NULL,
	)
	{
		foreach( $limits as $group => $limit ){
			$count	= $limit['limit'] ?? $limit[0] ?? 0;
			$period	= $limit['period'] ?? $limit[1] ?? 0;
			if( $count < 1 || $period < 1 )
				throw new InvalidArgumentException( 'Invalid failure limit for group "'.$group.'": limit and period must be positive' );
			$this->limits[$group]	= ['limit' => $count, 'period' => $period];
		}
		$this->clock	= NULL !== $clock ? Closure::fromCallable( $clock ) : static fn(): int => time();
	}

	public function check( HttpRequest $request, Route $route ): AccessResult
	{
		$group	= $this->getGroup( $route );
		if( NULL === $group )
			return AccessResult::allow();
		$now	= ( $this->clock )();
		[$count, $until]	= $this->read( $this->getKey( $group, $request ), $now );
		if( $count >= $this->limits[$group]['limit'] )
			return AccessResult::deny( 429, 'Too Many Failed Attempts', ['Retry-After' => (string) max( 1, $until - $now )] );
		return AccessResult::allow();
	}

	public function observeResponse( HttpRequest $request, Route $route, int $status ): void
	{
		$group	= $this->getGroup( $route );
		if( NULL === $group || !in_array( $status, $this->failureStatuses, TRUE ) )
			return;
		$now	= ( $this->clock )();
		$key	= $this->getKey( $group, $request );
		[$count, $until]	= $this->read( $key, $now );
		if( 0 === $count )
			$until	= $now + $this->limits[$group]['period'];
		$this->cache->set( $key, ( $count + 1 ).':'.$until, max( 1, $until - $now ) );
	}

	/*  --  PROTECTED  --  */

	protected function getGroup( Route $route ): ?string
	{
		$group	= $route->getMeta( $this->metaKey );
		if( !is_string( $group ) || '' === $group )
			return NULL;
		if( !isset( $this->limits[$group] ) ){
			Log::warn( 'FailureLimit: unknown group "'.$group.'", not limited' );
			return NULL;
		}
		return $group;
	}

	protected function getKey( string $group, HttpRequest $request ): string
	{
		$ip		= '' !== $request->getIp() ? $request->getIp() : 'unknown';
		return 'rest.failureLimit.'.$group.'.'.hash( 'sha256', $ip );
	}

	/**
	 *	Reads failures and end of period, stored as string "count:until" (some cache adapters store strings only).
	 *	@return		array{int, int}		Count (0 if none or expired) and end of period (Unix time)
	 */
	protected function read( string $key, int $now ): array
	{
		$stored	= $this->cache->get( $key );
		if( !is_string( $stored ) || 1 !== preg_match( '/^(\d+):(\d+)$/', $stored, $matches ) )
			return [0, 0];
		$until	= (int) $matches[2];
		return $until > $now ? [(int) $matches[1], $until] : [0, 0];
	}
}
