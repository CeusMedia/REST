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
class Auth extends AbstractAccessCheck
{
	/**
	 *	Options: tokens – list of accepted tokens (empty: deny all).
	 */
	public function __construct( Context|FrameworkEnvironment $context, array $options = [] )
	{
		$defaultOptions = [
			'tokens'	=> [],
		];
		$options	= array_merge( $defaultOptions, $options );
		parent::__construct( $context, $options );
	}

	/**
	 *	Accepts "Authorization: Bearer <token>" (also the former "<name>=<token>" and Basic auth password).
	 *	@param		HttpRequest		$request
	 *	@return		string			Empty if granted, otherwise reason
	 */
	public function perform( HttpRequest $request ): string
	{
		Log::debug( 'AccessCheck: Auth: perform' );
		/** @var array<int,string> $tokens */
		$tokens	= array_values( array_filter( (array) $this->options['tokens'], static fn( $token ): bool => is_string( $token ) && '' !== $token ) );
		if( [] === $tokens ){
			Log::error( 'AccessCheck: Auth: no tokens configured, access denied' );
			return 'Access denied';
		}
		$given	= $this->getGivenToken( $request );
		if( NULL !== $given )
			foreach( $tokens as $token )
				if( hash_equals( $token, $given ) )
					return '';
		Log::debug( '> Access denied' );
		return 'Access denied';
	}

	protected function getGivenToken( HttpRequest $request ): ?string
	{
		if( isset( $_SERVER['PHP_AUTH_PW'] ) && is_string( $_SERVER['PHP_AUTH_PW'] ) )
			return $_SERVER['PHP_AUTH_PW'];
		$header	= $_SERVER['HTTP_AUTHORIZATION'] ?? NULL;
		if( !is_string( $header ) ){
			$value	= $request->getHeader( 'Authorization', FALSE )?->getValue();
			$header	= is_string( $value ) ? $value : NULL;
		}
		if( NULL === $header || '' === trim( $header ) )
			return NULL;
		if( 1 === preg_match( '/^Bearer\s+(\S+)$/i', trim( $header ), $matches ) )
			return $matches[1];
		$parts	= explode( '=', $header, 2 );
		return isset( $parts[1] ) ? trim( $parts[1], " \t\"" ) : NULL;
	}
}
