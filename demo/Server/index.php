<?php
(@include '../../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);

use \CeusMedia\REST\Server as Server;

error_reporting( E_ALL );
ini_set( 'display_errors', 'On' );
\Loader::registerNew( 'php', NULL, './src/' );
new UI_DevOutput;

$options	= array(
	'forceMimeType' => 'application/json',
//	'routesFile'	=> 'routes.json',
);

$server	= new Server( $options );
$server->addRouterRegistrySource( new \CeusMedia\Router\Registry\Source\JsonFile( 'routes.json' ) );
//$server->registerAccessCheck( 'AccessCheck_User', 'perform' );
$server->handleRequest();
