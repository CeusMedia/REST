<?php
abstract class Controller{

	public function __construct( $client, $request ){
		$this->client	= $client;
		$this->request	= $request;

		$host	= getEnv( 'SERVER_NAME' ).':'.getEnv( 'SERVER_PORT' );
		$path	= dirname( getEnv( 'SCRIPT_NAME' ) ).'/';
		$this->baseUri	= 'http://'.$host.$path;
	}

	protected function redirect( $uri = NULL ){
		header( 'Location: '.$this->baseUri.$uri );
		exit;
	}

	abstract public function handle( ADT_List_Dictionary $arguments );
}
?>
