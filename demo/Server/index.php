<?php
(@include '../../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);

use \CeusMedia\REST\Server;
use \CeusMedia\REST\Server\AccessCheck\IP as AccessCheckIp;
use \CeusMedia\REST\Server\AccessCheck\User as AccessCheckUser;
use \CeusMedia\Router\Registry\Source\JsonFile as JsonFileRegistrySource;
use \CeusMedia\Router\Log;

//  --  SETUP  --  //
error_reporting( E_ALL );
ini_set( 'display_errors', 'On' );
\Loader::registerNew( 'php', NULL, './src/' );
new UI_DevOutput;

//  --  LOGGING  --  //
Log::$level	= Log::LEVEL_ALL;
Log::$file	= __DIR__.'/server.log';
/* @todo remove – this is done by server, now
register_shutdown_function( function(){
	if( !is_null( $e = error_get_last() ) )
		Log::error( 'Error', $e );
});*/

//  --  SERVER  --  //
$options	= array(
//	'forceMimeType' => 'application/json',
//	'routesFile'	=> 'routes.json',
);
$server	= new Server( $options );
$server->addRouterRegistrySource( new JsonFileRegistrySource( 'routes.json' ) );
//$server->registerAccessCheck( AccessCheckUser::CLASS, 'perform' );
$server->registerAccessCheck( AccessCheckIp::CLASS, 'perform', ['whitelist' => ['127.0.0.0']] );
$server->handleRequest();
