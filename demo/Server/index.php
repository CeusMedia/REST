<?php
(@include '../../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);

use \CeusMedia\REST\Server;
use \CeusMedia\Router\Log;

//  --  SETUP  --  //
error_reporting( E_ALL );
ini_set( 'display_errors', 'On' );
\Loader::registerNew( 'php', NULL, './src/' );
new UI_DevOutput;

//  --  LOGGING  --  //
Log::$level	= Log::LEVEL_ALL;
Log::$file	= __DIR__.'/server.log';
register_shutdown_function( function(){
	if( !is_null( $e = error_get_last() ) )
		Log::error( 'Error', $e );
});

//  --  SERVER  --  //
$options	= array(
//	'forceMimeType' => 'application/json',
//	'routesFile'	=> 'routes.json',
);
$server	= new Server( $options );
$server->addRouterRegistrySource( new \CeusMedia\Router\Registry\Source\JsonFile( 'routes.json' ) );
//$server->registerAccessCheck( 'AccessCheck_User', 'perform' );
$server->handleRequest();
