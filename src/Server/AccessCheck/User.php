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

	/**
	 *	HTTP Basic authentication against a JSON file of users:
	 *	{"name": {"password": "<hash from password_hash()>", "enabled": true}}.
	 *	Plain text passwords still work but are deprecated.
	 *	@param		HttpRequest		$request
	 *	@return		string			Empty if granted, otherwise reason (always the same for the client)
	 */
	public function perform( HttpRequest $request ): string
	{
		Log::debug( 'AccessCheck: User: perform' );
		$filePath	= (string) $this->options['filePath'];
		if( !file_exists( $filePath ) ){
			Log::error( 'AccessCheck: User: users file missing: '.$filePath );
			return 'Access denied';
		}
		$data	= (array) JsonReader::load( $filePath, TRUE );
		$users	= [];
		foreach( $data as $key => $value ){
			if( isset( $value['disabled'] ) ){
				unset( $value['disabled'] );
				$value['enabled']	= FALSE;
			}
			$users[$key]	= (object) array_merge( [
				'password'	=> '',
				'enabled'	=> TRUE,
			], $value );
		}
		$givenUsername	= trim( (string) ( $_SERVER['PHP_AUTH_USER'] ?? '' ) );
		$givenPassword	= (string) ( $_SERVER['PHP_AUTH_PW'] ?? '' );
		if( '' === $givenUsername || '' === $givenPassword )
			return 'Access denied';

		$user		= $users[$givenUsername] ?? NULL;
		$stored		= NULL !== $user ? (string) $user->password : '';
		$isHash		= NULL !== password_get_info( $stored )['algo'];
		//  verify also for unknown users, so the response time reveals nothing
		$valid		= $isHash
			? password_verify( $givenPassword, $stored )
			: ( '' !== $stored && hash_equals( $stored, $givenPassword ) );
		if( NULL !== $user && !$isHash && '' !== $stored )
			Log::warn( 'AccessCheck: User: plain text password for "'.$givenUsername.'" is deprecated, use password_hash()' );
		if( NULL === $user || !$user->enabled || !$valid ){
			Log::debug( '> Access denied for user: '.$givenUsername );
			return 'Access denied';
		}
		return '';
	}
}
