<?php
(@include '../../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);

use \CeusMedia\REST\Server as Server;

error_reporting( E_ALL );
\Loader::registerNew( 'php', NULL, './src/' );
new UI_DevOutput;

$options	= array(
//    'forceMimeType' => 'application/json',
	'routesFile'	=> 'routes.json',
);
$server = new Server( $options );
$server->handleRequest();
