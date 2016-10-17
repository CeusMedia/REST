<?php
(@include '../../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);

use \CeusMedia\REST\Client as Client;

error_reporting( E_ALL );
\Loader::registerNew( 'php', NULL, './src/' );
new UI_DevOutput;

$baseUri	= 'http://'.getEnv( 'SERVER_NAME' ).dirname( getEnv( 'REQUEST_URI' ) ).'/';

try{
	$client	= new Client( $baseUri );
	$client->expectFormat( 'JSON' );
	$client->expectFormat( 'PHP' );

	remark( 'GET: index' );
	print_m( $client->get( 'test?limit=2&page=2' ) );

//	remark( 'GET: read' );
//	print_m( $client->get( 'test/1476137194' ) );

	/*
	remark( 'POST: create' );
	$data	= $client->post( 'test', array( 'version' => 1 ) );
	print_m( $data );

	remark( 'DELETE: delete' );
	$data	= $client->delete( 'test/'.$data['id'], array( 'version' => 1 ) );
	print_m( $data );
	*/
}
catch( Exception $e ){
	print( UI_HTML_Exception_Page::display( $e ) );
}
