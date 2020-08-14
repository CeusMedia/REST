<?php
class AccessCheck_User{
	public function perform( $request ){
		$fileName	= 'users.json';
		if( file_exists( $fileName ) ){
			$data	= json_decode( FS_File_Reader::load( $fileName ), TRUE );
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
	}
}
