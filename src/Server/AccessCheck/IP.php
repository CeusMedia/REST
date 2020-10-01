<?php
/**
 *	...
 *
 *	Copyright (c) 2007-2020 Christian Würker (ceusmedia.de)
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
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
namespace CeusMedia\REST\Server\AccessCheck;

use CeusMedia\REST\Server\AbstractAccessCheck;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_REST
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2020 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class IP extends AbstractAccessCheck
{
	public function __construct( array $options = array() )
	{
		$defaultOptions = array(
			'whitelist'	=> ['127.0.0.1', '::1'],
			'blacklist'	=> [],
		);
		parent::__construct( array_merge( $defaultOptions, $options ) );
	}

	public function perform( $request ): string
	{
		$ip		= $_SERVER['REMOTE_ADDR'];
		if( preg_match( '/:/', $ip ) === 0 )
			return $this->performV6( $ip );
		return $this->performV4( $ip );
	}

	protected function performV4( string $ip ): string
	{
		foreach( $this->options->whitelist as $allowed ){
			if( $ip === $allowed )												// Exakte Übereinstimmung, direkt fertig.
				return '';
			else if( strpos( $allowed, '/' ) !== FALSE ){
				// Netzmaske prüfen
				list( $allowed, $netmask )	= explode( '/', $allowed, 2 );
				$x	= explode( '.', $allowed );
				while( count( $x ) < 4 )
					$x[]	= '0';
				$rangeDecimal	= ip2long( vsprintf( "%u.%u.%u.%u", array(
					(int) $x[0],
					(int) $x[1],
					(int) $x[2],
					(int) $x[3]
				) ) );
				$ipDecimal			= ip2long( $ip );
				$wildcardDecimal	= pow( 2, ( 32 - (int) $netmask ) ) - 1;
				$netmaskDecimal		= ~$wildcardDecimal;
				if( ( $ipDecimal & $netmaskDecimal ) == ( $rangeDecimal & $netmaskDecimal ) )	// Netzmaske enhält die IP
					return '';
			}
		}
		return 'Access for your IP ('.$ip.') denied';
	}

	protected function performV6( string $ip ): string
	{
		return '';
	}
}
