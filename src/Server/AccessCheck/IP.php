<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

/**
 *	...
 *
 *	Copyright (c) 2007-2023 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_REST
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
namespace CeusMedia\REST\Server\AccessCheck;

use CeusMedia\Common\Net\HTTP\Request as HttpRequest;
use CeusMedia\HydrogenFramework\Environment as FrameworkEnvironment;
use CeusMedia\REST\Server\AbstractAccessCheck;
use CeusMedia\REST\Server\Context;
use CeusMedia\Router\Log;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_REST
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class IP extends AbstractAccessCheck
{
	/**
	 *	Options: whitelist, blacklist – IPs or networks (CIDR), IPv4 and IPv6.
	 */
	public function __construct( Context|FrameworkEnvironment $context, array $options = [] )
	{
		$defaultOptions = [
			'whitelist'	=> ['127.0.0.1', '::1'],
			'blacklist'	=> [],
		];
		parent::__construct( $context, array_merge( $defaultOptions, $options ) );
	}

	/**
	 *	Grants access if the client IP is not blacklisted and whitelisted (empty whitelist: everybody).
	 *	The client IP respects trusted proxies, see Net\HTTP\Request::setTrustedProxies().
	 *	@param		HttpRequest		$request
	 *	@return		string			Empty if granted, otherwise reason
	 */
	public function perform( HttpRequest $request ): string
	{
		Log::debug( 'AccessCheck: IP: perform' );
		$ip			= '' !== $request->getIp() ? $request->getIp() : (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );
		/** @var array<int,string> $whitelist */
		$whitelist	= (array) $this->options['whitelist'];
		/** @var array<int,string> $blacklist */
		$blacklist	= (array) $this->options['blacklist'];
		Log::debug( '> IP: '.$ip );
		if( HttpRequest::isIpInList( $ip, $blacklist ) )
			return 'Access denied';
		if( [] !== $whitelist && !HttpRequest::isIpInList( $ip, $whitelist ) )
			return 'Access denied';
		return '';
	}
}
