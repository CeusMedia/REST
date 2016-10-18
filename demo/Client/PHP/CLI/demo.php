<?php
(@include '../../../../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);

$baseUri	= 'http://localhost/lib/CeusMedia/REST/demo/Server/';
//$baseUri	= 'http://domain.tld/path/to/server/';

try{
	$client	= new \CeusMedia\REST\Client( $baseUri );
//	$client->expectFormat( 'PHP' );
	$client->expectFormat( 'JSON' );

	$items	= $client->get( 'test?limit=2&page=1' );
	print( 'GET: index'.PHP_EOL );
	print( json_encode( $items, JSON_PRETTY_PRINT ).PHP_EOL );

	if( !empty( $items->data->items ) ){
		$items	= array_values( (array) $items->data->items );
		$item	= $client->get( 'test/'.$items[0]->id );
		print( 'GET: read #'.$items[0]->id.PHP_EOL );
		print( json_encode( $item, JSON_PRETTY_PRINT ).PHP_EOL );
	}

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
catch( \Exception $e ){
	print( 'Exception: '.$e->getMessage().PHP_EOL );
}
