<?php
(@include '../../../../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);

$baseUri	= 'http://localhost/lib/CeusMedia/REST/demo/Server/';
//$baseUri	= 'http://domain.tld/path/to/server/';

$isConsole	= !getEnv( 'HTTP_HOST' );

if( !$isConsole )
	ob_start();

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

if( !$isConsole ){
	$stdout	= ob_get_clean();

	$body	= '

<div class="container">
	<h1><span class="muted">Ceus Media REST</span> PHP CLI Client Demo</h2>
	<div class="row-fluid">
		<div class="span12">
			<h4>Code</h4>
			<pre>
require_once( \'vendor/autoload.php\');

$baseUri	= \'http://domain.tld/path/to/server/\';

try{
	$client	= new \CeusMedia\REST\Client( $baseUri );
	$client->expectFormat( \'JSON\' );

	$items	= $client->get( \'test?limit=2&page=1\' );
	print( \'GET: index\'.PHP_EOL );
	print( json_encode( $items, JSON_PRETTY_PRINT ).PHP_EOL );

	if( !empty( $items->data->items ) ){
		$items	= array_values( (array) $items->data->items );
		$item	= $client->get( \'test/\'.$items[0]->id );
		print( \'GET: read #\'.$items[0]->id.PHP_EOL );
		print( json_encode( $item, JSON_PRETTY_PRINT ).PHP_EOL );
	}
}
catch( \Exception $e ){
	print( \'Exception: \'.$e->getMessage().PHP_EOL );
}
</pre>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span12">
			<h4>Output</h4>
			<pre>'.$stdout.'</pre>
		</div>
	</div>
</div>';
	$page	= new UI_HTML_PageFrame();
	$page->setBaseHref( 'http://'.getEnv( 'SERVER_NAME' ).':'.getEnv( 'SERVER_PORT' ).dirname( getEnv( 'SCRIPT_NAME' ) ).'/' );
	$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
	$page->addStylesheet( 'https://cdn.ceusmedia.de/fonts/FontAwesome/font-awesome.min.css' );
	$page->addJavaScript( 'https://cdn.ceusmedia.de/js/jquery/1.10.2.min.js' );
	$page->addJavaScript( 'https://cdn.ceusmedia.de/js/bootstrap.min.js' );
	$page->addBody( $body );
	print( $page->build() );
}
