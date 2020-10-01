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

use FS_File_JSON_Reader as JsonReader;
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
class User extends AbstractAccessCheck
{
	public function __construct( array $options = array() )
	{
		$defaultOptions = array(
			'filePath'	=> 'users.json',
		);
		parent::__construct( array_merge( $defaultOptions, $options ) );
	}

	public function perform( $request ): string
	{
		if( file_exists( $this->options->filePath ) ){
			$data	= JsonReader::load( $this->options->filePath, TRUE );
			$users	= array();
			foreach( $data as $key => $value ){
				if( isset( $value['disabled'] ) ){
					unset( $value['disabled'] );
					$value['enabled']	= false;
				}
				$users[$key]	= (object) array_merge( array(
					'password'	=> '',
					'enabled'	=> true,
				), $value );
			}
			$givenUsername	= $_SERVER['PHP_AUTH_USER'] ?? NULL;
			$givenPassword	= $_SERVER['PHP_AUTH_PW'] ?? NULL;
			if( !strlen( $givenUsername ) || !strlen( $givenPassword ) )
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
