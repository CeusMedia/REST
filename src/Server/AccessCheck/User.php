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

use CeusMedia\Common\FS\File\JSON\Reader as JsonReader;
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
class User extends AbstractAccessCheck
{
	public function __construct( Context|FrameworkEnvironment $context, array $options = [] )
	{
		$defaultOptions = [
			'filePath'	=> 'users.json',
		];
		parent::__construct( $context, array_merge( $defaultOptions, $options ) );
	}

	public function perform( HttpRequest $request ): string
	{
		Log::debug( 'AccessCheck: User: perform' );
		if( file_exists( $this->options['filePath'] ) ){
			$data	= (array) JsonReader::load( $this->options['filePath'], TRUE );
			$users	= [];
			foreach( $data as $key => $value ){
				if( isset( $value['disabled'] ) ){
					unset( $value['disabled'] );
					$value['enabled']	= false;
				}
				$users[$key]	= (object) array_merge( [
					'password'	=> '',
					'enabled'	=> true,
				], $value );
			}
			$givenUsername	= trim( $_SERVER['PHP_AUTH_USER'] ?? '' );
			$givenPassword	= trim( $_SERVER['PHP_AUTH_PW'] ?? '' );
			if( strlen( $givenUsername ) === 0 || strlen( $givenPassword ) === 0 )
				return 'Insufficient credentials: username and password are needed';
			if( !array_key_exists( $givenUsername, $users ) )
				return 'User is unknown';
			if( !$users[$givenUsername]->enabled )
				return 'User is disabled';
			if( $users[$givenUsername]->password !== $givenPassword )
				return 'Password is invalid';
		}
		return '';
	}
}
