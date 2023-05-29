<?php
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

use CeusMedia\REST\Server\AbstractAccessCheck;
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
	public function __construct( array $options = [] )
	{
		$defaultOptions = [
			'filePath'	=> 'users.json',
		];
		$options	= array_merge( $defaultOptions, $options );
		parent::__construct( $options );
	}

	public function perform( $request ): string
	{
		Log::debug( 'AccessCheck: Auth: perform' );
		$split		= [];
		$headers	= getallheaders();

		if( isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) )
			$split	= [$_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']];
		else if( isset( $_SERVER['HTTP_AUTHORIZATION'] ) )
			$split	= explode( '=', $_SERVER['HTTP_AUTHORIZATION'] );
		else if( isset( $headers['Authorization'] ) )
			$split	= explode( '=', $headers['Authorization'] );

		Log::debug( '> found: '.json_encode( $split ) );
		if( isset( $split[1] ) ){
			if( trim( $split[1], '"' ) === 'super_geheimes_token' ){
				return '';
			}
		}
		Log::debug( '> Access denied' );
		return 'Access denied';
	}
}
