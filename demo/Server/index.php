<?php
(@include '../../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);

use \CeusMedia\Cache\Adapter\Folder as FolderCache;
use \CeusMedia\REST\Server;
use \CeusMedia\REST\Server\AccessCheck;
use \CeusMedia\Router\Registry\Source\JsonFile as JsonFileRegistrySource;
use \CeusMedia\Router\Log;

//  --  SETUP  --  //
error_reporting( E_ALL );
ini_set( 'display_errors', 'On' );
new \CeusMedia\Common\Loader( 'php', './src/' );
new \CeusMedia\Common\UI\DevOutput;

//  --  LOGGING  --  //
Log::$level	= Log::LEVEL_ALL;
Log::$file	= __DIR__.'/server.log';
/* @todo remove – this is done by server, now
register_shutdown_function( function(){
	if( !is_null( $e = error_get_last() ) )
		Log::error( 'Error', $e );
});*/

//  --  SERVER  --  //
$options	= [
	'forceMimeType'		=> 'application/json',
	'responseHeaders'	=> Server::SECURE_RESPONSE_HEADERS,
//	'routesFile'		=> 'routes.json',
];
$server	= new Server( $options );
$server->addRouterRegistrySource( new JsonFileRegistrySource( 'routes.json' ) );

//  --  ACCESS CHECKS  --  //
//  counters of rate and failure limits, outside the document root
$cache	= new FolderCache( sys_get_temp_dir().'/ceusmedia-rest-demo/' );

//  no writing requests from foreign sites (CSRF); allow a client on another origin like this:
//  new AccessCheck\Origin( ['http://localhost:8080'] )
$server->addAccessCheck( new AccessCheck\Origin() );

//  per client IP: 10 writing requests per minute (routes with meta "rateLimit": "write"), 120 others
$server->addAccessCheck( new AccessCheck\RateLimit( $cache, ['write' => [10, 60], 'api' => [120, 60]], 'api' ) );

//  per client IP: max. 10 failed logins (401, 403) within 15 minutes (routes with meta "failureLimit": "auth")
$server->addAccessCheck( new AccessCheck\FailureLimit( $cache, ['auth' => [10, 900]] ) );

//  HTTP Basic auth against users.json; routes without own roles need role "user",
//  writing routes need role "editor" (see routes.json), route "/" is public
$server->addAccessCheck( new AccessCheck\RouteAccess( new Authenticator( __DIR__.'/users.json' ), ['user'] ) );

$server->handleRequest();

